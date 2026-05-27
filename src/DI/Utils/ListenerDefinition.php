<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\DI\Utils;

final class ListenerDefinition
{

	public function __construct(
		public readonly string $serviceName,
		public readonly string $eventName,
		public readonly string $methodName,
		public readonly int $priority,
	)
	{
	}

}
