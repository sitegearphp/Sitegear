<?php
/*!
 * This file is a part of Sitegear.
 * Copyright (c) Ben New, Leftclick.com.au
 * See the LICENSE and README files in the main source directory for details.
 * http://sitegear.org/
 */

namespace Sitegear\Ext\Module\Customer;

use Sitegear\Base\Module\AbstractUrlMountableModule;
use Sitegear\Base\Module\PurchaseAdjustmentProviderModuleInterface;
use Sitegear\Base\Form\FieldReference;
use Sitegear\Base\Form\Form;
use Sitegear\Base\Form\Step;
use Sitegear\Base\Form\Fieldset;
use Sitegear\Base\Form\Field\SelectField;
use Sitegear\Base\Form\Field\InputField;
use Sitegear\Base\Module\PurchaseItemProviderModuleInterface;
use Sitegear\Base\View\ViewInterface;
use Sitegear\Ext\Module\Customer\Model\TransactionItem;
use Sitegear\Ext\Module\Customer\Model\Account;
use Sitegear\Util\TokenUtilities;
use Sitegear\Util\LoggerRegistry;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Provides customer management functionality.
 *
 * @method \Sitegear\Core\Engine\Engine getEngine()
 */
class CustomerModule extends AbstractUrlMountableModule {

	//-- Constants --------------------

	/**
	 * Alias to use for this module's entity namespace.
	 */
	const ENTITY_ALIAS = 'Customer';

	/**
	 * Session key to use for the trolley contents.
	 */
	const SESSION_KEY_TROLLEY = 'customer.trolley';

	//-- ModuleInterface Methods --------------------

	/**
	 * {@inheritDoc}
	 */
	public function getDisplayName() {
		return 'Customer Experience';
	}

	/**
	 * {@inheritDoc}
	 */
	public function start() {
		LoggerRegistry::debug('CustomerModule starting');
		$this->getEngine()->doctrine()->getEntityManager()->getConfiguration()->addEntityNamespace(self::ENTITY_ALIAS, '\\Sitegear\\Ext\\Module\\Customer\\Model');
	}

	//-- AbstractUrlMountableModule Methods --------------------

