<?php
/*!
 * This file is a part of Sitegear.
 * Copyright (c) Ben New, Leftclick.com.au
 * See the LICENSE and README files in the main source directory for details.
 * http://sitegear.org/
 */

namespace Sitegear\Ext\Module\RealCaptcha;

use Sitegear\Base\Module\AbstractUrlMountableModule;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Validator\ExecutionContextInterface;

use RealCaptcha\RealCaptcha;

/**
 * Provides modular integration with RealCaptcha human verification mechanism.  This is a simple wrapper, which allows
 * standard configuration options to be passed to the RealCaptcha constructor, and an accessor for the constructed
 * RealCaptcha object.
 */
class RealCaptchaModule extends AbstractUrlMountableModule {

	//-- Attributes --------------------

	/**
	 * @var RealCaptcha
	 */
	private $captcha;

	//-- ModuleInterface Methods --------------------

	/**
	 * {@inheritDoc}
	 */
	public function getDisplayName() {
		return 'RealCaptcha Integration';
	}

	/**
	 * {@inheritDoc}
	 */
	public function start() {
		$this->captcha = new RealCaptcha($this->config('real-captcha-options'));
	}

	//-- Controller Methods --------------------

	/**
	 * {@inheritDoc}
	 */
	protected function buildRoutes() {
		$routes = new RouteCollection();
		$routes->add('image', new Route(sprintf('%s/image', $this->getMountedUrl())));
		return $routes;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function buildNavigationData($mode) {
		return array();
	}

	//-- Controller Methods --------------------

	public function imageController() {
		ob_start();
		$this->getCaptcha()->writeImage();
		return new Response(ob_get_clean(), 200);
	}

	//-- Public Methods --------------------

	/**
	 * Retrieve the captcha constructed using the values set in the module's configuration.
	 *
	 * @return RealCaptcha
	 */
	public function getCaptcha() {
		return $this->captcha;
	}

	/**
	 * Validation constraint callback for checking that the captcha entered is correct.  This should be used by adding
	 * a constraint to the field with the following settings:
	 *
	 *     "callback": "module",
	 *     "module": "real-captcha",
	 *     "method": "validate-captcha"
	 *
	 * @param string $code
	 * @param ExecutionContextInterface $context
	 *
	 * @return boolean
	 */
	public function validateCaptcha($code, ExecutionContextInterface $context) {
		if (!$this->getCaptcha()->checkCode($code)) {
			$context->addViolation($this->config('validation.error-message'));
		}
	}

}
