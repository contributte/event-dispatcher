<?php

namespace Contributte\EventDispatcher\Events\Application;

use Contributte\EventDispatcher\Events\BaseEvent;
use Nette\Application\Application;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
final class StartupEvent extends BaseEvent
{

	/** @var Application */
	private $application;

	/**
	 * @param Application $application
	 */
	public function __construct(Application $application)
	{
		$this->application = $application;
	}

	/**
	 * @return Application
	 */
	public function getApplication()
	{
		return $this->application;
	}

}
