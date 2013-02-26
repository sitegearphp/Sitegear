<?php
/*!
 * This file is a part of Sitegear.
 * Copyright (c) Ben New, Leftclick.com.au
 * See the LICENSE and README files in the main source directory for details.
 * http://sitegear.org/
 */

namespace Sitegear\Base\Form\Element;

use Sitegear\Base\Form\StepInterface;

class ButtonsElement extends AbstractContainerElement {

	//-- Constructor --------------------

	public function __construct(StepInterface $step, $elementName=null, array $defaultAttributes=null) {
		$elementName = $elementName ?: 'div';
		$defaultAttributes = $defaultAttributes ?: array(
			'class' => 'buttons'
		);
		parent::__construct($step, $elementName, $defaultAttributes, null);
	}

	//-- ElementInterface Methods --------------------

	/**
	 * {@inheritDoc}
	 */
	public function getChildren() {
		return array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function addChild(ElementInterface $child, $index = null) {
		throw new \LogicException(sprintf('%s does not support children', get_class($this)));
	}

	/**
	 * {@inheritDoc}
	 */
	public function removeChild($child) {
		throw new \LogicException(sprintf('%s does not support children', get_class($this)));
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAncestorFields() {
		return array();
	}

}