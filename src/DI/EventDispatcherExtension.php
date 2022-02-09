<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\DI;

use Contributte\EventDispatcher\EventDispatcher;
use Contributte\EventDispatcher\LazyEventDispatcher;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\ServiceCreationException;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use stdClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
			$eventDispatcherDefinition
				->setFactory(LazyEventDispatcher::class);
		} else {
			$eventDispatcherDefinition
				->setFactory(EventDispatcher::class);
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
				$this->doBeforeCompileLaziness();
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
	private function doBeforeCompileLaziness(): void
	{
		$builder = $this->getContainerBuilder();
		$dispatcher = $builder->getDefinition($this->prefix('dispatcher'));
		assert($dispatcher instanceof ServiceDefinition);

		$subscribers = $builder->findByType(EventSubscriberInterface::class);
		foreach ($subscribers as $name => $subscriber) {
			assert($subscriber instanceof ServiceDefinition);
			$events = call_user_func([$subscriber->getEntity(), 'getSubscribedEvents']);
			assert(is_array($events));

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

}
