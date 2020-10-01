<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\Diagnostics;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DiagnosticDispatcher implements EventDispatcherInterface
{

	public const DISPATCHING = 'Dispatching event';

	/** @var EventDispatcherInterface */
	private $original;

	/** @var LoggerInterface[] */
	private $loggers = [];

	public function __construct(EventDispatcherInterface $original)
	{
		$this->original = $original;
	}

	public function addLogger(LoggerInterface $logger): void
	{
		$this->loggers[] = $logger;
	}

	public function clearLoggers(): void
	{
		$this->loggers = [];
	}

	/**
	 * @inheritDoc
	 */
	public function addListener($eventName, $listener, $priority = 0): void
	{
		$this->original->addListener(...func_get_args());
	}

	/**
	 * @inheritDoc
	 */
	public function addSubscriber(EventSubscriberInterface $subscriber): void
	{
		$this->original->addSubscriber(...func_get_args());
	}

	/**
	 * @inheritDoc
	 */
	public function removeListener($eventName, $listener): void
	{
		$this->original->removeListener(...func_get_args());
	}

	public function removeSubscriber(EventSubscriberInterface $subscriber): void
	{
		$this->original->removeSubscriber(...func_get_args());
	}

	/**
	 * @inheritDoc
	 */
	public function dispatch($event/*, string $eventName = null*/)
	{
		$eventName = 1 < \func_num_args() ? func_get_arg(1) : null;

		$info = new EventInfo($event, $eventName);
		foreach ($this->loggers as $logger) {
			$logger->debug(sprintf(self::DISPATCHING . ' "%s"', $info->name), ['event' => $info]);
		}

		$start = microtime(true);
		$return = $this->original->dispatch($event, $eventName);
		if ($this->original->hasListeners($info->name)) {
			$info->handled = true;
		}
		$info->duration = microtime(true) - $start;

		foreach ($this->loggers as $logger) {
			$logger->debug(sprintf('Dispatched event %s', $info->name), ['event' => $info]);
		}

		return $return;
	}

	/**
	 * @inheritDoc
	 */
	public function getListeners($eventName = null)
	{
		return $this->original->getListeners(...func_get_args());
	}

	/**
	 * @inheritDoc
	 */
	public function getListenerPriority($eventName, $listener)
	{
		return $this->original->getListenerPriority(...func_get_args());
	}

	/**
	 * @inheritDoc
	 */
	public function hasListeners($eventName = null)
	{
		return $this->original->hasListeners(...func_get_args());
	}

}
