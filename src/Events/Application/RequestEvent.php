<?php

namespace Contributte\EventDispatcher\Events\Application;

use Contributte\EventDispatcher\Events\BaseEvent;
use Nette\Application\Application;
use Nette\Application\Request;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
final class RequestEvent extends BaseEvent
{

	/** @var Application */
	private $application;

	/** @var Request */
	private $request;

	/**
	 * @param Application $application
	 * @param Request $request
	 */
	public function __construct(Application $application, Request $request)
	{
		$this->application = $application;
		$this->request = $request;
	}

	/**
	 * @return Application
	 */
	public function getApplication()
	{
		return $this->application;
	}

	/**
	 * @return Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

}
