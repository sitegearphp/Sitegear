<?php
/*!
 * This file is a part of Sitegear.
 * Copyright (c) Ben New, Leftclick.com.au
 * See the LICENSE and README files in the main source directory for details.
 * http://sitegear.org/
 */

/**
 * Default settings for Sitegear Customer module.
 */
return array(

	/**
	 * Settings that are shared by two or more components and/or pages.
	 */
	'common' => array(

		/**
		 * Text fragments.
		 */
		'text' => array(
			'no-items' => '<span class="sitegear-trolley-preview-no-items">Trolley is empty</span>',
			'items-count' => '<span class="sitegear-trolley-preview-items-count">Trolley contains %d items</span>',
			'details-link' => '<a href="%s" class="sitegear-trolley-preview-details-link">Details</a>',
			'checkout-link' => '<a href="%s" class="sitegear-trolley-preview-checkout-link">Checkout</a>'
		)
	),

	/**
	 * Settings for components.
	 */
	'components' => array(

		/**
		 * Settings for the trolley preview component.
		 */
		'trolley-preview' => array(

			/**
			 * Text used in the trolley preview component.
			 */
			'text' => array(
				'no-items' => '{{ config:common.text.no-items }}',
				'items-count' => '{{ config:common.text.items-count }}',
				'details-link' => '{{ config:common.text.details-link }}',
				'checkout-link' => '{{ config:common.text.checkout-link }}'
			),

			/**
			 * Links.
			 */
			'links' => array(
				/**
				 * Link wrapper element.
				 */
				'wrapper' => array(
					'element' => 'div',
					'attributes' => array(
						'class' => 'sitegear-trolley-preview-link-wrapper'
					)
				),

				/**
				 * Separator text (HTML) to insert between the two links.
				 */
				'separator' => '',

				/**
				 * Whether or not to display the "details" link (button) in the trolley preview.  Either boolean or
				 * 'not-empty' to display only when the trolley is not empty (the default).
				 */
				'details' => 'non-empty',

				/**
				 * Whether or not to display the "checkout" link (button) in the trolley preview.  Either boolean or
				 * 'not-empty' to display only when the trolley is not empty (the default).
				 */
				'checkout' => 'non-empty'
			)
		)
	),

	/**
	 * Settings for components.
	 */
	'pages' => array(

		/**
		 * Settings for the trolley preview component.
		 */
		'trolley' => array(

			/**
			 * Text used in the trolley preview component.
			 */
			'text' => array(
				'no-items' => '{{ config:common.text.no-items }}',
				'items-count' => '{{ config:common.text.items-count }}',
				'checkout-link' => '{{ config:common.text.checkout-link }}'
			)
		)
	)
);
