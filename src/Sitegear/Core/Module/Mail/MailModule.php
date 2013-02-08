<?php
/*!
 * This file is a part of Sitegear.
 * Copyright (c) Ben New, Leftclick.com.au
 * See the LICENSE and README files in the main source directory for details.
 * http://sitegear.org/
 */

namespace Sitegear\Core\Module\Mail;

use Sitegear\Base\Module\AbstractConfigurableModule;

class MailModule extends AbstractConfigurableModule {

	//-- ModuleInterface Methods --------------------

	/**
	 * {@inheritDoc}
	 */
	public function getDisplayName() {
		return 'Mail Processor';
	}

	//-- Public Methods --------------------

	public function send() {
		// TODO
		return true;
	}

}
