<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\DI;

use Contributte\EventDispatcher\Diagnostics\DiagnosticDispatcher;
use Contributte\EventDispatcher\EventDispatcher;
use Contributte\EventDispatcher\LazyEventDispatcher;
use Contributte\EventDispatcher\Tracy\Panel;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\ServiceCreationException;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use stdClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tracy\IBarPanel;

/**
 * @property-read stdClass $config
 */
class EventDispatcherExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'lazy' => Expect::bool(true),
			'autoload' => Expect::bool(true),
			'debug' => Expect::bool(false),
			'logger' => Expect::string(null),
		]);
	}

	/**
	 * Register services
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;

		$eventDispatcherDefinition = $builder->addDefinition($this->prefix('dispatcher'))
			->setType(EventDispatcherInterface::class);

		if ($config->lazy === true) {
			$factory = LazyEventDispatcher::class;
		} else {
			$factory = EventDispatcher::class;
		}

		if ($this->config->debug || $this->config->logger !== null) {
			$inner = $builder->addDefinition($this->prefix('innerDispatcher'))
				->setAutowired(false)
				->setFactory($factory);
			$eventDispatcherDefinition->setFactory(DiagnosticDispatcher::class, [$inner]);
		} else {
			$eventDispatcherDefinition->setFactory($factory);
		}

		if ($this->config->debug) {
			$tracyPanel = $builder->addDefinition($this->prefix('tracyPanel'))
				->setType(IBarPanel::class)
				->setFactory(Panel::class);

			$eventDispatcherDefinition->addSetup('addLogger', [$tracyPanel]);
		}

		if ($this->config->logger !== null) {
			$eventDispatcherDefinition->addSetup(
				'addLogger',
				[
					$this->config->logger,
					$this->prefix('logger'),
				]
			);
		}
	}

	/**
	 * Decorate services
	 */
	public function beforeCompile(): void
	{
		$config = $this->config;

		if ($config->autoload === true) {
			if ($config->lazy === true) {
				$this->doBeforeCompileLaziness($config->debug || $config->logger);
			} else {
				$this->doBeforeCompile();
			}
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
		foreach ($subscribers as $name => $subscriber) {
			$dispatcher->addSetup('addSubscriber', [$subscriber]);
		}
	}

	/**
	 * Collect listeners and subscribers in lazy-way
	 */
	private function doBeforeCompileLaziness(bool $useInner = false): void
	{
		$builder = $this->getContainerBuilder();
		$dispatcher = $builder->getDefinition($this->prefix($useInner ? 'innerDispatcher' : 'dispatcher'));
		assert($dispatcher instanceof ServiceDefinition);

		$subscribers = $builder->findByType(EventSubscriberInterface::class);
		foreach ($subscribers as $name => $subscriber) {
			assert($subscriber instanceof ServiceDefinition);
			$events = call_user_func([$subscriber->getEntity(), 'getSubscribedEvents']);

			/**
			 * ['eventName' => 'methodName']
			 * ['eventName' => ['methodName', $priority]]
			 * ['eventName' => [['methodName1', $priority], ['methodName2']]]
			 */
			foreach ($events as $event => $args) {
				if (is_string($args)) {
					if (!method_exists((string) $subscriber->getType(), $args)) {
						throw new ServiceCreationException(sprintf('Event listener %s does not have callable method %s', $subscriber->getType(), $args));
					}

					$dispatcher->addSetup('addSubscriberLazy', [$event, $name]);
				} elseif (is_string($args[0])) {
					if (!method_exists((string) $subscriber->getType(), $args[0])) {
						throw new ServiceCreationException(sprintf('Event listener %s does not have callable method %s', $subscriber->getType(), $args[0]));
					}

					$dispatcher->addSetup('addSubscriberLazy', [$event, $name]);
				} else {
					foreach ($args as $arg) {
						if (!method_exists((string) $subscriber->getType(), $arg[0])) {
							throw new ServiceCreationException(sprintf('Event listener %s does not have callable method %s', $subscriber->getType(), $arg[0]));
						}

						$dispatcher->addSetup('addSubscriberLazy', [$event, $name]);
					}
				}
			}
		}
	}

	/**
	 * Initialize Tracy panel
	 */
	public function afterCompile(ClassType $class): void
	{
		if ($this->config->debug) {
			$initialize = $class->getMethod('initialize');
			$initialize->addBody('$this->getService(?)->addPanel($this->getService(?));', ['tracy.bar', $this->prefix('tracyPanel')]);
			$initialize->addBody(
				'$this->getService(?)->setDispatcher($this->getService(?));',
				[$this->prefix('tracyPanel'), $this->prefix('dispatcher')]
			);
		}
	}

}
