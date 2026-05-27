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
	public function getSubscriberListeners(): array
	{
		$listeners = [];

		foreach ($this->getSubscribers() as $serviceName => $subscriber) {
			$className = $this->getDefinitionClass($subscriber);
			if ($className === null || !is_a($className, EventSubscriberInterface::class, true)) {
				continue;
			}

			array_push($listeners, ...Reflector::getSubscriberListeners($serviceName, $className));
		}

		return $listeners;
	}

	/**
	 * @return list<ListenerDefinition>
	 */
	public function getAttributedListeners(): array
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

			array_push($listeners, ...Reflector::getAttributedListeners($serviceName, $className));
		}

		return $listeners;
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
	 * @return array<string, ServiceDefinition>
	 */
	private function getSubscribers(): array
	{
		/** @var array<string, ServiceDefinition> $subscribers */
		$subscribers = $this->builder->findByType(EventSubscriberInterface::class);

		return $subscribers;
	}

	/**
	 * @return class-string|null
	 */
	private function getDefinitionClass(ServiceDefinition $definition): ?string
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

}
