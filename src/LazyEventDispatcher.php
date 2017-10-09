<?php

namespace Contributte\EventDispatcher;

use Contributte\EventDispatcher\Exceptions\Logical\InvalidStateException;
use Nette\DI\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
class LazyEventDispatcher extends EventDispatcher
{

	/** @var Container */
	private $container;

	/** @var array */
	private $mapping = [];

	/** @var array */
	private $instanced = [];

	/**
	 * @param Container $container
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * @param string|NULL $eventName
	 * @return array
	 */
	public function getListeners($eventName = NULL)
	{
		if (isset($this->mapping[$eventName])) {
			foreach ($this->mapping[$eventName] as $serviceName) {
				// If service is already instanced, them skip it.
				// It may producer multiple event registering.
				if (isset($this->instanced[$serviceName])) continue;

				// Obtain service from container
				$listener = $this->container->getService($serviceName);

				// Just for sure, validate type
				if ($listener instanceof EventSubscriberInterface) {
					$this->addSubscriber($listener);
				} else {
					throw new InvalidStateException('Unsupported type of subscriber');
				}

				// Mark services as instanced
				$this->instanced[$serviceName] = TRUE;
			}

			// Unset already attached listeners
			unset($this->mapping[$eventName]);
		}

		return parent::getListeners($eventName);
	}


	/**
	 * @param string $eventName
	 * @param string $serviceName
	 * @return void
	 */
	public function addSubscriberLazy($eventName, $serviceName)
	{
		if (empty($this->mapping[$eventName])) {
			$this->mapping[$eventName] = [];
		}

		$this->mapping[$eventName][] = $serviceName;
	}

}
