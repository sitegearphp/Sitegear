<?php
/*!
 * This file is a part of Sitegear.
 * Copyright (c) Ben New, Leftclick.com.au
 * See the LICENSE and README files in the main source directory for details.
 * http://sitegear.org/
 */

namespace Sitegear\Core\Module\Forms\Form\Builder;

use Sitegear\Base\Form\Form;
use Sitegear\Base\Form\FormInterface;
use Sitegear\Base\Form\Condition\ConditionInterface;
use Sitegear\Base\Form\Step;
use Sitegear\Base\Form\StepInterface;
use Sitegear\Base\Form\FieldReference;
use Sitegear\Base\Form\Fieldset;
use Sitegear\Base\Form\Processor\ModuleProcessor;
use Sitegear\Util\ArrayUtilities;
use Sitegear\Util\NameUtilities;
use Sitegear\Util\LoggerRegistry;

/**
 * Core FormBuilderInterface implementation.  Maps the format defined by the `FormsModule` data files into the Form
 * objects.
 */
class FormBuilder extends AbstractFormsModuleFormBuilder {

	//-- Constants --------------------

	/**
	 * Default class name for FieldInterface implementations.
	 */
	const CLASS_NAME_FORMAT_FIELD = '\\Sitegear\\Base\\Form\\Field\\%sField';

	/**
	 * Default class name for Constraint implementations.
	 */
	const CLASS_NAME_FORMAT_CONSTRAINT = '\\Symfony\\Component\\Validator\\Constraints\\%s';

	/**
	 * Default class name for Constraint implementations.
	 */
	const CLASS_NAME_FORMAT_CONDITION = '\\Sitegear\\Base\\Form\\Processor\\Condition\\%sCondition';

	//-- FormBuilderInterface Methods --------------------

	/**
	 * {@inheritDoc}
	 */
	public function buildForm($formDefinition) {
		LoggerRegistry::debug('FormBuilder::buildForm()');
		$form = new Form(
			$this->getSubmitUrl(isset($formDefinition['form-url']) ? $formDefinition['form-url'] : null),
			isset($formDefinition['target-url']) ? $formDefinition['target-url'] : null,
			isset($formDefinition['cancel-url']) ? $formDefinition['cancel-url'] : null,
			isset($formDefinition['method']) ? $formDefinition['method'] : null,
			isset($formDefinition['submit-button']) ? $formDefinition['submit-button'] : null,
			isset($formDefinition['reset-button']) ? $formDefinition['reset-button'] : null,
			isset($formDefinition['back-button']) ? $formDefinition['back-button'] : null
		);
		$constraintLabelMarkers = isset($formDefinition['constraint-label-markers']) ? $formDefinition['constraint-label-markers'] : array();
		foreach ($formDefinition['fields'] as $name => $fieldData) {
			$form->addField($this->buildField($name, $fieldData, $constraintLabelMarkers));
		}
		for ($i=0, $l=sizeof($formDefinition['steps']); $i<$l; ++$i) {
			$form->addStep($this->buildStep($form, $formDefinition, $i));
		}
		return $form;
	}

	//-- Public Methods --------------------

	/**
	 * Create a single field.
	 *
	 * @param string $name
	 * @param array $fieldDefinition
	 * @param string[] $constraintLabelMarkers
	 *
	 * @return \Sitegear\Base\Form\Field\FieldInterface
	 */
	public function buildField($name, array $fieldDefinition, array $constraintLabelMarkers=null) {
		LoggerRegistry::debug('FormBuilder::buildField()');
		$fieldType = $fieldDefinition['type'];
		$fieldTypeClass = new \ReflectionClass(
			isset($fieldDefinition['class']) ?
					$fieldDefinition['class'] :
					sprintf(self::CLASS_NAME_FORMAT_FIELD, NameUtilities::convertToStudlyCaps($fieldType))
		);
		$defaultValue = isset($fieldDefinition['default']) ? $fieldDefinition['default'] : null;
		$labelText = isset($fieldDefinition['label']) ? $fieldDefinition['label'] : '';
		$labelMarkers = array();
		$constraints = array();
		if (isset($fieldDefinition['constraints'])) {
			if (is_null($constraintLabelMarkers)) {
				$constraintLabelMarkers = array();
			}
			foreach ($fieldDefinition['constraints'] as $constraintData) {
				$constraints[] = $this->buildConstraint($constraintData);
				if (isset($constraintLabelMarkers[$constraintData['name']])) {
					if (is_array($constraintLabelMarkers[$constraintData['name']])) {
						$labelMarkers = array_merge($labelMarkers, $constraintLabelMarkers[$constraintData['name']]);
					} else {
						$labelMarkers[] = $constraintLabelMarkers[$constraintData['name']];
					}
				}
			}
		}
		if (isset($fieldDefinition['label-markers'])) {
			if (is_array($fieldDefinition['label-markers'])) {
				$labelMarkers = array_merge($labelMarkers, $fieldDefinition['label-markers']);
			} else {
				$labelMarkers[] = $fieldDefinition['label-markers'];
			}
		}
		$settings = isset($fieldDefinition['settings']) ? $fieldDefinition['settings'] : array();
		return $fieldTypeClass->newInstance($name, $this->getFieldValue($name, $defaultValue), $labelText, $labelMarkers, $constraints, $this->getFieldErrors($name), $settings);
	}

