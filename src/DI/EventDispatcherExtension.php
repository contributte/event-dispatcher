<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\DI;

use Contributte\EventDispatcher\DI\Utils\BuilderMan;
use Contributte\EventDispatcher\Diagnostics\DebugDispatcher;
use Contributte\EventDispatcher\Diagnostics\TracyDispatcher;
use Contributte\EventDispatcher\Tracy\EventPanel;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use stdClass;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tracy\Bar;

/**
 * @method stdClass getConfig()
 */
class EventDispatcherExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'lazy' => Expect::bool(true),
			'autoload' => Expect::bool(true),
			'debug' => Expect::structure([
				'panel' => Expect::bool(false),
				'deep' => Expect::int()->nullable(),
			]),
			'loggers' => Expect::arrayOf(Expect::type(Statement::class)),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		// Original dispatcher
		$outerDispatcher = $dispatcherDef = $builder->addDefinition($this->prefix('dispatcher'))
			->setType(EventDispatcherInterface::class)
			->setFactory(EventDispatcher::class)
			->setAutowired(false);

		// Dispatcher for logging
		if ($config->loggers !== []) {
			$loggingDispatcherDef = $builder->addDefinition($this->prefix('dispatcher.logging'))
				->setFactory(DebugDispatcher::class, [$outerDispatcher])
				->setAutowired(false);
			$outerDispatcher = $loggingDispatcherDef;
		}

		// Dispatcher for Tracy bar
		if ($config->debug->panel) {
			$tracyDispatcherDef = $builder->addDefinition($this->prefix('dispatcher.tracy'))
				->setType(EventDispatcherInterface::class)
				->setFactory(TracyDispatcher::class, [$outerDispatcher])
				->setAutowired(false);
			$outerDispatcher = $tracyDispatcherDef;
		}

		// Only outer dispatcher should be autowired
		$outerDispatcher->setAutowired();
	}

	public function beforeCompile(): void
	{
		$config = $this->getConfig();

		if ($config->autoload === true) {
			if ($config->lazy === true) {
				$this->doBeforeCompileLaziness();
			} else {
				$this->doBeforeCompile();
			}
		}
	}

	public function afterCompile(ClassType $class): void
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();
		$initialization = $this->getInitialization();

		if ($config->debug->panel) {
			$initialization->addBody(
				// @phpstan-ignore-next-line
				$builder->formatPhp('?->addPanel(?);', [
					$builder->getDefinitionByType(Bar::class),
					new Statement(EventPanel::class, [
						$builder->getDefinition($this->prefix('dispatcher.tracy')),
						$config->debug->deep,
					]),
				])
			);
		}
	}

	/**
	 * Collect listeners and subscribers
	 */
	private function doBeforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$builderMan = BuilderMan::of($builder);
		$dispatcher = $builder->getDefinition($this->prefix('dispatcher'));
		assert($dispatcher instanceof ServiceDefinition);

		foreach ($builderMan->getSubscribers() as $subscriber) {
			$dispatcher->addSetup('addSubscriber', [$subscriber]);
		}

		foreach ($builderMan->getAttributedListeners() as $listener) {
			$builderMan->registerListener($dispatcher, $listener, lazy: false);
		}
	}

	/**
	 * Collect listeners and subscribers in lazy-way
	 */
	private function doBeforeCompileLaziness(): void
	{
		$builder = $this->getContainerBuilder();
		$builderMan = BuilderMan::of($builder);
		$dispatcher = $builder->getDefinition($this->prefix('dispatcher'));
		assert($dispatcher instanceof ServiceDefinition);

		foreach ($builderMan->getSubscriberListeners() as $listener) {
			$builderMan->registerListener($dispatcher, $listener, lazy: true);
		}

		foreach ($builderMan->getAttributedListeners() as $listener) {
			$builderMan->registerListener($dispatcher, $listener, lazy: true);
		}
	}

}
