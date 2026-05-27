<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class InvalidInferredEventListener
{

	#[AsEventListener]
	public function onInvalid(string $event): void
	{
		// Used by compile-time validation tests.
	}

}
