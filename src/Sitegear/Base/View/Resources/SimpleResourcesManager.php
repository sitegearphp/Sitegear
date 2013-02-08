<?php
/*!
 * This file is a part of Sitegear.
 * Copyright (c) Ben New, Leftclick.com.au
 * See the LICENSE and README files in the main source directory for details.
 * http://sitegear.org/
 */

namespace Sitegear\Base\View\Resources;

use Sitegear\Util\LoggerRegistry;
use Sitegear\Util\NameUtilities;

/**
 * Provides a simple implementation of ResourcesManagerInterface, which activates all passed-in requirements
 * (dependencies) of a resource before activating the resource itself.
 */
class SimpleResourcesManager implements ResourcesManagerInterface {

	//-- Attributes --------------------

	/**
	 * @var string[]
	 */
	private $types;

	/**
	 * @var string[]
	 */
	private $resources;

	//-- Constructor --------------------

	public function __construct() {
		$this->types = array();
		$this->resources = array();
	}

	//-- ResourcesManagerInterface Methods --------------------

	/**
	 * {@inheritDoc}
	 */
	public function registerType($type, $format) {
		if ($this->isTypeRegistered($type)) {
			throw new \LogicException(sprintf('Could not register resource type "%s" because the type has already been registered.', $type));
		}
		if (!$this->isValidType($type)) {
			throw new \InvalidArgumentException(sprintf('Could not register resource type "%s" because it is not a valid type specifier (alphanumeric characters and hyphens only)', $type));
		}
		LoggerRegistry::debug(sprintf('SimpleResourcesManager registering type "%s" with format "%s"', $type, $format));
		$this->types[$type] = $format;
	}

	/**
	 * {@inheritDoc}
	 */
	public function registerTypeMap(array $typeMap) {
		foreach ($typeMap as $type => $format) {
			$this->registerType($type, $format);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function isTypeRegistered($type) {
		return array_key_exists($type, $this->types);
	}

	/**
	 * {@inheritDoc}
	 */
	public function types() {
		return array_keys($this->types);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFormat($type) {
		return array_key_exists($type, $this->types) ? $this->types[$type] : null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function register($key, $type, $url, $active=false, $requires=array()) {
		if (!$this->isTypeRegistered($type)) {
			throw new \DomainException(sprintf('Could not add resource "%s" of type "%s" because the type has not been registered.', $key, $type));
		}
		if (!$this->isRegistered($key)) {
			LoggerRegistry::debug(sprintf('SimpleResourcesManager registering resource "%s" with type "%s" and "%s"', $key, $type, $url));
			$this->resources[] = array(
				'key' => $key,
				'type' => $type,
				'url' => $url,
				'requires' => is_array($requires) ? $requires : array( $requires ),
				'active' => false
			);
			if ($active) {
				$this->activate($key);
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function registerMap(array $map) {
		LoggerRegistry::debug('SimpleResourcesManager registering resource map');
		foreach ($map as $key => $resource) {
			$this->register($key,
				$resource['type'],
				$resource['url'],
				array_key_exists('active', $resource) ? $resource['active'] : false,
				array_key_exists('requires', $resource) ? $resource['requires'] : array()
			);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function isRegistered($key) {
		$result = false;
		foreach ($this->resources as $resource) {
			$result = $result || $key === $resource['key'];
		}
		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isActive($key) {
		$result = false;
		foreach ($this->resources as $resource) {
			$result = $result || ($key === $resource['key'] && $resource['active']);
		}
		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getType($key) {
		$result = null;
		foreach ($this->resources as $resource) {
			if ($key === $resource['key']) {
				$result = $resource['type'];
			}
		}
		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getUrl($key) {
		$result = null;
		foreach ($this->resources as $resource) {
			if ($key === $resource['key']) {
				$result = $resource['url'];
			}
		}
		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAllUrls($type, $includeInactive=false) {
		if (!is_null($type) && !$this->isTypeRegistered($type)) {
			throw new \DomainException(sprintf('Could not retrieve URLs of resource type "%s" because the type has not been registered.', $type));
		}
		$result = array();
		foreach ($this->resources as $resource) {
			if (($includeInactive || $resource['active']) && $resource['type'] === $type) {
				$result[] = $resource['url'];
			}
		}
		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	public function activate($key) {
		LoggerRegistry::debug(sprintf('SimpleResourcesManager activating resource "%s"', $key));
		foreach ($this->resources as $index => $resource) {
			if ($key === $resource['key']) {
				foreach ($resource['requires'] as $require) {
					$this->activate($require);
				}
				$this->resources[$index]['active'] = true;
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function render($type) {
		LoggerRegistry::debug(sprintf('SimpleResourcesManager rendering resources of type "%s"', $type));
		if (!$this->isTypeRegistered($type)) {
			throw new \DomainException(sprintf('Could not render resources of type "%s" because the type has not been registered.', $type));
		}
		$format = $this->getFormat($type);
		$rendered = '';
		foreach ($this->getAllUrls($type) as $url) {
			// Check for internal specifier, which are mapped to real URLs via config
			$rendered .= preg_replace('/\\[\\[\s*url\s*\\]\\]/', $url, $format) . PHP_EOL;
		}
		return $rendered;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isValidType($type) {
		return preg_match('/^[a-zA-Z0-9\-]+$/', $type) > 0;
	}

	//-- Magic Methods --------------------

	/**
	 * {@inheritDoc}
	 *
	 * This implementation allows resources to be rendered by calling a method named for the resource type. For example
	 * "$view->resources()->css()" renders the resources of type 'css'.  The method name will be converted to
	 * dashed-lower form, so a call like "$view->resources()->someType()" will refer to the type "some-type".
	 */
	public function __call($name, $arguments) {
		return $this->render(NameUtilities::convertToDashedLower($name));
	}

}
