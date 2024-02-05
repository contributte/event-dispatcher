<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\Diagnostics;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TracyDispatcher implements EventDispatcherInterface
{

	private EventDispatcherInterface $original;

	/** @var EventTrace[] */
	private array $events = [];

	public function __construct(EventDispatcherInterface $original)
	{
		$this->original = $original;
	}

	/**
	 * @return EventTrace[]
	 */
	public function getEvents(): array
	{
		return $this->events;
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

		// Store trace
		$this->events[] = $trace;

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

		return $return;
	}

}
