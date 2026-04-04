<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\EventDispatcher\Event;

#[AsEventListener(event: 'listener.class', method: 'onClass', priority: 3)]
final class ClassAttributedListener
{

	/** @var Event[] */
	public array $onCall = [];

	public function onClass(Event $event): void
	{
		$this->onCall[] = $event;
	}

}
