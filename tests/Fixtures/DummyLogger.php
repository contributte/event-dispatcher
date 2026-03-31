<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Psr\Log\AbstractLogger;
use Stringable;

final class DummyLogger extends AbstractLogger
{

	/** @var array<int, array{level: mixed, message: Stringable|string, context: array<mixed>}> */
	public static array $globalRecords = [];

	/** @var array<int, array{level: mixed, message: Stringable|string, context: array<mixed>}> */
	public array $records = [];

	public static function reset(): void
	{
		self::$globalRecords = [];
	}

	/**
	 * @param array<mixed> $context
	 */
	public function log(mixed $level, Stringable|string $message, array $context = []): void
	{
		$record = [
			'level' => $level,
			'message' => $message,
			'context' => $context,
		];

		$this->records[] = $record;
		self::$globalRecords[] = $record;
	}

}
