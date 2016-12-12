<?php

namespace Contributte\EventDispatcher\DI;

use Contributte\EventDispatcher\EventDispatcher;
use Contributte\EventDispatcher\Events\Application\ApplicationEvents;
use Contributte\EventDispatcher\Events\Application\ErrorEvent;
use Contributte\EventDispatcher\Events\Application\PresenterEvent;
use Contributte\EventDispatcher\Events\Application\RequestEvent;
use Contributte\EventDispatcher\Events\Application\ResponseEvent;
use Contributte\EventDispatcher\Events\Application\ShutdownEvent;
use Contributte\EventDispatcher\Events\Application\StartupEvent;
use Contributte\EventDispatcher\EventSubscriber;
use Contributte\EventDispatcher\LazyEventDispatcher;
use Nette\DI\CompilerExtension;
use Nette\DI\ServiceCreationException;
use Nette\PhpGenerator\PhpLiteral;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
final class EventDispatcherExtension extends CompilerExtension
{

	/** @var array */
	private $defaults = [
		'lazy' => TRUE,
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

		if ($config['lazy'] === TRUE) {
			$this->doBeforeCompileLaziness();
		} else {
			$this->doBeforeCompile();
		}

		$this->doBeforeCompileBridge();
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

		$subscribers = $builder->findByType(EventSubscriber::class);
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

		$subscribers = $builder->findByType(EventSubscriber::class);
		foreach ($subscribers as $name => $subscriber) {
			$events = call_user_func([$subscriber->getEntity(), 'getSubscribedEvents']);

			/**
			 * array('eventName' => 'methodName')
			 * array('eventName' => array('methodName', $priority))
			 * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
			 */
			foreach ($events as $event => $args) {
				if (is_string($args)) {
					$dispatcher->addSetup('addSubscriberLazy', [$event, $name]);
				} else if (is_string($args[0])) {
					if (!method_exists($subscriber, $args[0])) {
						throw new ServiceCreationException(sprintf('Event listener %s does not have callable method %s', get_class($subscriber), $args[0]));
					}

					$dispatcher->addSetup('addSubscriberLazy', [$event, $args[0]]);
				} else {
					foreach ($args as $arg) {
						if (!method_exists($subscriber, $arg[0])) {
							throw new ServiceCreationException(sprintf('Event listener %s does not have callable method %s', get_class($subscriber), $arg[0]));
						}

						$dispatcher->addSetup('addSubscriberLazy', [$event, $arg[0]]);
					}
				}
			}
		}
	}

	/**
	 * Build bridge into nette application events
	 *
	 * @return void
	 */
	private function doBeforeCompileBridge()
	{
		$builder = $this->getContainerBuilder();

		// Skip if nette application is not provided
		if (!$builder->hasDefinition('application.application')) return;

		$application = $builder->getDefinition('application.application');
		$dispatcher = $builder->getDefinition($this->prefix('dispatcher'));

		$application->addSetup('?->onStartup[] = function() {?->dispatch(?, new ?(...func_get_args()));}', [
			'@self',
			$dispatcher,
			ApplicationEvents::ON_STARTUP,
			new PhpLiteral(StartupEvent::class),
		]);
		$application->addSetup('?->onError[] = function() {?->dispatch(?, new ?(...func_get_args()));}', [
			'@self',
			$dispatcher,
			ApplicationEvents::ON_ERROR,
			new PhpLiteral(ErrorEvent::class),
		]);
		$application->addSetup('?->onPresenter[] = function() {?->dispatch(?, new ?(...func_get_args()));}', [
			'@self',
			$dispatcher,
			ApplicationEvents::ON_PRESENTER,
			new PhpLiteral(PresenterEvent::class),
		]);
		$application->addSetup('?->onRequest[] = function() {?->dispatch(?, new ?(...func_get_args()));}', [
			'@self',
			$dispatcher,
			ApplicationEvents::ON_REQUEST,
			new PhpLiteral(RequestEvent::class),
		]);
		$application->addSetup('?->onResponse[] = function() {?->dispatch(?, new ?(...func_get_args()));}', [
			'@self',
			$dispatcher,
			ApplicationEvents::ON_RESPONSE,
			new PhpLiteral(ResponseEvent::class),
		]);
		$application->addSetup('?->onShutdown[] = function() {?->dispatch(?, new ?(...func_get_args()));}', [
			'@self',
			$dispatcher,
			ApplicationEvents::ON_SHUTDOWN,
			new PhpLiteral(ShutdownEvent::class),
		]);
	}

}
