<?php declare(strict_types = 1);

use Contributte\EventDispatcher\DI\EventDispatcherExtension;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Nette\DI\ServiceCreationException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Tester\Assert;
use Tests\Fixtures\ClassAttributedListener;
use Tests\Fixtures\InferredMethodAttributedListener;
use Tests\Fixtures\InvokableAttributedListener;
use Tests\Fixtures\MethodAttributedListener;
use Tests\Fixtures\TypedEvent;

require_once __DIR__ . '/../../bootstrap.php';

// Register method-level listeners lazily by default.
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				services:
					listener: Tests\Fixtures\MethodAttributedListener
			NEON
			));
		})->build();

	/** @var EventDispatcherInterface $em */
	$em = $container->getByType(EventDispatcherInterface::class);

	Assert::false($container->isCreated('listener'));
	Assert::true($em->hasListeners('listener.method'));

	$listeners = $em->getListeners('listener.method');
	Assert::count(1, $listeners);
	Assert::same(5, $em->getListenerPriority('listener.method', $listeners[0]));

	$event = new Event();
	$em->dispatch($event, 'listener.method');

	Assert::true($container->isCreated('listener'));

	/** @var MethodAttributedListener $listener */
	$listener = $container->getByType(MethodAttributedListener::class);
	Assert::equal([$event], $listener->onMethodCall);
});

// Infer typed events and register repeatable method attributes.
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				services:
					listener: Tests\Fixtures\MethodAttributedListener
			NEON
			));
		})->build();

	/** @var EventDispatcherInterface $em */
	$em = $container->getByType(EventDispatcherInterface::class);

	Assert::true($em->hasListeners(TypedEvent::class));
	Assert::count(1, $em->getListeners('listener.repeat.one'));
	Assert::count(1, $em->getListeners('listener.repeat.two'));

	$typedEvent = new TypedEvent();
	$repeatOne = new Event();
	$repeatTwo = new Event();

	$em->dispatch($typedEvent);
	$em->dispatch($repeatOne, 'listener.repeat.one');
	$em->dispatch($repeatTwo, 'listener.repeat.two');

	/** @var MethodAttributedListener $listener */
	$listener = $container->getByType(MethodAttributedListener::class);
	Assert::equal([$typedEvent], $listener->onTypedCall);
	Assert::equal([$repeatOne, $repeatTwo], $listener->onRepeatedCall);
});

// Register class-level listeners with explicit and inferred methods.
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				services:
					classListener: Tests\Fixtures\ClassAttributedListener
					inferredListener: Tests\Fixtures\InferredMethodAttributedListener
					invokableListener: Tests\Fixtures\InvokableAttributedListener
			NEON
			));
		})->build();

	/** @var EventDispatcherInterface $em */
	$em = $container->getByType(EventDispatcherInterface::class);

	$classEvent = new Event();
	$inferredEvent = new Event();
	$typedEvent = new TypedEvent();

	$listeners = $em->getListeners('listener.class');
	Assert::count(1, $listeners);
	Assert::same(3, $em->getListenerPriority('listener.class', $listeners[0]));
	Assert::true($em->hasListeners(TypedEvent::class));

	$em->dispatch($classEvent, 'listener.class');
	$em->dispatch($inferredEvent, 'listener.inferred');
	$em->dispatch($typedEvent);

	/** @var ClassAttributedListener $classListener */
	$classListener = $container->getByType(ClassAttributedListener::class);
	Assert::equal([$classEvent], $classListener->onCall);

	/** @var InferredMethodAttributedListener $inferredListener */
	$inferredListener = $container->getByType(InferredMethodAttributedListener::class);
	Assert::equal([$inferredEvent], $inferredListener->onCall);

	/** @var InvokableAttributedListener $invokableListener */
	$invokableListener = $container->getByType(InvokableAttributedListener::class);
	Assert::equal([$typedEvent], $invokableListener->onCall);
});

// Do not autoload attribute listeners when disabled.
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				events:
					autoload: false
				services:
					listener: Tests\Fixtures\MethodAttributedListener
			NEON
			));
		})->build();

	/** @var EventDispatcherInterface $em */
	$em = $container->getByType(EventDispatcherInterface::class);

	Assert::false($em->hasListeners());
	Assert::false($container->isCreated('listener'));
});

// Reject method attributes that declare a method name.
Toolkit::test(function (): void {
	Assert::exception(
		static function (): void {
			ContainerBuilder::of()
				->withCompiler(function (Compiler $compiler): void {
					$compiler->addExtension('events', new EventDispatcherExtension());
					$compiler->addConfig(Neonkit::load(<<<'NEON'
						services:
							listener: Tests\Fixtures\InvalidMethodAttributeListener
					NEON
					));
				})->build();
		},
		ServiceCreationException::class,
		'#cannot declare method#',
	);
});

// Reject inferred events without a concrete first argument type.
Toolkit::test(function (): void {
	Assert::exception(
		static function (): void {
			ContainerBuilder::of()
				->withCompiler(function (Compiler $compiler): void {
					$compiler->addExtension('events', new EventDispatcherExtension());
					$compiler->addConfig(Neonkit::load(<<<'NEON'
						services:
							listener: Tests\Fixtures\InvalidInferredEventListener
					NEON
					));
				})->build();
		},
		ServiceCreationException::class,
		'~must define event in #\[AsEventListener\]~',
	);
});

// Reject class attributes without a resolvable handler.
Toolkit::test(function (): void {
	Assert::exception(
		static function (): void {
			ContainerBuilder::of()
				->withCompiler(function (Compiler $compiler): void {
					$compiler->addExtension('events', new EventDispatcherExtension());
					$compiler->addConfig(Neonkit::load(<<<'NEON'
						services:
							listener: Tests\Fixtures\InvalidClassAttributedListener
					NEON
					));
				})->build();
		},
		ServiceCreationException::class,
		'#public __invoke\(\) handler#',
	);
});
