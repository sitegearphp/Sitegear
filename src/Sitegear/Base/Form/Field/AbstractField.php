<?php
/*!
 * This file is a part of Sitegear.
 * Copyright (c) Ben New, Leftclick.com.au
 * See the LICENSE and README files in the main source directory for details.
 * http://sitegear.org/
 */

namespace Sitegear\Base\Form\Field;

use Sitegear\Base\Form\Constraint\ConditionalConstraintInterface;
use Symfony\Component\Validator\Constraint;
use Sitegear\Util\LoggerRegistry;

/**
 * Abstract base implementation of FieldInterface.
 */
abstract class AbstractField implements FieldInterface {

	//-- Attributes --------------------

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var mixed
	 */
	private $value;

	/**
	 * @var string
	 */
	private $labelText;

	/**
	 * @var string[]
	 */
	private $labelMarkers;

	/**
	 * @var ConditionalConstraintInterface[]
	 */
	private $conditionalConstraints;

	/**
	 * @var string[]
	 */
	private $errors;

	/**
	 * @var array
	 */
	private $settings;

	//-- Constructor --------------------

	/**
	 * @param string $name
	 * @param mixed $value
	 * @param string|null $labelText
	 * @param string[]|null $labelMarkers
	 * @param ConditionalConstraintInterface[]|null $constraints
	 * @param string[]|null $errors
	 * @param array $settings
	 */
	public function __construct($name, $value=null, $labelText=null, array $labelMarkers=null, array $constraints=null, array $errors=null, array $settings=null) {
		$this->name = $name;
		$this->value = $value;
		$this->labelText = $labelText;
		$this->labelMarkers = $labelMarkers ?: array();
		$this->conditionalConstraints = $constraints ?: array();
		$this->errors = $errors ?: array();
		$this->settings = $settings ?: array();
	}

	//-- FieldInterface Methods --------------------

	/**
	 * {@inheritDoc}
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setValue($value) {
		$this->value = $value;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLabelText() {
		return $this->labelText;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setLabelText($labelText) {
		$this->labelText = $labelText;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 *
	 * This implementation uses a default separator of a single whitespace character.
	 */
	public function getLabelMarkers($separator=null) {
		$separator = $separator ?: ' ';
		return $separator . implode($separator, $this->labelMarkers);
	}

	/**
	 * {@inheritDoc}
	 */
	public function addLabelMarker($labelMarker, $index=null) {
		if (is_null($index)) {
			$this->labelMarkers[] = $labelMarker;
		} else {
			$this->labelMarkers = array_splice($this->labelMarkers, intval($index), 0, $labelMarker);
		}
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function removeLabelMarker($labelMarker) {
		$index = is_integer($labelMarker) ? $labelMarker : array_search($labelMarker, $this->labelMarkers);
		$this->labelMarkers = array_splice($this->labelMarkers, $index, 1);
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getConditionalConstraints() {
		return $this->conditionalConstraints;
	}

	/**
	 * {@inheritDoc}
	 */
	public function addConditionalConstraint(ConditionalConstraintInterface $conditionalConstraint, $index=null) {
		if (is_null($index)) {
			$this->conditionalConstraints[] = $conditionalConstraint;
		} else {
			$this->conditionalConstraints = array_splice($this->conditionalConstraints, intval($index), 0, $conditionalConstraint);
		}
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function removeConditionalConstraint(ConditionalConstraintInterface $conditionalConstraint) {
		if (($index = array_search($conditionalConstraint, $this->conditionalConstraints)) !== false) {
			$this->conditionalConstraints = array_splice($this->conditionalConstraints, $index, 1);
		}
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setErrors(array $errors) {
		$this->errors = $errors;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getSetting($key, $default=null) {
		return isset($this->settings[$key]) ? $this->settings[$key] : $default;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setSetting($key, $value) {
		$this->settings[$key] = $value;
		return $this;
	}

}
