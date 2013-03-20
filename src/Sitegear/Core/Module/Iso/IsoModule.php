<?php
/*!
 * This file is a part of Sitegear.
 * Copyright (c) Ben New, Leftclick.com.au
 * See the LICENSE and README files in the main source directory for details.
 * http://sitegear.org/
 */

namespace Sitegear\Core\Module\Iso;

use Sitegear\Base\Module\AbstractConfigurableModule;

/**
 * Modular implementation of relevant ISO standards.
 */
class IsoModule extends AbstractConfigurableModule {

	//-- ModuleInterface Methods --------------------

	/**
	 * {@inheritDoc}
	 */
	public function getDisplayName() {
		return 'ISO Standards';
	}

	//-- Public Methods --------------------

	/**
	 * Retrieve a key-value array of country codes mapped to their names.
	 *
	 * @param array|null $includedCodes Indexed array of codes to include in the result.
	 *
	 * @return array Key-value array with code keys and label values, of ISO-3166 countries, possibly filtered to
	 *   only include the specified codes.
	 */
	public function getIso3166CountryCodes(array $includedCodes=null) {
		return array_intersect_key($this->config('iso-3166-country-codes'), array_fill_keys($includedCodes, true));
	}

	/**
	 * Retrieve an array structure suitable for use in form generation containing country codes and names.
	 *
	 * @param array|null $includedCodes Indexed array of codes to ignore.
	 *
	 * @return array Array of key-value arrays, each of which contains a 'value' and 'label' key, representing ISO-3166
	 *   countries, possibly filtered to only include the specified codes.
	 */
	public function getIso3166CountrySelectOptions(array $includedCodes=null) {
		$codes = $this->getIso3166CountryCodes($includedCodes);
		return array_map(function($code, $label) {
			return array(
				'value' => $code,
				'label' => $label
			);
		}, array_keys($codes), array_values($codes));
	}

}