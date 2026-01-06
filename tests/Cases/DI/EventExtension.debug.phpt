<?php declare(strict_types = 1);

use Contributte\EventDispatcher\DI\EventDispatcherExtension;
use Contributte\EventDispatcher\Tracy\EventPanel;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Tester\Assert;
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
