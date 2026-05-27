<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\EventDispatcher\Event;

final class InvalidMethodAttributeListener
{

	#[AsEventListener(event: 'listener.invalid', method: 'onOther')]
	public function onInvalid(Event $event): void
	{
		// Used by compile-time validation tests.
	}

}
