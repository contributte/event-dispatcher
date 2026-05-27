<?php declare(strict_types = 1);

namespace Contributte\EventDispatcher\DI\Utils;

use Nette\DI\ServiceCreationException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class Reflector
{

	/**
	 * @param class-string<EventSubscriberInterface> $className
	 * @return list<ListenerDefinition>
	 */
	public static function getSubscriberListeners(string $serviceName, string $className): array
	{
		$events = self::getSubscribedEvents($className);

		$listeners = [];
		foreach ($events as $event => $params) {
			if (is_string($params)) { // ['eventName' => 'methodName']
				self::assertListenerMethod($className, $params);
				$listeners[] = new ListenerDefinition($serviceName, $event, $params, 0);
			} elseif (is_array($params) && isset($params[0]) && is_string($params[0])) { // ['eventName' => ['methodName', $priority]]
				$method = $params[0];
				$priority = isset($params[1]) && is_int($params[1]) ? $params[1] : 0;

				self::assertListenerMethod($className, $method);
				$listeners[] = new ListenerDefinition($serviceName, $event, $method, $priority);
			} elseif (is_array($params) && isset($params[0]) && is_array($params[0])) { // ['eventName' => [['methodName1', $priority], ['methodName2']]]
				foreach ($params as $listener) {
					assert(is_array($listener) && isset($listener[0]) && is_string($listener[0]));
					$method = $listener[0];
					$priority = isset($listener[1]) && is_int($listener[1]) ? $listener[1] : 0;

					self::assertListenerMethod($className, $method);
					$listeners[] = new ListenerDefinition($serviceName, $event, $method, $priority);
				}
			}
		}

		return $listeners;
	}

	/**
	 * @param class-string $className
	 * @return list<ListenerDefinition>
	 */
	public static function getAttributedListeners(string $serviceName, string $className): array
	{
		$reflection = new ReflectionClass($className);
		$listeners = [];

		foreach ($reflection->getAttributes(AsEventListener::class) as $attribute) {
			$listeners[] = self::resolveAttributedListener($serviceName, $reflection, null, $attribute);
		}

		foreach ($reflection->getMethods() as $method) {
			foreach ($method->getAttributes(AsEventListener::class) as $attribute) {
				$listeners[] = self::resolveAttributedListener($serviceName, $reflection, $method, $attribute);
			}
		}

		return $listeners;
	}

	/**
	 * @param class-string<EventSubscriberInterface> $className
	 * @return array<mixed>
	 */
	private static function getSubscribedEvents(string $className): array
	{
		return $className::getSubscribedEvents();
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 * @param ReflectionAttribute<AsEventListener> $attribute
	 */
	private static function resolveAttributedListener(
		string $serviceName,
		ReflectionClass $reflection,
		?ReflectionMethod $method,
		ReflectionAttribute $attribute,
	): ListenerDefinition
	{
		$listener = $attribute->newInstance();

		if ($listener->dispatcher !== null) {
			throw new ServiceCreationException(sprintf(
				'Event listener %s cannot use dispatcher in #[AsEventListener], named dispatchers are not supported.',
				$reflection->getName(),
			));
		}

		if ($method !== null && $listener->method !== null) {
			throw new ServiceCreationException(sprintf(
				'Event listener %s::%s() cannot declare method in #[AsEventListener].',
				$reflection->getName(),
				$method->getName(),
			));
		}

		$methodName = $method?->getName() ?? self::resolveAttributedMethod($reflection, $listener);
		self::assertListenerMethod($reflection->getName(), $methodName);

		$eventName = $listener->event ?? self::inferAttributedEventName($reflection, $methodName);

		return new ListenerDefinition($serviceName, $eventName, $methodName, $listener->priority);
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 */
	private static function resolveAttributedMethod(ReflectionClass $reflection, AsEventListener $listener): string
	{
		if ($listener->method !== null) {
			return $listener->method;
		}

		if ($listener->event !== null) {
			$method = self::formatEventListenerMethod($listener->event);
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
	private static function inferAttributedEventName(ReflectionClass $reflection, string $methodName): string
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

	/**
	 * @param class-string $className
	 */
	private static function assertListenerMethod(string $className, string $methodName): void
	{
		if (!method_exists($className, $methodName) || !(new ReflectionMethod($className, $methodName))->isPublic()) {
			throw new ServiceCreationException(sprintf('Event listener %s does not have callable method %s', $className, $methodName));
		}
	}

	private static function formatEventListenerMethod(string $eventName): string
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