	/**
	 * {@inheritDoc}
	 */
	protected function buildRoutes() {
		$routes = new RouteCollection();
		$routes->add('index', new Route($this->getMountedUrl()));
		$routes->add('addTrolleyItem', new Route(sprintf('%s/%s', $this->getMountedUrl(), $this->config('routes.add-trolley-item'))));
		$routes->add('removeTrolleyItem', new Route(sprintf('%s/%s', $this->getMountedUrl(), $this->config('routes.remove-trolley-item'))));
		$routes->add('modifyTrolleyItem', new Route(sprintf('%s/%s', $this->getMountedUrl(), $this->config('routes.modify-trolley-item'))));
		$routes->add('trolley', new Route(sprintf('%s/%s', $this->getMountedUrl(), $this->config('routes.trolley'))));
		$routes->add('checkout', new Route(sprintf('%s/%s', $this->getMountedUrl(), $this->config('routes.checkout'))));
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
	 * Show the customer profile page.
	 */
	public function indexController(ViewInterface $view, Request $request) {
		LoggerRegistry::debug('CustomerModule::indexController');
		$this->applyConfigToView('pages.index', $view);
		if (!$this->getEngine()->getUserManager()->isLoggedIn()) {
			return new RedirectResponse($this->getEngine()->userIntegration()->getAuthenticationLinkUrl('login', $request->getUri()));
		}
		$email = $this->getEngine()->getUserManager()->getLoggedInUserEmail();
		$account = $this->getRepository('Account')->findOneBy(array( 'email' => $email ));
		if (is_null($account)) {
			$account = new Account();
			$account->setEmail($email);
			$this->getEngine()->doctrine()->getEntityManager()->persist($account);
		}
		$view['account'] = $account;
		$view['fields'] = $this->getRepository('Field')->findAll();
		return null;
	}

	/**
	 * Handle the "add trolley item" action for any purchasable item.  This is the target of the "add trolley item" form.
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function addTrolleyItemController(Request $request) {
		LoggerRegistry::debug('CustomerModule::addTrolleyItemController');
		// Extract request details.
		$moduleName = $request->request->get('module');
		$type = $request->request->get('type');
		$id = $request->request->get('id');
		// Setup the generated form.
		$formKey = $this->config('forms.add-trolley-item.form-key');
		$form = $this->buildAddTrolleyItemForm($moduleName, $type, $id);
		$this->getEngine()->forms()->registerForm($formKey, $form);
		// Validate the data against the generated form, and add the trolley item if valid.
		if ($valid = $this->getEngine()->forms()->validateForm($formKey, $form->getStep(0)->getReferencedFields(), $request->request->all())) {
			$attributeValues = array();
			foreach ($request->request->all() as $key => $value) {
				if (strstr($key, 'attr_') !== false) {
					$attributeValues[substr($key, 5)] = $value;
				}
			}
			$this->addTrolleyItem($moduleName, $type, $id, $attributeValues, intval($request->request->get('quantity')));
		}
		// Go back to the page where the submission was made.
		return new RedirectResponse($request->getUriForPath(
			$valid ?
				sprintf('/%s/%s', $this->getMountedUrl(), $this->config('routes.trolley')) :
				sprintf('/%s', $request->request->get('form-url'))
		));
	}

	/**
	 * Handle the "remove" button action from the trolley details page.
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function removeTrolleyItemController(Request $request) {
		LoggerRegistry::debug('CustomerModule::removeTrolleyItemController');
		// Remove the item from the stored trolley data.
		$this->removeTrolleyItem(intval($request->request->get('index')));
		// Go back to the page where the submission was made.
		return new RedirectResponse($request->getUriForPath(sprintf('/%s/%s', $this->getMountedUrl(), $this->config('routes.trolley'))));
	}

	/**
	 * Handle the quantity update from the trolley details page.
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function modifyTrolleyItemController(Request $request) {
		LoggerRegistry::debug('CustomerModule::modifyTrolleyItemController');
		// Update the stored trolley data.
		$this->modifyTrolleyItem(intval($request->request->get('index')), intval($request->request->get('quantity')));
		// Go back to the page where the submission was made.
		return new RedirectResponse($request->getUriForPath(sprintf('/%s/%s', $this->getMountedUrl(), $this->config('routes.trolley'))));
	}

	/**
	 * Display the trolley details page.
	 *
	 * @param \Sitegear\Base\View\ViewInterface $view
	 *
	 * @throws \RuntimeException
	 */
	public function trolleyController(ViewInterface $view) {
		LoggerRegistry::debug('CustomerModule::trolleyController');
		$this->applyConfigToView('pages.trolley', $view);
		$view['modify-item-url'] = sprintf('%s/%s', $this->getMountedUrl(), $this->config('routes.modify-trolley-item'));
		$view['remove-item-url'] = sprintf('%s/%s', $this->getMountedUrl(), $this->config('routes.remove-trolley-item'));
		$view['form-url'] = sprintf('%s/%s', $this->getMountedUrl(), $this->config('routes.trolley'));
		$view['checkout-url'] = sprintf('%s/%s', $this->getMountedUrl(), $this->config('routes.checkout'));
		$view['trolley-data'] = $this->getTrolleyData();
		$view['adjustments'] = $this->getAdjustments();
	}

	/**
	 * Display the checkout page.
	 *
	 * @param \Sitegear\Base\View\ViewInterface $view
	 */
	public function checkoutController(ViewInterface $view) {
		LoggerRegistry::debug('CustomerModule::checkoutController');
		$this->applyConfigToView('pages.checkout', $view);
		// TODO Checkout page
	}

	//-- Component Controller Methods --------------------

	/**
	 * Display the trolley preview, which is usually found in the site header.
	 *
	 * @param \Sitegear\Base\View\ViewInterface $view
	 */
	public function trolleyPreviewComponent(ViewInterface $view) {
		LoggerRegistry::debug('CustomerModule::trolleyPreviewComponent');
		$this->applyConfigToView('components.trolley-preview', $view);
		$view['trolley-data'] = $this->getTrolleyData();
		$view['details-url'] = sprintf('%s/%s', $this->getMountedUrl(), $this->config('routes.trolley'));
		$view['checkout-url'] = sprintf('%s/%s', $this->getMountedUrl(), $this->config('routes.checkout'));
	}

	/**
	 * Display the "add trolley item" form.
	 *
	 * @param \Sitegear\Base\View\ViewInterface $view
	 * @param $moduleName
	 * @param $type
	 * @param $id
	 */
	public function addTrolleyItemFormComponent(ViewInterface $view, $moduleName, $type, $id) {
		LoggerRegistry::debug('CustomerModule::addTrolleyItemFormComponent');
		// Setup the generated form.
		$formKey = $view['form-key'] = $this->config('forms.add-trolley-item.form-key');
		$this->getEngine()->forms()->registerForm($formKey, $this->buildAddTrolleyItemForm($moduleName, $type, $id));
	}

	//-- Public Methods --------------------

	/**
	 * Add a single item (of any quantity) to the trolley.
	 *
	 * @param string $moduleName Name of the module that provides the item being added.
	 * @param string $type Name of the item type being added.
	 * @param int $itemId Unique identifier of the item being added.
	 * @param array $attributeValues Attribute selections, a key-value array where the keys are attribute identifiers and
	 *   the values are value identifiers.
	 * @param int $quantity Quantity being added, 1 by default.
	 *
	 * @throws \InvalidArgumentException
	 * @throws \DomainException
	 */
	public function addTrolleyItem($moduleName, $type, $itemId, array $attributeValues=null, $quantity=null) {
		if ($quantity < 1) {
			throw new \DomainException('CustomerModule cannot modify trolley item to a zero or negative quantity; use removeTrolleyItem instead.');
		}
		$module = $this->getPurchaseItemProviderModule($moduleName);
		$attributeDefinitions = $module->getPurchaseItemAttributeDefinitions($type, $itemId);
		$attributes = array();

		// Get an array of attributes, which each have a value and a label.
		foreach ($attributeValues as $attributeValue) {
			$attributeValue = intval($attributeValue);
			foreach ($attributeDefinitions as $attributeDefinition) {
				foreach ($attributeDefinition['values'] as $value) {
					if ($value['id'] === $attributeValue) {
						$attributes[] = array(
							'value' => $attributeValue,
							'label' => sprintf('%s: %s', $attributeDefinition['label'], $value['label'])
						);
					}
				}
			}
		}

		// Add the item data to the trolley, or merge it in to an existing matching item.
		$data = $this->getTrolleyData();
		$matched = false;
		foreach ($data as $index => $item) { /** @var TransactionItem $item */
			if (($item->getModule() === $moduleName) && ($item->getType() === $type) && ($item->getAttributes() === $attributes)) {
				$matched = $index;
			}
		}
		if ($matched !== false) {
			$item = $data[$matched];
			$item->setQuantity($item->getQuantity() + $quantity);
			$data[$matched] = $item;
		} else {
			$item = new TransactionItem();
			$item->setModule($moduleName);
			$item->setType($type);
			$item->setItemId($itemId);
			$item->setLabel($module->getPurchaseItemLabel($type, $itemId));
			$item->setDetailsUrl($module->getPurchaseItemDetailsUrl($type, $itemId, $attributeValues));
			$item->setAttributes($attributes);
			$item->setUnitPrice($module->getPurchaseItemUnitPrice($type, $itemId, $attributeValues));
			$item->setQuantity($quantity);
			$data[] = $item;
		}
		$this->setTrolleyData($data);
	}

	/**
	 * Remove the trolley item at the given index.
	 *
	 * @param $index
	 *
	 * @throws \OutOfBoundsException
	 */
	public function removeTrolleyItem($index) {
		$data = $this->getTrolleyData();
		if ($index < 0 || $index >= sizeof($data)) {
			throw new \OutOfBoundsException(sprintf('CustomerModule cannot modify trolley item with index (%d) out-of-bounds', $index));
		}
		array_splice($data, $index, 1);
		$this->setTrolleyData($data);
	}

	/**
	 * Set the quantity of the trolley item at the given index.  The quantity must be greater than zero.
	 *
	 * @param $index
	 * @param $quantity
	 *
	 * @throws \DomainException
	 * @throws \OutOfBoundsException
	 */
	public function modifyTrolleyItem($index, $quantity) {
		if ($quantity < 1) {
			throw new \DomainException('CustomerModule cannot modify trolley item to a zero or negative quantity; use removeTrolleyItem instead.');
		}
		$data = $this->getTrolleyData();
		if ($index < 0 || $index >= sizeof($data)) {
			throw new \OutOfBoundsException(sprintf('CustomerModule cannot modify trolley item with index (%d) out-of-bounds', $index));
		}
		$item = $data[$index]; /** @var TransactionItem $item */
		$item->setQuantity($quantity);
		$data[$index] = $item;
		$this->setTrolleyData($data);
	}

	//-- Internal Methods --------------------

	/**
	 * Retrieve a named module from the engine and check that it is an instance of PurchaseItemProviderModuleInterface.
	 * Essentially this is a shortcut to getEngine()->getModule() with an additional type check.
	 *
	 * @param $name
	 *
	 * @return \Sitegear\Base\Module\PurchaseItemProviderModuleInterface
	 * @throws \InvalidArgumentException
	 */
	protected function getPurchaseItemProviderModule($name) {
		$module = $this->getEngine()->getModule($name);
		if (!$module instanceof PurchaseItemProviderModuleInterface) {
			throw new \InvalidArgumentException(sprintf('The specified module "%s" is not a valid purchase item provider.', $name));
		}
		return $module;
	}

	/**
	 * Get the current contents of the trolley.
	 *
	 * @return TransactionItem[]
	 */
	protected function getTrolleyData() {
		return $this->getEngine()->getSession()->get(self::SESSION_KEY_TROLLEY, array());
	}

	/**
	 * Set the contents of the trolley.
	 *
	 * @param TransactionItem[] $data
	 */
	protected function setTrolleyData(array $data) {
		$this->getEngine()->getSession()->set(self::SESSION_KEY_TROLLEY, $data);
	}

	/**
	 * Dynamically generate the "add trolley item" form configuration.
	 *
	 * @param $moduleName
	 * @param $type
	 * @param $id
	 *
	 * @return \Sitegear\Base\Form\FormInterface
	 */
	private function buildAddTrolleyItemForm($moduleName, $type, $id) {
		$submitUrl = sprintf('%s/%s', $this->getMountedUrl(), $this->config('routes.add-trolley-item'));
		$form = new Form($submitUrl);
		// Add the hidden fields.
		$moduleField = new InputField('module', $moduleName);
		$moduleField->setSetting('type', 'hidden');
		$form->addField($moduleField);
		$typeField = new InputField('type', $type);
		$typeField->setSetting('type', 'hidden');
		$form->addField($typeField);
		$idField = new InputField('id', $id);
		$idField->setSetting('type', 'hidden');
		$form->addField($idField);
		// Create the array of field names for references used by the single step of the form.
		$fields = array( 'module', 'type', 'id' );
		// Add a field to the form for every purchase item attribute.
		foreach ($this->getPurchaseItemProviderModule($moduleName)->getPurchaseItemAttributeDefinitions($type, $id) as $attribute) {
			$name = sprintf('attr_%s', $attribute['id']);
			// TODO Other field types - MultiInputField with radios and checkboxes
			$attributeField = new SelectField($name, null, $attribute['label']);
			$attributeField->addConstraint(new NotBlank());
			$attributeField->setSetting('values', $this->buildAddTrolleyItemFormAttributeFieldValues($attribute));
			$form->addField($attributeField);
			$fields[] = $name;
		}
		// Add the quantity field, which is a standard text field with a label.
		$quantityField = new InputField('quantity', 1, $this->config('forms.add-trolley-item.quantity-label'));
		$quantityField->addConstraint(new NotBlank());
		$quantityField->addConstraint(new Range(array( 'min' => 1 )));
		$form->addField($quantityField);
		$fields[] = 'quantity';
		// Complete the form structure.
		$step = new Step($form, 0);
		$fieldset = new Fieldset($step);
		foreach ($fields as $field) {
			$fieldset->addFieldReference(new FieldReference($field, false, true));
		}
		$form->addStep($step->addFieldset($fieldset));
		return $form;
	}

	/**
	 * Create the values array for the given attribute.
	 *
	 * @param array $attribute
	 *
	 * @return array
	 */
	private function buildAddTrolleyItemFormAttributeFieldValues(array $attribute) {
		$values = array();
		// Add the 'no value' value.
		$noValueLabel = $this->config('forms.add-trolley-item.no-value-label');
		if (!is_null($noValueLabel)) {
			$values[] = array(
				'value' => '',
				'label' => $noValueLabel
			);
		}
		// Add the other values.
		$labelFormat = $this->config('forms.add-trolley-item.value-format');
		foreach ($attribute['values'] as $value) {
			$label = TokenUtilities::replaceTokens(
				$labelFormat,
				array(
					'label' => $value['label'],
					'value' => sprintf('$%s', number_format($value['value'] / 100, 2))
				)
			);
			$values[] = array(
				'value' => $value['id'],
				'label' => $label
			);
		}
		return $values;
	}

	/**
	 * Get an array of key-value arrays specifying the label and value for each configured adjustment.
	 *
	 * @return array[]
	 *
	 * @throws \RuntimeException
	 */
	private function getAdjustments() {
		$adjustments = array();
		foreach ($this->config('checkout.adjustments') as $name) {
			$module = $this->getEngine()->getModule($name);
			if (is_null($module)) {
				throw new \RuntimeException(sprintf('FormsModule found invalid entry in "checkout.adjustments"; module "%s" does not exist', $name));
			} elseif (!$module instanceof PurchaseAdjustmentProviderModuleInterface) {
				throw new \RuntimeException(sprintf('FormsModule found invalid entry in "checkout.adjustments"; must be a purchase adjustment provider module, found "%s"', $name));
			}
			$value = $module->getAdjustmentAmount($this->getTrolleyData(), array()); // TODO Pass in $data array
			if (!empty($value) || $module->isVisibleUnset()) {
				$adjustments[] = array(
					'label' => $module->getAdjustmentLabel(),
					'value' => $value
				);
			}
		}
		return $adjustments;
	}

	/**
	 * @param string $entity
	 *
	 * @return \Doctrine\ORM\EntityRepository
	 */
	private function getRepository($entity) {
		return $this->getEngine()->doctrine()->getEntityManager()->getRepository(sprintf('%s:%s', self::ENTITY_ALIAS, $entity));
	}

}
