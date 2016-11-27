<?php

namespace Tests\Fixtures;

use Contributte\EventDispatcher\EventSubscriber;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
final class FooSubscriber implements EventSubscriber
{

	/** @var array */
	public $onCall = [];

	/**
	 * @return array The event names to listen to
	 */
	public static function getSubscribedEvents()
	{
		return ['foobar' => 'onFoobar'];
	}

	/**
	 * @param Event $event
	 * @return void
	 */
	public function onFoobar(Event $event)
	{
		$this->onCall[] = $event;
	}

}
