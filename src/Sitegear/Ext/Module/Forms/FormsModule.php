<?php
/*!
 * This file is a part of Sitegear.
 * Copyright (c) Ben New, Leftclick.com.au
 * See the LICENSE and README files in the main source directory for details.
 * http://sitegear.org/
 */

namespace Sitegear\Ext\Module\Forms;

use Sitegear\Base\Module\AbstractUrlMountableModule;
use Sitegear\Base\View\ViewInterface;
use Sitegear\Base\View\Resources\ResourceLocations;
use Sitegear\Base\Engine\EngineInterface;
use Sitegear\Util\NameUtilities;
use Sitegear\Util\TypeUtilities;
use Sitegear\Util\LoggerRegistry;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Collection;

/**
 * Displays and allows management of programmable HTML forms.
 *
 * @method \Sitegear\Core\Engine\Engine getEngine()
 */
class FormsModule extends AbstractUrlMountableModule {

	//-- Attributes --------------------

	/**
	 * @var array[]|null
	 */
	private $data;

	//-- Constructor --------------------

	public function __construct(EngineInterface $engine) {
		parent::__construct($engine);
		$this->data = null;
	}

	//-- ModuleInterface Methods --------------------

	/**
	 * {@inheritDoc}
	 */
	public function getDisplayName() {
		return 'Web Forms';
	}

	//-- AbstractUrlMountableModule Methods --------------------

