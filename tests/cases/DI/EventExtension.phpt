<?php

/**
 * Test: DI\EventDispatcherExtensions
 */

use Contributte\EventDispatcher\DI\EventDispatcherExtension;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tester\Assert;
use Tester\FileMock;
use Tests\Fixtures\FooSubscriber;
use Tests\Fixtures\MultiSubscriber;

require_once __DIR__ . '/../../bootstrap.php';

// Dispatch event with NO defined subscriber
test(function () {
	$loader = new ContainerLoader(TEMP_DIR, TRUE);
	$class = $loader->load(function (Compiler $compiler) {
		$compiler->addExtension('events', new EventDispatcherExtension());
		$compiler->loadConfig(FileMock::create('
		services:
			foo: Tests\Fixtures\FooSubscriber
', 'neon'));
	}, 1);

	/** @var Container $container */
	$container = new $class;

	/** @var EventDispatcherInterface $em */
	$em = $container->getByType(EventDispatcherInterface::class);

	// Subscriber is not created
	Assert::false($container->isCreated('foo'));

	// Dispatch event
	$em->dispatch('baz.baz', new Event());

	// Subscriber is still not created
	Assert::false($container->isCreated('foo'));

	/** @var FooSubscriber $subscriber */
	$subscriber = $container->getByType(FooSubscriber::class);
	Assert::equal([], $subscriber->onCall);
});

// Dispatch event with defined subscriber
test(function () {
	$loader = new ContainerLoader(TEMP_DIR, TRUE);
	$class = $loader->load(function (Compiler $compiler) {
		$compiler->addExtension('events', new EventDispatcherExtension());
		$compiler->loadConfig(FileMock::create('
		services:
			foo: Tests\Fixtures\FooSubscriber
', 'neon'));
	}, 2);

	/** @var Container $container */
	$container = new $class;

	/** @var EventDispatcherInterface $em */
	$em = $container->getByType(EventDispatcherInterface::class);

	// Subscriber is not created
	Assert::false($container->isCreated('foo'));

	// Dispatch event
	$event = new Event();
	$em->dispatch('foobar', $event);

	// Subscriber is already created
	Assert::true($container->isCreated('foo'));

	/** @var FooSubscriber $subscriber */
	$subscriber = $container->getByType(FooSubscriber::class);
	Assert::equal([$event], $subscriber->onCall);
});

// Register multiple subscribers
test(function () {
	$loader = new ContainerLoader(TEMP_DIR, TRUE);
	$class = $loader->load(function (Compiler $compiler) {
		$compiler->addExtension('events', new EventDispatcherExtension());
		$compiler->loadConfig(FileMock::create('
		services:
			foo: Tests\Fixtures\FooSubscriber
			bar: Tests\Fixtures\BarSubscriber
', 'neon'));
	}, 3);

	/** @var Container $container */
	$container = new $class;

	Assert::count(2, $container->findByType(EventSubscriberInterface::class));
});

// Register subscriber with more events
test(function () {
	$loader = new ContainerLoader(TEMP_DIR, TRUE);
	$class = $loader->load(function (Compiler $compiler) {
		$compiler->addExtension('events', new EventDispatcherExtension());
		$compiler->loadConfig(FileMock::create('
		services:
			multi: Tests\Fixtures\MultiSubscriber
', 'neon'));
	}, 4);

	/** @var Container $container */
	$container = new $class;

	/** @var EventDispatcherInterface $em */
	$em = $container->getByType(EventDispatcherInterface::class);

	// Subscriber is not created
	Assert::false($container->isCreated('multi'));

	// Dispatch event
	$event1 = new Event();
	$em->dispatch('multi.one', $event1);

	$event2 = new Event();
	$em->dispatch('multi.two', $event2);

	// Subscriber is already created
	Assert::true($container->isCreated('multi'));

	/** @var MultiSubscriber $subscriber */
	$subscriber = $container->getByType(MultiSubscriber::class);
	Assert::equal([$event1, $event2], $subscriber->onCall);
});
