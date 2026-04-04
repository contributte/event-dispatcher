<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\EventDispatcher\Event;

#[AsEventListener(event: 'listener.inferred')]
final class InferredMethodAttributedListener
{

	/** @var Event[] */
	public array $onCall = [];

	public function onListenerInferred(Event $event): void
	{
		$this->onCall[] = $event;
	}

}
