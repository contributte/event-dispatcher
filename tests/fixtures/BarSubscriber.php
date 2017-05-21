<?php

namespace Tests\Fixtures;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
final class BarSubscriber implements EventSubscriberInterface
{

	/** @var array */
	public $onCall = [];

	/**
	 * @return array The event names to listen to
	 */
	public static function getSubscribedEvents()
	{
		return ['baz' => 'onBaz'];
	}

	/**
	 * @param Event $event
	 * @return void
	 */
	public function onBaz(Event $event)
	{
		$this->onCall[] = $event;
	}

}
