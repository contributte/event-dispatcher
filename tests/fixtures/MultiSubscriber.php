<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Contributte\EventDispatcher\EventSubscriber;
use Symfony\Component\EventDispatcher\Event;

final class MultiSubscriber implements EventSubscriber
{

	/** @var Event[] */
	public $onCall = [];

	/**
	 * @return mixed[] The event names to listen to
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'multi.one' => 'onOne',
			'multi.two' => 'onTwo',
		];
	}

	public function onOne(Event $event): void
	{
		$this->onCall[] = $event;
	}

	public function onTwo(Event $event): void
	{
		$this->onCall[] = $event;
	}

}
