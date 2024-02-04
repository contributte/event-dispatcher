<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class FooSubscriber implements EventSubscriberInterface
{

	/** @var Event[] */
	public array $onCall = [];

	/**
	 * @return mixed[] The event names to listen to
	 */
	public static function getSubscribedEvents(): array
	{
		return ['foobar' => 'onFoobar'];
	}

	public function onFoobar(Event $event): void
	{
		$this->onCall[] = $event;
	}

}
