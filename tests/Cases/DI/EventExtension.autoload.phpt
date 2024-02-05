<?php declare(strict_types = 1);

use Contributte\EventDispatcher\DI\EventDispatcherExtension;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tester\Assert;
use Tracy\Bridges\Nette\TracyExtension;

require_once __DIR__ . '/../../bootstrap.php';

// Disable autoloading
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('tracy', new TracyExtension());
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				events:
					autoload: false
				services:
					foo: Tests\Fixtures\FooSubscriber
			NEON
			));
		})->build();

	/** @var EventDispatcherInterface $em */
	$em = $container->getByType(EventDispatcherInterface::class);

	Assert::false($em->hasListeners());
});
