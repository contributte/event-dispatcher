<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\Diagnostics;

use Symfony\Contracts\EventDispatcher\Event;
use function get_class;

class EventInfo
{

	/** @var bool */
	public $handled = false;

	/** @var string */
	public $name;

	/** @var Event|object */
	public $event;

	/** @var float */
	public $duration = 0.0;

	public function __construct(object $event, ?string $eventName = null)
	{
		$this->event = $event;
		$this->name = $eventName ?? get_class($event);
	}

}
