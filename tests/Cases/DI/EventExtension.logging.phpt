<?php declare(strict_types = 1);

use Contributte\EventDispatcher\DI\EventDispatcherExtension;
use Contributte\EventDispatcher\Diagnostics\DebugDispatcher;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Disable autoloading
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('events', new EventDispatcherExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				events:
					loggers:
						- Tests\Fixtures\DummyLogger()
			NEON
			));
		})->build();

	/** @var DebugDispatcher $dd */
	$dd = $container->getByType(DebugDispatcher::class);

	Assert::notNull($dd->getLoggers());
});
