<?php

namespace Tests\Fixtures;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Jiří Pudil <me@jiripudil.cz>
 */
final class PrioritizedSubscriber implements EventSubscriberInterface
{

	/** @var array */
	public $onCall = [];

	/**
	 * @return array The event names to listen to
	 */
	public static function getSubscribedEvents()
	{
		return ['prioritized' => ['onPrioritized', 1]];
	}

	/**
	 * @param Event $event
	 * @return void
	 */
	public function onPrioritized(Event $event)
	{
		$this->onCall[] = $event;
	}

}
