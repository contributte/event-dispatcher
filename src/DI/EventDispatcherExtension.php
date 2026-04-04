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
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use stdClass;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;
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
		$dispatcher = $builder->getDefinition($this->prefix('dispatcher'));
		assert($dispatcher instanceof ServiceDefinition);

		$subscribers = $builder->findByType(EventSubscriberInterface::class);
		foreach ($subscribers as $subscriber) {
			$dispatcher->addSetup('addSubscriber', [$subscriber]);
		}

		$this->registerAttributedListeners($dispatcher, lazy: false);
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

			foreach ($this->resolveSubscriberListeners($subscriber) as $listener) {
				$this->registerListener(
					$dispatcher,
					$serviceName,
					$listener['eventName'],
					$listener['methodName'],
					$listener['priority'],
					true,
				);
			}
		}

		$this->registerAttributedListeners($dispatcher, lazy: true);
	}

	/**
	 * @return list<array{eventName: string, methodName: string, priority: int}>
	 */
	private function resolveSubscriberListeners(ServiceDefinition $subscriber): array
	{
		$events = call_user_func([$subscriber->getEntity(), 'getSubscribedEvents']); // @phpstan-ignore-line
		assert(is_array($events));

		$listeners = [];
		foreach ($events as $event => $params) {
			if (is_string($params)) { // ['eventName' => 'methodName']
				$this->assertListenerMethod((string) $subscriber->getType(), $params);
				$listeners[] = [
					'eventName' => $event,
					'methodName' => $params,
					'priority' => 0,
				];
			} elseif (is_array($params) && isset($params[0]) && is_string($params[0])) { // ['eventName' => ['methodName', $priority]]
				$method = $params[0];
				$priority = isset($params[1]) && is_int($params[1]) ? $params[1] : 0;

				$this->assertListenerMethod((string) $subscriber->getType(), $method);
				$listeners[] = [
					'eventName' => $event,
					'methodName' => $method,
					'priority' => $priority,
				];
			} elseif (is_array($params) && isset($params[0]) && is_array($params[0])) { // ['eventName' => [['methodName1', $priority], ['methodName2']]]
				foreach ($params as $listener) {
					assert(is_array($listener) && isset($listener[0]) && is_string($listener[0]));
					$method = $listener[0];
					$priority = isset($listener[1]) && is_int($listener[1]) ? $listener[1] : 0;

					$this->assertListenerMethod((string) $subscriber->getType(), $method);
					$listeners[] = [
						'eventName' => $event,
						'methodName' => $method,
						'priority' => $priority,
					];
				}
			}
		}

		return $listeners;
	}

	private function registerAttributedListeners(ServiceDefinition $dispatcher, bool $lazy): void
	{
		foreach ($this->resolveAttributedListeners() as $listener) {
			$this->registerListener(
				$dispatcher,
				$listener['serviceName'],
				$listener['eventName'],
				$listener['methodName'],
				$listener['priority'],
				$lazy,
			);
		}
	}

	private function registerListener(
		ServiceDefinition $dispatcher,
		string $serviceName,
		string $eventName,
		string $methodName,
		int $priority,
		bool $lazy,
	): void
	{
		$builder = $this->getContainerBuilder();

		$listener = $lazy
			? new Statement(LazyListener::class, [$serviceName, $methodName, $builder->getDefinitionByType(Container::class)])
			: [$builder->getDefinition($serviceName), $methodName];

		$dispatcher->addSetup('addListener', [
			'eventName' => $eventName,
			'listener' => $listener,
			'priority' => $priority,
		]);
	}

	/**
	 * @return list<array{serviceName: string, eventName: string, methodName: string, priority: int}>
	 */
	private function resolveAttributedListeners(): array
	{
		$builder = $this->getContainerBuilder();
		$listeners = [];

		foreach ($builder->getDefinitions() as $serviceName => $definition) {
			if (!$definition instanceof ServiceDefinition) {
				continue;
			}

			$className = $this->resolveDefinitionClass($definition);
			if ($className === null) {
				continue;
			}

			$reflection = new ReflectionClass($className);

			foreach ($reflection->getAttributes(AsEventListener::class) as $attribute) {
				$listeners[] = $this->resolveAttributedListener($serviceName, $reflection, null, $attribute->newInstance());
			}

			foreach ($reflection->getMethods() as $method) {
				foreach ($method->getAttributes(AsEventListener::class) as $attribute) {
					$listeners[] = $this->resolveAttributedListener($serviceName, $reflection, $method, $attribute->newInstance());
				}
			}
		}

		return $listeners;
	}

	/**
	 * @return class-string|null
	 */
	private function resolveDefinitionClass(ServiceDefinition $definition): ?string
	{
		$type = $definition->getType();
		if (is_string($type) && class_exists($type)) {
			return $type;
		}

		$entity = $definition->getEntity();
		if (is_string($entity) && class_exists($entity)) {
			return $entity;
		}

		return null;
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 * @return array{serviceName: string, eventName: string, methodName: string, priority: int}
	 */
	private function resolveAttributedListener(
		string $serviceName,
		ReflectionClass $reflection,
		?ReflectionMethod $method,
		AsEventListener $attribute,
	): array
	{
		if ($attribute->dispatcher !== null) {
			throw new ServiceCreationException(sprintf(
				'Event listener %s cannot use dispatcher in #[AsEventListener], named dispatchers are not supported.',
				$reflection->getName(),
			));
		}

		if ($method !== null && $attribute->method !== null) {
			throw new ServiceCreationException(sprintf(
				'Event listener %s::%s() cannot declare method in #[AsEventListener].',
				$reflection->getName(),
				$method->getName(),
			));
		}

		$methodName = $method?->getName() ?? $this->resolveAttributedMethod($reflection, $attribute);
		$this->assertListenerMethod($reflection->getName(), $methodName);

		$eventName = $attribute->event ?? $this->inferAttributedEventName($reflection, $methodName);

		return [
			'serviceName' => $serviceName,
			'eventName' => $eventName,
			'methodName' => $methodName,
			'priority' => $attribute->priority,
		];
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 */
	private function resolveAttributedMethod(ReflectionClass $reflection, AsEventListener $attribute): string
	{
		if ($attribute->method !== null) {
			return $attribute->method;
		}

		if ($attribute->event !== null) {
			$method = $this->formatEventListenerMethod($attribute->event);
			if ($reflection->hasMethod($method) && $reflection->getMethod($method)->isPublic()) {
				return $method;
			}
		}

		if ($reflection->hasMethod('__invoke') && $reflection->getMethod('__invoke')->isPublic()) {
			return '__invoke';
		}

		throw new ServiceCreationException(sprintf(
			'Event listener %s must define method in #[AsEventListener] or provide a public __invoke() handler.',
			$reflection->getName(),
		));
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 */
	private function inferAttributedEventName(ReflectionClass $reflection, string $methodName): string
	{
		$method = $reflection->getMethod($methodName);

		if (
			$method->getNumberOfParameters() < 1
			|| !($type = $method->getParameters()[0]->getType()) instanceof ReflectionNamedType
			|| $type->isBuiltin()
			|| $type->getName() === Event::class
		) {
			throw new ServiceCreationException(sprintf(
				'Event listener %s::%s() must define event in #[AsEventListener] or type its first argument with a concrete event class.',
				$reflection->getName(),
				$methodName,
			));
		}

		return $type->getName();
	}

	private function assertListenerMethod(string $className, string $methodName): void
	{
		if (!method_exists($className, $methodName) || !(new ReflectionMethod($className, $methodName))->isPublic()) {
			throw new ServiceCreationException(sprintf('Event listener %s does not have callable method %s', $className, $methodName));
		}
	}

	private function formatEventListenerMethod(string $eventName): string
	{
		$method = preg_replace_callback(
			['/(?<=\\b|_)[a-z]/i', '/[^a-z0-9]/i'],
			static fn (array $matches): string => strtoupper($matches[0]),
			$eventName,
		);
		if ($method === null) {
			throw new ServiceCreationException(sprintf('Could not normalize event listener method for event %s', $eventName));
		}

		$normalized = preg_replace('/[^a-z0-9]/i', '', $method);
		if ($normalized === null) {
			throw new ServiceCreationException(sprintf('Could not normalize event listener method for event %s', $eventName));
		}

		return 'on' . $normalized;
	}

}
