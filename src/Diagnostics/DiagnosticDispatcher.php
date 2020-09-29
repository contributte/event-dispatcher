<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\Diagnostics;

use Nette\Utils\ObjectHelpers;
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
	 * @param mixed[] $args
	 * @return mixed
	 */
	public function __call(string $name, array $args)
	{
		if (method_exists($this->original, $name)) {
			$callable = [$this->original, $name];
			assert(is_callable($callable));
			return call_user_func_array($callable, $args);
		}
		ObjectHelpers::strictCall(get_class($this->original), $name);
	}

	/**
	 * @inheritDoc
	 */
	public function addListener(string $eventName, $listener, $priority = 0): void
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
	public function removeListener(string $eventName, $listener): void
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
	public function dispatch(object $event, ?string $eventName = null): object
	{
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
	public function getListeners(?string $eventName = null)
	{
		return $this->original->getListeners(...func_get_args());
	}

	/**
	 * @inheritDoc
	 */
	public function getListenerPriority(string $eventName, $listener)
	{
		return $this->original->getListenerPriority(...func_get_args());
	}

	/**
	 * @inheritDoc
	 */
	public function hasListeners(?string $eventName = null)
	{
		return $this->original->hasListeners(...func_get_args());
	}

}