	/**
	 * Create a single constraint on a field.
	 *
	 * @param array $constraintDefinition
	 *
	 * @return \Symfony\Component\Validator\Constraint
	 */
	public function buildConstraint(array $constraintDefinition) {
		LoggerRegistry::debug('FormBuilder::buildConstraint()');
		$constraintClass = new \ReflectionClass(
			isset($constraintDefinition['class']) ?
					$constraintDefinition['class'] :
					sprintf(self::CLASS_NAME_FORMAT_CONSTRAINT, NameUtilities::convertToStudlyCaps($constraintDefinition['name']))
		);
		return $constraintClass->newInstance(isset($constraintDefinition['options']) ? $constraintDefinition['options'] : null);
	}

	/**
	 * Create a single step of the form.
	 *
	 * @param FormInterface $form
	 * @param array $formDefinition
	 * @param integer $stepIndex
	 *
	 * @return \Sitegear\Base\Form\StepInterface
	 */
	public function buildStep(FormInterface $form, array $formDefinition, $stepIndex) {
		LoggerRegistry::debug('FormBuilder::buildStep()');
		$stepData = $formDefinition['steps'][$stepIndex];
		$oneWay = isset($stepData['one-way']) ? $stepData['one-way'] : false;
		$heading = isset($stepData['heading']) ? $stepData['heading'] : null;
		$errorHeading = isset($stepData['error-heading']) ? $stepData['error-heading'] : null;
		$step = new Step($form, $stepIndex, $oneWay, $heading, $errorHeading);
		if (isset($stepData['fieldsets'])) {
			foreach ($stepData['fieldsets'] as $fieldsetData) {
				$step->addFieldset($this->buildFieldset($step, $fieldsetData));
			}
		}
		if (isset($stepData['processors'])) {
			foreach ($stepData['processors'] as $processorData) {
				$step->addProcessor($this->buildProcessor($processorData));
			}
		}
		return $step;
	}

	/**
	 * Create a single fieldset, which exists within a given step.
	 *
	 * @param \Sitegear\Base\Form\StepInterface $step
	 * @param array $fieldsetDefinition
	 *
	 * @return \Sitegear\Base\Form\Fieldset
	 */
	public function buildFieldset(StepInterface $step, array $fieldsetDefinition) {
		LoggerRegistry::debug('FormBuilder::buildFieldset()');
		$heading = isset($fieldsetDefinition['heading']) ? $fieldsetDefinition['heading'] : null;
		$fieldset = new Fieldset($step, $heading);
		foreach ($fieldsetDefinition['fields'] as $fieldData) {
			if (!is_array($fieldData)) {
				$fieldData = array( 'field' => $fieldData );
			}
			$fieldset->addFieldReference(new FieldReference(
				$fieldData['field'],
				isset($fieldData['read-only']) && $fieldData['read-only'],
				!isset($fieldData['wrapped']) || $fieldData['wrapped']
			));
		}
		return $fieldset;
	}

	/**
	 * Create a single processor for a step of the form.
	 *
	 * @param array $processorDefinition
	 *
	 * @return \Sitegear\Base\Form\Processor\FormProcessorInterface
	 */
	public function buildProcessor(array $processorDefinition) {
		LoggerRegistry::debug('FormBuilder::buildProcessor()');
		$processor = new ModuleProcessor(
			$this->getFormsModule()->getEngine()->getModule($processorDefinition['module']),
			$processorDefinition['method'],
			isset($processorDefinition['arguments']) ? $processorDefinition['arguments'] : array(),
			isset($processorDefinition['exception-field-names']) ? $processorDefinition['exception-field-names'] : null,
			isset($processorDefinition['exception-action']) ? $processorDefinition['exception-action'] : null
		);
		if (isset($processorDefinition['conditions'])) {
			foreach ($processorDefinition['conditions'] as $conditionData) {
				$processor->addCondition($this->buildCondition($conditionData));
			}
		}
		return $processor;
	}

	/**
	 * Create a single condition for a single processor.
	 *
	 * @param array $conditionDefinition
	 *
	 * @return ConditionInterface
	 */
	public function buildCondition(array $conditionDefinition) {
		$conditionClass = new \ReflectionClass(
			sprintf(
				self::CLASS_NAME_FORMAT_CONDITION,
				NameUtilities::convertToStudlyCaps($conditionDefinition['condition'])
			)
		);
		return $conditionClass->newInstance($conditionDefinition['field'], $conditionDefinition['values']);
	}

}
