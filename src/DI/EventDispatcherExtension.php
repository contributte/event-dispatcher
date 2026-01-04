<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\DI;

use Contributte\EventDispatcher\Diagnostics\DebugDispatcher;
use Contributte\EventDispatcher\Diagnostics\TracyDispatcher;
use Contributte\EventDispatcher\LazyListener;
use Contributte\EventDispatcher\Tracy\EventPanel;
use Nette\DI\CompilerExtension;
use Nette\DI\Container;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\DI\ServiceCreationException;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use stdClass;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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
			'debug' => Expect::bool(false),
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
		if ($config->debug === true) {
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

		if ($config->debug) {
			$initialization->addBody(
				// @phpstan-ignore-next-line
				$builder->formatPhp('?->addPanel(?);', [
					$builder->getDefinitionByType(Bar::class),
					new Statement(EventPanel::class, [$builder->getDefinition($this->prefix('dispatcher.tracy'))]),
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
		$dispatcher = $builder->getDefinition($this->prefix('dispatcher'));
		assert($dispatcher instanceof ServiceDefinition);

		$subscribers = $builder->findByType(EventSubscriberInterface::class);
		foreach ($subscribers as $subscriber) {
			$dispatcher->addSetup('addSubscriber', [$subscriber]);
		}
	}

	/**
	 * Collect listeners and subscribers in lazy-way
	 */
	private function doBeforeCompileLaziness(): void
	{
		$builder = $this->getContainerBuilder();
		$dispatcher = $builder->getDefinition($this->prefix('dispatcher'));
		assert($dispatcher instanceof ServiceDefinition);

		$subscribers = $builder->findByType(EventSubscriberInterface::class);
		foreach ($subscribers as $serviceName => $subscriber) {
			assert($subscriber instanceof ServiceDefinition);
			$events = call_user_func([$subscriber->getEntity(), 'getSubscribedEvents']); // @phpstan-ignore-line
			assert(is_array($events));

			foreach ($events as $event => $params) {
				if (is_string($params)) { // ['eventName' => 'methodName']
					if (!method_exists((string) $subscriber->getType(), $params)) {
						throw new ServiceCreationException(sprintf('Event listener %s does not have callable method %s', $subscriber->getType(), $params));
					}

					$dispatcher->addSetup('addListener', [
							'eventName' => $event,
							'listener' => new Statement(LazyListener::class, [$serviceName, $params, $builder->getDefinitionByType(Container::class)]),
							'priority' => 0,
						]);
				} elseif (is_array($params) && isset($params[0]) && is_string($params[0])) { // ['eventName' => ['methodName', $priority]]
					$method = $params[0];
					$priority = isset($params[1]) && is_int($params[1]) ? $params[1] : 0;

					if (!method_exists((string) $subscriber->getType(), $method)) {
						throw new ServiceCreationException(sprintf('Event listener %s does not have callable method %s', $subscriber->getType(), $method));
					}

					$dispatcher->addSetup('addListener', [
							'eventName' => $event,
							'listener' => new Statement(LazyListener::class, [$serviceName, $method, $builder->getDefinitionByType(Container::class)]),
							'priority' => $priority,
						]);
				} elseif (is_array($params) && isset($params[0]) && is_array($params[0])) { // ['eventName' => [['methodName1', $priority], ['methodName2']]]
					foreach ($params as $listener) {
						assert(is_array($listener) && isset($listener[0]) && is_string($listener[0]));
						$method = $listener[0];
						$priority = isset($listener[1]) && is_int($listener[1]) ? $listener[1] : 0;

						if (!method_exists((string) $subscriber->getType(), $method)) {
							throw new ServiceCreationException(sprintf('Event listener %s does not have callable method %s', $subscriber->getType(), $method));
						}

						$dispatcher->addSetup('addListener', [
								'eventName' => $event,
								'listener' => new Statement(LazyListener::class, [$serviceName, $method, $builder->getDefinitionByType(Container::class)]),
								'priority' => $priority,
							]);
					}
				}
			}
		}
	}

}
