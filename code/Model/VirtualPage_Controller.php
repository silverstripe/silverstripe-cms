<?php
namespace SilverStripe\CMS\Model;

use Exception;
use Page_Controller;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Controllers\ModelAsController;

/**
 * Controller for the virtual page.
 * @package cms
 */
class VirtualPage_Controller extends Page_Controller
{

	private static $allowed_actions = array(
		'loadcontentall' => 'ADMIN',
	);

	/**
	 * Backup of virtualised controller
	 *
	 * @var ContentController
	 */
	protected $virtualController = null;

	/**
	 * Get virtual controller
	 *
	 * @return ContentController
	 */
	protected function getVirtualisedController()
	{
		if ($this->virtualController) {
			return $this->virtualController;
		}

		// Validate virtualised model
		/** @var VirtualPage $page */
		$page = $this->data();
		$virtualisedPage = $page->CopyContentFrom();
		if (!$virtualisedPage || !$virtualisedPage->exists()) {
			return null;
		}

		// Create controller using standard mechanism
		$this->virtualController = ModelAsController::controller_for($virtualisedPage);
		return $this->virtualController;
	}

	public function getViewer($action)
	{
		$controller = $this->getVirtualisedController() ?: $this;
		return $controller->getViewer($action);
	}

	/**
	 * When the virtualpage is loaded, check to see if the versions are the same
	 * if not, reload the content.
	 * NOTE: Virtual page must have a container object of subclass of sitetree.
	 * We can't load the content without an ID or record to copy it from.
	 */
	public function init()
	{
		parent::init();
		$this->__call('init', array());
	}

	/**
	 * Also check the original object's original controller for the method
	 *
	 * @param string $method
	 * @return bool
	 */
	public function hasMethod($method)
	{
		if (parent::hasMethod($method)) {
			return true;
		};

		// Fallback
		$controller = $this->getVirtualisedController();
		return $controller && $controller->hasMethod($method);
	}

	/**
	 * Pass unrecognized method calls on to the original controller
	 *
	 * @param string $method
	 * @param string $args
	 * @return mixed
	 *
	 * @throws Exception Any error other than a 'no method' error.
	 */
	public function __call($method, $args)
	{
		// Check if we can safely call this method before passing it back
		// to custom methods.
		if ($this->getExtraMethodConfig($method)) {
			return parent::__call($method, $args);
		}

		// Pass back to copied page
		$controller = $this->getVirtualisedController();
		if (!$controller) {
			return null;
		}

		// Ensure request/response data is available on virtual controller
		$controller->setRequest($this->getRequest());
		$controller->setResponse($this->getResponse());

		return call_user_func_array(array($controller, $method), $args);
	}
}
