<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\Diagnostics;

use Throwable;

class ListenerTrace
{

	public string $label;

	public int $priority;

	public bool $called = false;

	public bool $propagationStopped = false;

	public float $duration = 0.0;

	public ?Throwable $exception = null;

	public function __construct(string $label, int $priority)
	{
		$this->label = $label;
		$this->priority = $priority;
	}

}
