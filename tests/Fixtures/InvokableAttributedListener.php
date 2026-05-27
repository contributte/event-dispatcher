<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final class InvokableAttributedListener
{

	/** @var TypedEvent[] */
	public array $onCall = [];

	public function __invoke(TypedEvent $event): void
	{
		$this->onCall[] = $event;
	}

}
