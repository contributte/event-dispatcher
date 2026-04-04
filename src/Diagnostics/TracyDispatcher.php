<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\Diagnostics;

class TracyDispatcher extends DebugDispatcher
{

	/** @var EventTrace[] */
	private array $events = [];

	/**
	 * @return EventTrace[]
	 */
	public function getEvents(): array
	{
		return $this->events;
	}

	protected function afterDispatch(EventTrace $trace): void
	{
		$this->events[] = $trace;
	}

}
