<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\DI\Utils;

final readonly class ListenerDefinition
{

	public function __construct(
		public string $serviceName,
		public string $eventName,
		public string $methodName,
		public int $priority,
	)
	{
	}

}
