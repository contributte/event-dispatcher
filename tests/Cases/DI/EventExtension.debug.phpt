<?php declare(strict_types = 1);

use Contributte\EventDispatcher\DI\EventDispatcherExtension;
use Contributte\EventDispatcher\Diagnostics\TracyDispatcher;
use Contributte\EventDispatcher\Tracy\EventPanel;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Tester\Assert;
use Tests\Fixtures\FollowingSubscriber;
use Tests\Fixtures\StoppingSubscriber;
use Tracy\Bar;
use Tracy\Bridges\Nette\TracyExtension;

require_once __DIR__ . '/../../bootstrap.php';

// Add Tracy panel
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				events:
					debug:
						panel: true
			NEON
			));
		})->build();

	$container->initialize();

	/** @var Bar $bar */
	$bar = $container->getByType(Bar::class);

	Assert::notNull($bar->getPanel(EventPanel::class));
});

// Collect dispatched event traces and render listener details
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				services:
					foo: Tests\Fixtures\FooSubscriber
				events:
					debug:
						panel: true
			NEON
			));
		})->build();

	$container->initialize();

	/** @var EventDispatcherInterface $em */
	$em = $container->getByType(EventDispatcherInterface::class);

	/** @var TracyDispatcher $tracy */
	$tracy = $container->getService('events.dispatcher.tracy');

	$em->dispatch(new Event(), 'foobar');

	$panel = new EventPanel($tracy);

	Assert::count(1, $tracy->getEvents());

	$trace = $tracy->getEvents()[0];
	Assert::same('foobar', $trace->name);
	Assert::same(Event::class, $trace->eventClass);
	Assert::true($trace->handled);
	Assert::same(1, $trace->listenerCount);
	Assert::false($trace->propagationStopped);
	Assert::count(1, $trace->listeners);
	Assert::same('Tests\Fixtures\FooSubscriber::onFoobar', $trace->listeners[0]['listener']);
	Assert::same(0, $trace->listeners[0]['priority']);

	Assert::contains('Tests\Fixtures\FooSubscriber::onFoobar', $panel->getPanel());
	Assert::contains('1 listeners', $panel->getPanel());
});

// Capture propagation stop and skipped listeners
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				services:
					stopping: Tests\Fixtures\StoppingSubscriber
					following: Tests\Fixtures\FollowingSubscriber
				events:
					debug:
						panel: true
			NEON
			));
		})->build();

	$container->initialize();

	/** @var EventDispatcherInterface $em */
	$em = $container->getByType(EventDispatcherInterface::class);

	/** @var TracyDispatcher $tracy */
	$tracy = $container->getService('events.dispatcher.tracy');

	$event = new Event();
	$em->dispatch($event, 'stoppable');

	Assert::count(1, $tracy->getEvents());

	$trace = $tracy->getEvents()[0];
	Assert::true($trace->handled);
	Assert::true($trace->propagationStopped);
	Assert::same(2, $trace->listenerCount);
	Assert::count(2, $trace->listeners);
	Assert::same('Tests\Fixtures\StoppingSubscriber::onStoppable', $trace->listeners[0]['listener']);
	Assert::same(10, $trace->listeners[0]['priority']);
	Assert::same('Tests\Fixtures\FollowingSubscriber::onStoppable', $trace->listeners[1]['listener']);
	Assert::same(0, $trace->listeners[1]['priority']);

	/** @var StoppingSubscriber $stopping */
	$stopping = $container->getByType(StoppingSubscriber::class);

	/** @var FollowingSubscriber $following */
	$following = $container->getByType(FollowingSubscriber::class);

	Assert::same([$event], $stopping->onCall);
	Assert::same([], $following->onCall);
});

// Add Tracy panel with depth limit
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				events:
					debug:
						panel: true
						deep: 3
			NEON
			));
		})->build();

	$container->initialize();

	/** @var Bar $bar */
	$bar = $container->getByType(Bar::class);

	Assert::notNull($bar->getPanel(EventPanel::class));
});
