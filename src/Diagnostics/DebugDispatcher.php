<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\Diagnostics;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DebugDispatcher implements EventDispatcherInterface
{

	private EventDispatcherInterface $original;

	/** @var LoggerInterface[] */
	private array $loggers = [];

	public function __construct(EventDispatcherInterface $original)
	{
		$this->original = $original;
	}

	public function addLogger(LoggerInterface $logger): void
	{
		$this->loggers[] = $logger;
	}

	/**
	 * @param LoggerInterface[] $loggers
	 */
	public function setLoggers(array $loggers = []): void
	{
		$this->loggers = $loggers;
	}

	/**
	 * @return LoggerInterface[]
	 */
	public function getLoggers(): array
	{
		return $this->loggers;
	}

	/**
	 * {@inheritdoc}
	 */
	public function addListener(string $eventName, callable $listener, int $priority = 0): void
	{
		$this->original->addListener($eventName, $listener, $priority);
	}

	/**
	 * {@inheritdoc}
	 */
	public function addSubscriber(EventSubscriberInterface $subscriber): void
	{
		$this->original->addSubscriber($subscriber);
	}

	/**
	 * {@inheritdoc}
	 */
	public function removeListener(string $eventName, callable $listener): void
	{
		$this->original->removeListener($eventName, $listener);
	}

	/**
	 * {@inheritdoc}
	 */
	public function removeSubscriber(EventSubscriberInterface $subscriber): void
	{
		$this->original->removeSubscriber($subscriber);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getListeners(?string $eventName = null): array
	{
		return $this->original->getListeners($eventName);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getListenerPriority(string $eventName, callable $listener): ?int
	{
		return $this->original->getListenerPriority($eventName, $listener);
	}

	/**
	 * {@inheritdoc}
	 */
	public function hasListeners(?string $eventName = null): bool
	{
		return $this->original->hasListeners($eventName);
	}

	/**
	 * {@inheritdoc}
	 */
	public function dispatch(object $event, ?string $eventName = null): object
	{
		$trace = new EventTrace($event, $eventName);

		// Iterate over all loggers
		foreach ($this->loggers as $logger) {
			$logger->debug(sprintf('EventDispatcher@%s: event started', $trace->name), ['event' => $trace]);
		}

		// Start timer
		$start = microtime(true);

		// Dispatch event
		$return = $this->original->dispatch($event, $eventName);

		// If event was handled, mark it
		if ($this->original->hasListeners($trace->name)) {
			$trace->handled = true;
		}

		// Calculate duration
		$trace->duration = microtime(true) - $start;

		foreach ($this->loggers as $logger) {
			$logger->debug(sprintf('EventDispatcher@%s: event dispatched', $trace->name), ['event' => $trace]);
		}

		return $return;
	}

}
