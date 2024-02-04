<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher;

use Contributte\EventDispatcher\Exceptions\Logical\InvalidStateException;
use Nette\DI\Container;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LazyEventDispatcher extends EventDispatcher
{

	private Container $container;

	/** @var string[][] */
	private array $mapping = [];

	/** @var bool[] */
	private array $instanced = [];

	public function __construct(Container $container)
	{
		parent::__construct();

		$this->container = $container;
	}

	/**
	 * @return array<callable[]|callable>
	 */
	public function getListeners(?string $eventName = null): array
	{
		if (isset($this->mapping[$eventName])) {
			foreach ($this->mapping[$eventName] as $serviceName) {
				// If service is already instanced, them skip it.
				// It may produce multiple event registering.
				if (isset($this->instanced[$serviceName]))

				continue;

				// Obtain service from container
				$listener = $this->container->getService($serviceName);

				// Just for sure, validate type
				if ($listener instanceof EventSubscriberInterface) {
					$this->addSubscriber($listener);
				} else {
					throw new InvalidStateException('Unsupported type of subscriber');
				}

				// Mark services as instanced
				$this->instanced[$serviceName] = true;
			}

			// Unset already attached listeners
			unset($this->mapping[$eventName]);
		}

		return parent::getListeners($eventName);
	}

	public function addSubscriberLazy(string $eventName, string $serviceName): void
	{
		$this->mapping[$eventName][] = $serviceName;
	}

	public function hasListeners(?string $eventName = null): bool
	{
		// check if event name is specified and has some lazy subscriber
		if ($eventName !== null && isset($this->mapping[$eventName])) {
			return true;
		}

		// check if any lazy subscriber exists
		if ($eventName === null && count($this->mapping) > 0) {
			return true;
		}

		// defer to default implementation
		return parent::hasListeners($eventName);
	}

}
