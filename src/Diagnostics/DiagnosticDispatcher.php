<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\Diagnostics;

use Contributte\EventDispatcher\Tracy\Panel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tracy\Debugger;
use function spl_object_hash;

class DiagnosticDispatcher implements EventDispatcherInterface
{

	/** @var EventDispatcherInterface */
	private $original;

	/** @var Panel|null */
	private $panel = null;

	/** @var LoggerInterface|null */
	private $logger = null;

	public function __construct(EventDispatcherInterface $original)
	{
		$this->original = $original;
	}

	public function setPanel(Panel $panel): void
	{
		$this->panel = $panel;
		$panel->setDispatcher($this);
	}

	public function setLogger(LoggerInterface $logger): void
	{
		$this->logger = $logger;
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
		if ($this->panel !== null) {
			$this->panel->dispatch($info);
		}
		if ($this->logger !== null) {
			$this->logger->debug(sprintf('Dispatching event %s', $info->name), ['event' => $info]);
		}

		$timerName = 'event-' . spl_object_hash($event);
		Debugger::timer($timerName);
		$return = $this->original->dispatch($event, $eventName);
		if ($this->original->hasListeners($info->name)) {
			$info->handled = true;
		}
		$info->duration = Debugger::timer($timerName);

		if ($this->logger !== null) {
			$this->logger->debug(sprintf('Dispatched event %s', $info->name), ['event' => $info]);
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
