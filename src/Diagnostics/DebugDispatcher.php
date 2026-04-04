<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\Diagnostics;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

class DebugDispatcher implements EventDispatcherInterface
{

	private EventDispatcherInterface $original;

	/** @var LoggerInterface[] */
	private array $loggers = [];

	private ?EventTrace $lastTrace = null;

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

	public function getLastTrace(): ?EventTrace
	{
		return $this->lastTrace;
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
	 * @template T of object
	 * @param T $event
	 * @return T
	 */
	public function dispatch(object $event, ?string $eventName = null): object
	{
		$trace = new EventTrace($event, $eventName);
		$trace->listeners = $this->getEventListeners($trace->name);
		$trace->listenerCount = count($trace->listeners);
		$this->lastTrace = $trace;

		foreach ($this->loggers as $logger) {
			$logger->debug(sprintf('EventDispatcher@%s: event started', $trace->name), ['event' => $trace]);
		}

		$start = microtime(true);

		try {
			return $this->original->dispatch($event, $eventName);
		} catch (Throwable $e) {
			$trace->exception = $e;

			throw $e;
		} finally {
			$trace->handled = $trace->listenerCount > 0;
			$trace->propagationStopped = $this->isPropagationStopped($event);
			$trace->duration = microtime(true) - $start;
			$this->afterDispatch($trace);

			$message = $trace->exception === null
				? sprintf('EventDispatcher@%s: event dispatched', $trace->name)
				: sprintf('EventDispatcher@%s: event failed', $trace->name);

			foreach ($this->loggers as $logger) {
				$logger->debug($message, ['event' => $trace]);
			}
		}
	}

	protected function afterDispatch(EventTrace $trace): void
	{
		// Intended for subclasses.
	}

	private function isPropagationStopped(object $event): bool
	{
		return is_callable([$event, 'isPropagationStopped']) && (bool) $event->isPropagationStopped();
	}

	/**
	 * @return array<int, array{listener: string, priority: int}>
	 */
	private function getEventListeners(string $eventName): array
	{
		$listeners = $this->original->getListeners($eventName);
		$normalized = [];

		foreach ($listeners as $listener) {
			if (is_callable($listener)) {
				$normalized[] = [
					'listener' => ListenerDescriber::describe($listener),
					'priority' => $this->original->getListenerPriority($eventName, $listener) ?? 0,
				];
			}
		}

		return $normalized;
	}

}
