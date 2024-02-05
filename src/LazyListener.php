<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher;

use Nette\DI\Container;

class LazyListener
{

	private ?object $service = null;

	public function __construct(
		private readonly string $serviceName,
		private readonly string $methodName,
		private readonly Container $container
	)
	{
	}

	public function __invoke(): mixed
	{
		if ($this->service === null) {
			$this->service = $this->container->getService($this->serviceName);
		}

		return $this->service->{$this->methodName}(...func_get_args()); // @phpstan-ignore-line
	}

}
