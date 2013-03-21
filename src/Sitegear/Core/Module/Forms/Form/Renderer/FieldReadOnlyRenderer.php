<?php
/*!
 * This file is a part of Sitegear.
 * Copyright (c) Ben New, Leftclick.com.au
 * See the LICENSE and README files in the main source directory for details.
 * http://sitegear.org/
 */

namespace Sitegear\Core\Module\Forms\Form\Renderer;

use Sitegear\Core\Module\Forms\Form\Renderer\AbstractFieldRenderer;
use Sitegear\Util\HtmlUtilities;

/**
 * Renders a field in read-only mode.
 */
class FieldReadOnlyRenderer extends AbstractFieldRenderer {

	//-- RendererInterface Methods --------------------

	/**
	 * {@inheritDoc}
	 */
	public function render(array & $output, array $values, array $errors) {
		$name = $this->getField()->getName();
		$value = isset($values[$name]) ? $values[$name] : $this->getField()->getDefaultValue();
		if ($this->getField()->isArrayValue()) {
			$value = implode(', ', $value);
		}
		return sprintf(
			'<%s%s>%s</%s>',
			$this->getRenderOption(self::RENDER_OPTION_KEY_ELEMENT_NAME),
			HtmlUtilities::attributes($this->getRenderOption(self::RENDER_OPTION_KEY_ATTRIBUTES)),
			strlen($value) > 0 ? $value : '&nbsp;',
			$this->getRenderOption(self::RENDER_OPTION_KEY_ELEMENT_NAME)
		);
	}

}