	/**
	 * {@inheritDoc}
	 */
	protected function buildRoutes() {
		$routes = new RouteCollection();
		$routes->add('form', new Route(sprintf('%s/{slug}', $this->getMountedUrl())));
		return $routes;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function buildNavigationData($mode) {
		return array();
	}

	//-- Page Controller Methods --------------------

	/**
	 * Handles a form submission.
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 *
	 * @throws \RuntimeException
	 */
	public function formController(Request $request) {
		LoggerRegistry::debug('FormsModule::formController');

		// Get the form and submission details
		$formKey = $request->attributes->get('slug');
		$form = $this->getData($formKey);
		// TODO Multiple pages
		$currentPage = 0;
		$page = $form['pages'][$currentPage];
		$data = $request->request->all();

		// Perform validation, only continue if no errors
		$validator = Validation::createValidator();
		$constraints = $this->getConstraints($page['fieldsets'], $form['fields']);
		$violations = $validator->validateValue($data, $constraints);
		if ($valid = ($violations->count() === 0)) {
			// Execute configured processors
			if (isset($page['processors'])) {
				foreach ($page['processors'] as $processor) {
					$this->executeProcessor($processor, $data);
				}
			}
		} else {
			// An error occurred.
			$session = $this->getEngine()->getSession();

			// Set the values into the session so they can be displayed after redirecting
			foreach ($page['fieldsets'] as $fieldset) {
				foreach ($fieldset['fields'] as $fieldSpec) {
					if ($fieldSpec['mode'] === 'field') {
						$field = $fieldSpec['field'];
						$fieldValue = $request->request->get($field);
						$session->set($this->getSessionKey($formKey, $field, 'value'), $fieldValue);
					}
				}
			}

			// Set the error messages into the session so they can be displayed after redirecting
			foreach ($violations as $violation) { /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
				$field = $violation->getPropertyPath();
				$session->set($this->getSessionKey($formKey, $field, 'violation'), $violation->getMessage());
				LoggerRegistry::debug(sprintf('Set validation violation for field "%s" to "%s"', $field, $violation->getMessage()));
			}
		}

		// Redirect to the target page
		$targetUrl = ($valid && isset($form['target-url']) && (++$page >= sizeof($form['pages']))) ?
				$form['target-url'] :
				$request->request->get('form-url', $request->getBaseUrl());
		return new RedirectResponse($request->getUriForPath('/' . $targetUrl));
	}

	//-- Component Controller Methods --------------------

	/**
	 * Display a form.
	 *
	 * @param \Sitegear\Base\View\ViewInterface $view
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param string $formKey
	 */
	public function formComponent(ViewInterface $view, Request $request, $formKey) {
		LoggerRegistry::debug('FormsModule::formComponent');
		$this->applyConfigToView('components.form', $view);
		$form = $this->getData($formKey);
		// TODO Multiple pages
		$currentPage = 0;
		$view['current-page'] = $currentPage;
		$view['page-count'] = sizeof($form['pages']);
		$view['form-name'] = $formKey;
		$view['page'] = $form['pages'][$currentPage];
		$view['fields'] = $form['fields'];
		$view['action-url'] = sprintf('%s/%s', $this->getMountedUrl(), $formKey);
		$view['form-url'] = ltrim($request->getPathInfo(), '/');
	}

	/**
	 * Display a regular, single <input> element form field.
	 *
	 * @param \Sitegear\Base\View\ViewInterface $view
	 * @param string $name
	 * @param array $field
	 * @param null $value
	 */
	public function inputComponent(ViewInterface $view, $name, array $field, $value=null) {
		$view['name'] = $name;
		$view['field'] = $field;
		$view['value'] = $value;
	}

	/**
	 * Display a set of checkbox or radio button <input> elements as a single form field.
	 *
	 * @param \Sitegear\Base\View\ViewInterface $view
	 * @param string $name
	 * @param array $field
	 * @param null $value
	 */
	public function checkboxesComponent(ViewInterface $view, $name, array $field, $value=null) {
		$view['name'] = $name;
		$view['field'] = $field;
		$view['value'] = $value;
	}

	/**
	 * Display a <select> element for single or multiple selections.
	 *
	 * @param \Sitegear\Base\View\ViewInterface $view
	 * @param string $name
	 * @param array $field
	 * @param null $value
	 */
	public function selectComponent(ViewInterface $view, $name, array $field, $value=null) {
		$view['name'] = $name;
		$view['field'] = $field;
		$view['value'] = $value;
	}

	/**
	 * Display a <textarea> form field element.
	 *
	 * @param \Sitegear\Base\View\ViewInterface $view
	 * @param string $name
	 * @param array $field
	 * @param null $value
	 */
	public function textareaComponent(ViewInterface $view, $name, array $field, $value=null) {
		$view['name'] = $name;
		$view['field'] = $field;
		$view['value'] = $value;
	}

	//-- Public Methods --------------------

	/**
	 * Get the error for the given field in the given form, and clear the error message from the session.
	 *
	 * If this method is called twice in a row with the same parameters, the second call will return null.
	 *
	 * @param string $form
	 * @param string $field
	 * @param string $subKey
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function extractFieldSessionData($form, $field, $subKey, $default=null) {
		$sessionKey = $this->getSessionKey($form, $field, $subKey);
		$session = $this->getEngine()->getSession();
		$value = $session->get($sessionKey, $default);
		$session->remove($sessionKey);
		return $value;
	}

	//-- Internal Methods --------------------

	/**
	 * Get the data for the given form key, loading the data file first if necessary.
	 *
	 * @param string $formKey
	 *
	 * @return array
	 *
	 * @throws \InvalidArgumentException If the form key cannot be found or loaded.
	 */
	protected function getData($formKey) {
		if (is_null($this->data)) {
			// The main data array is uninitialised, load the main file if it exists.
			$path = $this->getEngine()->getSiteInfo()->getSitePath(ResourceLocations::RESOURCE_LOCATION_SITE, $this, 'forms.json');
			$this->data = file_exists($path) ? json_decode(file_get_contents($path), true) : array();
		}
		if (!isset($this->data[$formKey])) {
			// The data array does not currently contain the given key, look for a form-specific file and load it.
			$pathWithKey = $this->getEngine()->getSiteInfo()->getSitePath(ResourceLocations::RESOURCE_LOCATION_SITE, $this, sprintf('%s.json', $formKey));
			if (file_exists($pathWithKey)) {
				$this->data[$formKey] = json_decode(file_get_contents($pathWithKey), true);
			}
		}
		if (!isset($this->data[$formKey])) {
			// The data array still does not contain the given key, which means the form is unavailable.
			throw new \InvalidArgumentException(sprintf('FormsModule received unknown form key "%s"', $formKey));

		}
		$this->normaliseData();
		return $this->data[$formKey];
	}

	/**
	 * Normalise all the data that has currently been loaded.  This is safe to call repeatedly and it will not perform
	 * any processing on already normalised data.
	 */
	protected function normaliseData() {
		foreach ($this->data as $formKey => $form) {
			if (!isset($this->data[$formKey]['_normalised'])) {
				// Normalise field definitions
				foreach ($form['fields'] as $fieldIndex => $field) {
					if (!isset($field['default'])) {
						$field['default'] = null;
					}
					$this->data[$formKey]['fields'][$fieldIndex] = $field;
				}
				// Normalise page and fieldset definitions and field specifications
				foreach ($form['pages'] as $pageIndex => $page) {
					foreach ($page['fieldsets'] as $fieldsetIndex => $fieldset) {
						foreach ($fieldset['fields'] as $fieldIndex => $field) {
							if (!is_array($field)) {
								$field = array( 'field' => $field );
							}
							if (!isset($field['mode'])) {
								$field['mode'] = 'field';
							}
							$fieldDefinition = $form['fields'][$field['field']];
							if (isset($fieldDefinition['validators'])) {
								// Allow validators to specify the label mask
								foreach ($fieldDefinition['validators'] as $validatorSpec) {
									if (!isset($field['label-mask'])) {
										$validatorDefinition = $this->config(sprintf('validators.%s', $validatorSpec['constraint']));
										if (isset($validatorDefinition['label-mask'])) {
											$field['label-mask'] = $validatorDefinition['label-mask'];
										}
									}
								}
							}
							$this->data[$formKey]['pages'][$pageIndex]['fieldsets'][$fieldsetIndex]['fields'][$fieldIndex] = $field;
						}
						if (!isset($fieldset['attributes'])) {
							$this->data[$formKey]['pages'][$pageIndex]['fieldsets'][$fieldsetIndex]['attributes'] = array();
						}
					}
				}
				$this->data[$formKey]['_normalised'] = true;
			}
		}
	}

	/**
	 * Retrieve the session key to store details about the given field in the given form.
	 *
	 * @param string $form
	 * @param string $field
	 * @param string $subKey
	 *
	 * @return string Session key
	 */
	protected function getSessionKey($form, $field, $subKey) {
		return sprintf('forms.%s.%s.%s', $form, $field, $subKey);
	}

	/**
	 * Get the validation constraints from the given fieldset collection.
	 *
	 * @param array $fieldsets
	 * @param array $fields
	 *
	 * @return \Symfony\Component\Validator\Constraints\Collection
	 */
	protected function getConstraints(array $fieldsets, array $fields) {
		$constraints = array();
		foreach ($fieldsets as $fieldset) {
			foreach ($fieldset['fields'] as $fieldSpec) {
				// Store the value in the session for the next page load
				$fieldDefinition = $fields[$fieldSpec['field']];
				if (isset($fieldDefinition['validators'])) {
					$fieldConstraints = array();
					//$fieldLabel = isset($fieldDefinition['label']) ? $fieldDefinition['label'] : $fieldDefinition['id'];
					foreach ($fieldDefinition['validators'] as $validator) {
						$constraintClass = $this->getConstraintClassName(
							$validator['constraint'],
							$this->config('constraints.class-map'),
							$this->config('constraints.namespaces')
						);
						$fieldConstraints[] = TypeUtilities::typeCheckedObject(
							$constraintClass,
							'constraint',
							'\\Symfony\\Component\\Validator\\Constraint'
						);
					}
					switch (sizeof($fieldConstraints)) {
						case 0:
							break;
						case 1:
							$constraints[$fieldSpec['field']] = $fieldConstraints[0];
							break;
						default:
							$constraints[$fieldSpec['field']] = $fieldConstraints;
					}
				}
			}
		}
		return new Collection(array(
			'fields' => $constraints,
			'allowExtraFields' => true,
			'allowMissingFields' => false
		));
	}

	/**
	 * Get the class name for the constraint validator implementation with the given name.
	 *
	 * @param string $name
	 *
	 * @return null|string
	 */
	protected function getConstraintClassName($name) {
		$name = NameUtilities::convertToStudlyCaps($name);
		$classMap = $this->config('constraints.class-map', array());
		if (isset($classMap[$name])) {
			return $classMap[$name];
		} else {
			foreach ($this->config('constraints.namespaces') as $namespace) {
				$prefix = $this->config('constraints.class-name-prefix');
				$suffix = $this->config('constraints.class-name-suffix');
				$className = sprintf('%s\\%s%s%s', $namespace, $prefix, $name, $suffix);
				if (class_exists($className)) {
					return $className;
				}
			}
		}
		return null;
	}

	/**
	 * Execute a single processor with the given specification.
	 *
	 * @param array $processor Processor specification.
	 * @param array $data Data for replacements.
	 *
	 * @throws \RuntimeException If any processor fails.
	 */
	protected function executeProcessor(array $processor, array $data) {
		// TODO Processor conditions
		// Determine the module and method to call.
		$moduleName = $processor['module'];
		$methodName = $processor['method'];
		$module = $this->getEngine()->getModule($moduleName);
		if (is_null($module)) {
			throw new \RuntimeException(sprintf('FormsModule encountered invalid processor with unknown module "%s", method "%s"', $moduleName, $methodName));
		}
		$method = new \ReflectionMethod($module, NameUtilities::convertToCamelCase($methodName));

		// Determine arguments to pass to the processor method.
		$arguments = $this->compileProcessorArguments($processor['arguments'], $data);

		// Run the processors.
		if ($method->invokeArgs($module, $arguments) === false) {
			// A processor failed, throw an exception.
			throw new \RuntimeException(sprintf('FormsModule ran processor module "%s", method "%s", which indicated failure', $moduleName, $methodName));
		}
	}

	protected function compileProcessorArguments(array $arguments, array $data) {
		$compiled = array();
		foreach ($arguments as $key => $value) {
			if (is_string($key)) {
				$key = $this->performReplacements($key, $data);
			}
			if (is_string($value)) {
				$value = $this->performReplacements($value, $data);
			} elseif (is_array($value)) {
				$value = $this->compileProcessorArguments($value, $data);
			}
			$compiled[$key] = $value;
		}
		return $compiled;
	}

	/**
	 * TODO Use config package instead of this, it does the same thing.
	 *
	 * @param $value
	 * @param $data
	 *
	 * @return mixed
	 * @throws \DomainException
	 */
	protected function performReplacements($value, $data) {
		return (trim($value) === '{{ data }}') ?
				$data :
				preg_replace_callback('/\\{\\{ ([\w\\-]+)\\:([\S]+?) \\}\\}/', function($matches) use ($data) {
					switch ($matches[1]) {
						case 'data':
							return isset($data[$matches[2]]) ? $data[$matches[2]] : '';
						case 'config':
							return $this->config($matches[2]);
						case 'engine-config':
							return $this->getEngine()->config($matches[2]);
						default:
							throw new \DomainException(sprintf('FormsModule encountered unknown replacement prefix "%s" in processor arguments', $matches[1]));
					}
				}, $value);
	}

}
