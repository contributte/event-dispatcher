<?php

namespace Contributte\EventDispatcher\DI;

use Contributte\EventDispatcher\EventDispatcher;
use Contributte\EventDispatcher\LazyEventDispatcher;
use Nette\DI\CompilerExtension;
use Nette\DI\ServiceCreationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
class EventDispatcherExtension extends CompilerExtension
{

	/** @var array */
	private $defaults = [
		'lazy' => TRUE,
		'autoload' => TRUE,
	];

	/**
	 * Register services
	 *
	 * @return void
	 */
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		if ($config['lazy'] === TRUE) {
			$builder->addDefinition($this->prefix('dispatcher'))
				->setClass(LazyEventDispatcher::class);
		} else {
			$builder->addDefinition($this->prefix('dispatcher'))
				->setClass(EventDispatcher::class);
		}
	}

	/**
	 * Decorate services
	 *
	 * @return void
	 */
	public function beforeCompile()
	{
		$config = $this->validateConfig($this->defaults);

		if ($config['autoload'] === TRUE) {
			if ($config['lazy'] === TRUE) {
				$this->doBeforeCompileLaziness();
			} else {
				$this->doBeforeCompile();
			}
		}
	}

	/**
	 * Collect listeners and subscribers
	 *
	 * @return void
	 */
	private function doBeforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$dispatcher = $builder->getDefinition($this->prefix('dispatcher'));

		$subscribers = $builder->findByType(EventSubscriberInterface::class);
		foreach ($subscribers as $name => $subscriber) {
			$dispatcher->addSetup('addSubscriber', [$subscriber]);
		}
	}

	/**
	 * Collect listeners and subscribers in lazy-way
	 *
	 * @return void
	 */
	private function doBeforeCompileLaziness()
	{
		$builder = $this->getContainerBuilder();
		$dispatcher = $builder->getDefinition($this->prefix('dispatcher'));

		$subscribers = $builder->findByType(EventSubscriberInterface::class);
		foreach ($subscribers as $name => $subscriber) {
			$events = call_user_func([$subscriber->getEntity(), 'getSubscribedEvents']);

			/**
			 * array('eventName' => 'methodName')
			 * array('eventName' => array('methodName', $priority))
			 * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
			 */
			foreach ($events as $event => $args) {
				if (is_string($args)) {
					if (!method_exists($subscriber->getClass(), $args)) {
						throw new ServiceCreationException(sprintf('Event listener %s does not have callable method %s', $subscriber->getClass(), $args));
					}

					$dispatcher->addSetup('addSubscriberLazy', [$event, $name]);
				} else if (is_string($args[0])) {
					if (!method_exists($subscriber->getClass(), $args[0])) {
						throw new ServiceCreationException(sprintf('Event listener %s does not have callable method %s', $subscriber->getClass(), $args[0]));
					}

					$dispatcher->addSetup('addSubscriberLazy', [$event, $name]);
				} else {
					foreach ($args as $arg) {
						if (!method_exists($subscriber->getClass(), $arg[0])) {
							throw new ServiceCreationException(sprintf('Event listener %s does not have callable method %s', $subscriber->getClass(), $arg[0]));
						}

						$dispatcher->addSetup('addSubscriberLazy', [$event, $name]);
					}
				}
			}
		}
	}

}
