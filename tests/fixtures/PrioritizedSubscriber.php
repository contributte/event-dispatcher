<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class PrioritizedSubscriber implements EventSubscriberInterface
{

	/** @var Event[] */
	public $onCall = [];

	/**
	 * @return mixed[] The event names to listen to
	 */
	public static function getSubscribedEvents(): array
	{
		return ['prioritized' => ['onPrioritized', 1]];
	}

	public function onPrioritized(Event $event): void
	{
		$this->onCall[] = $event;
	}

}
