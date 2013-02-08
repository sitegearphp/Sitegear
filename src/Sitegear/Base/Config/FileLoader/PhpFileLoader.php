<?php
/*!
 * This file is a part of Sitegear.
 * Copyright (c) Ben New, Leftclick.com.au
 * See the LICENSE and README files in the main source directory for details.
 * http://sitegear.org/
 */

namespace Sitegear\Base\Config\FileLoader;

use Sitegear\Util\LoggerRegistry;

/**
 * LoaderInterface implementation which loads configuration data from PHP files.  The PHP files must return an
 * array, which may be a nested structure.  Normally the entire file looks like "<?php return array( ...data... );"
 *
 * The $args value in this implementation must be a string which is the absolute file path of the PHP file, or a
 * relative file on the include path.
 */
class PhpFileLoader implements FileLoaderInterface {

	/**
	 * {@inheritDoc}
	 */
	public function supports($args) {
		return file_exists($args) && pathinfo($args, PATHINFO_EXTENSION) === 'php';
	}

	/**
	 * {@inheritDoc}
	 */
	public function load($args) {
		LoggerRegistry::debug(sprintf('PhpFileLoader loading from "%s"', $args));
		if (!$this->supports($args)) {
			throw new \InvalidArgumentException(sprintf('PhpFileLoader attempting to load unsupported config file "%s".', $args));
		}
		/** @noinspection PhpIncludeInspection */
		return require $args;
	}

}
