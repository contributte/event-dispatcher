<?php

namespace Tests\Fixtures;

use Contributte\EventDispatcher\EventSubscriber;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
final class MultiSubscriber implements EventSubscriber
{

	/** @var array */
	public $onCall = [];

	/**
	 * @return array The event names to listen to
	 */
	public static function getSubscribedEvents()
	{
		return [
			'multi.one' => 'onOne',
			'multi.two' => 'onTwo',
		];
	}

	/**
	 * @param Event $event
	 * @return void
	 */
	public function onOne(Event $event)
	{
		$this->onCall[] = $event;
	}

	/**
	 * @param Event $event
	 * @return void
	 */
	public function onTwo(Event $event)
	{
		$this->onCall[] = $event;
	}

}
