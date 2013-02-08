<?php
/*!
 * This file is a part of Sitegear.
 * Copyright (c) Ben New, Leftclick.com.au
 * See the LICENSE and README files in the main source directory for details.
 * http://sitegear.org/
 */

namespace Sitegear\Base\User\Acl;

class AllowNoneAccessController implements AccessControllerInterface {

	/**
	 * {@inheritDoc}
	 */
	public function checkPrivilege($id, $privilege) {
		return false;
	}

}
