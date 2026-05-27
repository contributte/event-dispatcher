<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\EventDispatcher\Event;

final class MethodAttributedListener
{

	/** @var Event[] */
	public array $onMethodCall = [];

	/** @var TypedEvent[] */
	public array $onTypedCall = [];

	/** @var Event[] */
	public array $onRepeatedCall = [];

	#[AsEventListener(event: 'listener.method', priority: 5)]
	public function onMethod(Event $event): void
	{
		$this->onMethodCall[] = $event;
	}

	#[AsEventListener]
	public function onTyped(TypedEvent $event): void
	{
		$this->onTypedCall[] = $event;
	}

	#[AsEventListener(event: 'listener.repeat.one', priority: 10)]
	#[AsEventListener(event: 'listener.repeat.two')]
	public function onRepeated(Event $event): void
	{
		$this->onRepeatedCall[] = $event;
	}

}
