<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Psr\Log\AbstractLogger;
use Stringable;

final class DummyLogger extends AbstractLogger
{

	/**
	 * @param array<mixed> $context
	 */
	public function log(mixed $level, Stringable|string $message, array $context = []): void
	{
		// Nothing
	}

}
