<?php declare(strict_types = 1);

use Contributte\EventDispatcher\DI\EventDispatcherExtension;
use Contributte\EventDispatcher\Diagnostics\DebugDispatcher;
use Contributte\EventDispatcher\Diagnostics\EventTrace;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Tester\Assert;
use Tests\Fixtures\DummyLogger;

require_once __DIR__ . '/../../bootstrap.php';

// Inject configured loggers into debug dispatcher
Toolkit::test(function (): void {
	DummyLogger::reset();

	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				services:
					foo: Tests\Fixtures\FooSubscriber
				events:
					loggers:
						- Tests\Fixtures\DummyLogger()
			NEON
			));
		})->build();

	/** @var EventDispatcherInterface $em */
	$em = $container->getByType(EventDispatcherInterface::class);

	/** @var DebugDispatcher $dd */
	$dd = $container->getService('events.dispatcher.logging');

	Assert::count(1, $dd->getLoggers());

	$em->dispatch(new Event(), 'foobar');

	Assert::count(2, DummyLogger::$globalRecords);
	Assert::same('EventDispatcher@foobar: event started', (string) DummyLogger::$globalRecords[0]['message']);
	Assert::same('EventDispatcher@foobar: event dispatched', (string) DummyLogger::$globalRecords[1]['message']);

	$trace = DummyLogger::$globalRecords[1]['context']['event'];
	Assert::type(EventTrace::class, $trace);
	Assert::true($trace->handled);
	Assert::same(1, $trace->listenerCount);
	Assert::same(1, $trace->calledCount);
	Assert::false($trace->propagationStopped);
	Assert::count(1, $trace->listeners);
	Assert::same('Tests\Fixtures\FooSubscriber::onFoobar', $trace->listeners[0]->label);
	Assert::null($trace->exception);
	Assert::notNull($dd->getLastTrace());
	Assert::notNull($dd->getLoggers());
});

// Log failed dispatches with trace details
Toolkit::test(function (): void {
	DummyLogger::reset();

	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				services:
					throwing: Tests\Fixtures\ThrowingSubscriber
				events:
					loggers:
						- Tests\Fixtures\DummyLogger()
			NEON
			));
		})->build();

	/** @var EventDispatcherInterface $em */
	$em = $container->getByType(EventDispatcherInterface::class);

	Assert::exception(
		fn (): object => $em->dispatch(new Event(), 'broken'),
		RuntimeException::class,
		'Broken listener',
	);

	Assert::count(2, DummyLogger::$globalRecords);
	Assert::same('EventDispatcher@broken: event started', (string) DummyLogger::$globalRecords[0]['message']);
	Assert::same('EventDispatcher@broken: event failed', (string) DummyLogger::$globalRecords[1]['message']);

	$trace = DummyLogger::$globalRecords[1]['context']['event'];
	Assert::type(EventTrace::class, $trace);
	Assert::true($trace->handled);
	Assert::same(1, $trace->listenerCount);
	Assert::same(1, $trace->calledCount);
	Assert::type(RuntimeException::class, $trace->exception);
	Assert::count(1, $trace->listeners);
	Assert::same('Tests\Fixtures\ThrowingSubscriber::onBroken', $trace->listeners[0]->label);
	Assert::type(RuntimeException::class, $trace->listeners[0]->exception);
});
