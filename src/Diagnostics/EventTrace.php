<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\Diagnostics;

class EventTrace
{

	public object $event;

	public string $name;

	public bool $handled = false;

	public float $duration = 0.0;

	public function __construct(object $event, ?string $eventName = null)
	{
		$this->event = $event;
		$this->name = $eventName ?? $event::class;
	}

}
