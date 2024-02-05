<?php declare(strict_types = 1);

use Contributte\EventDispatcher\DI\EventDispatcherExtension;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Tester\Assert;
use Tester\FileMock;
use Tests\Fixtures\FooSubscriber;
use Tests\Fixtures\MultiSubscriber;
use Tests\Fixtures\PrioritizedSubscriber;

require_once __DIR__ . '/../../bootstrap.php';

// Dispatch event with NO defined subscriber for event
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				services:
					foo: Tests\Fixtures\FooSubscriber
			NEON
			));
		})->build();

	/** @var EventDispatcherInterface $em */
	$em = $container->getByType(EventDispatcherInterface::class);

	// Subscriber is not created
	Assert::false($container->isCreated('foo'));

	// Dispatcher has some listeners
	Assert::true($em->hasListeners());

	// Dispatcher has no listeners for event
	Assert::false($em->hasListeners('baz.baz'));

	// Dispatch event
	$em->dispatch(new Event(), 'baz.baz');

	// Subscriber is still not created
	Assert::false($container->isCreated('foo'));

	/** @var FooSubscriber $subscriber */
	$subscriber = $container->getByType(FooSubscriber::class);
	Assert::equal([], $subscriber->onCall);
});

// Dispatch event with defined subscriber
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				services:
					foo: Tests\Fixtures\FooSubscriber
			NEON
			));
		})->build();

	/** @var EventDispatcherInterface $em */
	$em = $container->getByType(EventDispatcherInterface::class);

	// Subscriber is not created
	Assert::false($container->isCreated('foo'));

	// Dispatcher has some listeners
	Assert::true($em->hasListeners());

	// Dispatcher has listeners for foobar event
	Assert::true($em->hasListeners('foobar'));

	// Dispatch event
	$event = new Event();
	$em->dispatch($event, 'foobar');

	// Subscriber is already created
	Assert::true($container->isCreated('foo'));

	// Dispatcher has some listeners after instantiation
	Assert::true($em->hasListeners());

	// Dispatcher has listeners for foobar event after instantiation
	Assert::true($em->hasListeners('foobar'));

	/** @var FooSubscriber $subscriber */
	$subscriber = $container->getByType(FooSubscriber::class);
	Assert::equal([$event], $subscriber->onCall);
});

// Register multiple subscribers
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				services:
					foo: Tests\Fixtures\FooSubscriber
					bar: Tests\Fixtures\BarSubscriber
			NEON
			));
		})->build();

	Assert::count(2, $container->findByType(EventSubscriberInterface::class));
});

// Register subscriber with more events
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				services:
					multi: Tests\Fixtures\MultiSubscriber
			NEON
			));
		})->build();

	/** @var EventDispatcherInterface $em */
	$em = $container->getByType(EventDispatcherInterface::class);

	// Subscriber is not created
	Assert::false($container->isCreated('multi'));

	// Dispatch event
	$event1 = new Event();
	$em->dispatch($event1, 'multi.one');

	$event2 = new Event();
	$em->dispatch($event2, 'multi.two');

	// Subscriber is already created
	Assert::true($container->isCreated('multi'));

	/** @var MultiSubscriber $subscriber */
	$subscriber = $container->getByType(MultiSubscriber::class);
	Assert::equal([$event1, $event2], $subscriber->onCall);
});

// Register prioritized subscriber
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				services:
					prioritized: Tests\Fixtures\PrioritizedSubscriber
			NEON
			));
		})->build();

	/** @var EventDispatcherInterface $em */
	$em = $container->getByType(EventDispatcherInterface::class);

	// Subscriber is not created
	Assert::false($container->isCreated('prioritized'));

	// Dispatch event
	$event = new Event();
	$em->dispatch($event, 'prioritized');

	// Subscriber is already created
	Assert::true($container->isCreated('prioritized'));

	/** @var PrioritizedSubscriber $subscriber */
	$subscriber = $container->getByType(PrioritizedSubscriber::class);
	Assert::equal([$event], $subscriber->onCall);
});

// Dispatch event with NO subscribers at all
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->loadConfig(FileMock::create('', 'neon'));
		})->build();

	/** @var EventDispatcherInterface $em */
	$em = $container->getByType(EventDispatcherInterface::class);

	// Dispatcher has no listeners
	Assert::false($em->hasListeners());
});
