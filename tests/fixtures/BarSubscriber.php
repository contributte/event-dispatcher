<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class BarSubscriber implements EventSubscriberInterface
{

	/** @var Event[] */
	public $onCall = [];

	/**
	 * @return mixed[] The event names to listen to
	 */
	public static function getSubscribedEvents(): array
	{
		return ['baz' => 'onBaz'];
	}

	public function onBaz(Event $event): void
	{
		$this->onCall[] = $event;
	}

}
