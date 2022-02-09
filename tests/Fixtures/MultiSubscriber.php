<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class MultiSubscriber implements EventSubscriberInterface
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
