<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\DI\Utils;

use Contributte\EventDispatcher\LazyListener;
use Nette\DI\Container;
use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class BuilderMan
{

	private function __construct(private readonly ContainerBuilder $builder)
	{
	}

	public static function of(ContainerBuilder $builder): self
	{
		return new self($builder);
	}

	/**
	 * @return list<ListenerDefinition>
	 */
	public function getListeners(): array
	{
		return [
			...$this->getSubscriberListeners(),
			...$this->getAttributedListeners(),
		];
	}

	public function registerListener(ServiceDefinition $dispatcher, ListenerDefinition $listener): void
	{
		$dispatcher->addSetup('addListener', [
			'eventName' => $listener->eventName,
			'listener' => new Statement(LazyListener::class, [$listener->serviceName, $listener->methodName, $this->builder->getDefinitionByType(Container::class)]),
			'priority' => $listener->priority,
		]);
	}

	/**
	 * @return list<ListenerDefinition>
	 */
	private function getSubscriberListeners(): array
	{
		$listeners = [];

		/** @var array<string, ServiceDefinition> $subscribers */
		$subscribers = $this->builder->findByType(EventSubscriberInterface::class);

		foreach ($subscribers as $serviceName => $subscriber) {
			$className = $this->getDefinitionClass($subscriber);
			if ($className === null || !is_a($className, EventSubscriberInterface::class, true)) {
				continue;
			}

			$listeners = [...$listeners, ...Reflector::getSubscriberListeners($serviceName, $className)];
		}

		return $listeners;
	}

	/**
	 * @return list<ListenerDefinition>
	 */
	private function getAttributedListeners(): array
	{
		$listeners = [];

		foreach ($this->builder->getDefinitions() as $serviceName => $definition) {
			if (!$definition instanceof ServiceDefinition) {
				continue;
			}

			$className = $this->getDefinitionClass($definition);
			if ($className === null) {
				continue;
			}

			$listeners = [...$listeners, ...Reflector::getAttributedListeners($serviceName, $className)];
		}

		return $listeners;
	}

	/**
	 * @return class-string|null
	 */
	private function getDefinitionClass(ServiceDefinition $definition): ?string
	{
		$type = $definition->getType();
		if ($type !== null && class_exists($type)) {
			return $type;
		}

		return null;
	}

}
