<?php
/*!
 * This file is a part of Sitegear.
 * Copyright (c) Ben New, Leftclick.com.au
 * See the LICENSE and README files in the main source directory for details.
 * http://sitegear.org/
 */

namespace Sitegear\Base\Form\Builder;

/**
 * Describes the behaviour of an object responsible for converting a particular representation of a form into a set of
 * objects.  The input representation of the form data could be anything, e.g. an array, object structure, or a
 * filename to load the data from.
 */
interface FormBuilderInterface {

	/**
	 * @param mixed $formData Representation of the form.
	 * @param callable $valueCallback
	 * @param callable $errorsCallback
	 * @param array $options Options for the builder implementation.
	 *
	 * @return \Sitegear\Base\Form\FormInterface
	 */
	public function buildForm($formData, callable $valueCallback, callable $errorsCallback, array $options);

}
