<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class ThrowingSubscriber implements EventSubscriberInterface
{

	/**
	 * @return mixed[] The event names to listen to
	 */
	public static function getSubscribedEvents(): array
	{
		return ['broken' => 'onBroken'];
	}

	public function onBroken(Event $event): void
	{
		throw new RuntimeException('Broken listener');
	}

}
