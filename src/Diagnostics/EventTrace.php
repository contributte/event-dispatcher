<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\Diagnostics;

use Throwable;

class EventTrace
{

	public object $event;

	public string $name;

	public string $eventClass;

	public bool $handled = false;

	public bool $propagationStopped = false;

	public float $duration = 0.0;

	public int $listenerCount = 0;

	public int $calledCount = 0;

	public ?Throwable $exception = null;

	/** @var ListenerTrace[] */
	public array $listeners = [];

	public function __construct(object $event, ?string $eventName = null)
	{
		$this->event = $event;
		$this->eventClass = $event::class;
		$this->name = $eventName ?? $this->eventClass;
	}

}
