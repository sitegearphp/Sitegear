<?php
/*!
 * This file is a part of Sitegear.
 * Copyright (c) Ben New, Leftclick.com.au
 * See the LICENSE and README files in the main source directory for details.
 * http://sitegear.org/
 */

namespace Sitegear\Module\ResourcesIntegration;

use Sitegear\Base\Resources\ResourceLocations;
use Sitegear\Core\Engine\Engine;
use Sitegear\Core\Module\AbstractCoreModule;
use Sitegear\Util\LoggerRegistry;
use Sitegear\Util\NameUtilities;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

/**
 * This module handles requests for all internal resources (/sitegear/resources/*).
 *
 * @method \Sitegear\Core\Engine\Engine getEngine()
 */
class ResourcesIntegrationModule extends AbstractCoreModule {

	//-- Constants --------------------

	/**
	 * Special value for the "location" attribute representing the engine scope.
	 */
	const LOCATION_ATTRIBUTE_ENGINE = 'engine';

	/**
	 * Special value for the "location" attribute representing the vendor-resources package scope.
	 */
	const LOCATION_ATTRIBUTE_VENDOR = 'vendor';

	//-- ModuleInterface Methods --------------------

	/**
	 * {@inheritDoc}
	 */
	public function getDisplayName() {
		return 'Site Resources Integration';
	}

	//-- BootstrapModuleInterface Methods --------------------

	/**
	 * {@inheritDoc}
	 */
	public function resourceController(Request $request) {
		LoggerRegistry::debug('ResourcesIntegrationModule::resourceController');

		LoggerRegistry::info('location = ' . $request->attributes->get('location') . ', path = ' . $request->attributes->get('path'));

		$location = $request->attributes->get('location');
		switch ($location) {
			case self::LOCATION_ATTRIBUTE_ENGINE:
				$locationPath = ResourceLocations::RESOURCE_LOCATION_ENGINE;
				break;
			case self::LOCATION_ATTRIBUTE_VENDOR:
				$locationPath = ResourceLocations::RESOURCE_LOCATION_VENDOR;
				break;
			default:
				$locationPath = ResourceLocations::RESOURCE_LOCATION_MODULE;
		}
		$path = $this->getEngine()->getSiteInfo()->getPublicPath($locationPath, $location, $request->attributes->get('path'));
		if (!file_exists($path)) {
			throw new FileNotFoundException($path);
		}
		return $this->getEngine()->createFileResponse($request, $path);
	}

}