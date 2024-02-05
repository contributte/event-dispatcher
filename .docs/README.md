# Contributte Event Dispatcher

## Content

- [Setup](#setup)
- [Configuration](#configuration)
- [Subscriber - example subscriber](#subscriber)
- [Dispatcher - dispatching events](#dispatcher)
- [Extra - extra Nette bridge](#extra)
- [Compatibility](#compatibility)

## Prologue

`Contributte/EventDispatcher` brings `Symfony/EventDispatcher` to your Nette applications.

Please take a look at official documentation: https://symfony.com/doc/current/components/event_dispatcher.html

**Subscriber** is class that listen on defined events and handle them.

**Event** is a value object that has all data.

**Dispatcher** is manager class that tracks all listeners and thru `dispatch` method emits all events.

## Setup

```bash
composer require contributte/event-dispatcher
```

```neon
extensions:
	events: Contributte\EventDispatcher\DI\EventDispatcherExtension
```

The extension looks for all services implementing `Symfony\Component\EventDispatcher\EventSubscriberInterface`.
And automatically adds them to the event dispatcher. That's all. You don't have to be worried.

## Configuration

**Default**

```neon
events:
		lazy: true
		autoload: true
		debug: false
		loggers: []
```

### Autoload

Autoload option is enabled (`true`) as default. If you would like to add all subscribers by yourself, you have to disable `autoload`.

```neon
events:
	autoload: true/false
```

### Lazy-loading

Lazy option is enabled (`true`) as default. But you can override it.

```neon
events:
	lazy: true/false
```

### Debug

Debug option is disabled (`false`) as default. If you want to show Tracy panel, you have to enable it.

```neon
events:
	debug: %debugMode%
```

### Logging

You can log all events via loggers. Just add logger to the configuration.

```neon
events:
	loggers:
		- App\Logger\FileLogger(%tempDir%/events.log)
```

## Subscriber

```php
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class OrderLoggerSubscriber implements EventSubscriberInterface
{

	public static function getSubscribedEvents(): array
	{
		return [
			OrderCreatedEvent::class => 'log',
			OrderUpdatedEvent::class => 'log',
			OrderPaidEvent::class => 'log',
		];
	}

	public function log(Event $event): void
	{
		// Do some magic here...
	}
}
```

```neon
services:
	- OrderLoggerSubscriber
```

## Dispatcher

Get dispatcher from DI and dispatch your events

```php
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class UserFacade
{

	public function __construct(
	  private EventDispatcherInterface $eventDispatcher
  )
	{
	}

	public function createOrder(Order $order): void
	{
		$this->eventDispatcher->dispatch(new OrderCreatedEvent($order));
	}

}
```

## Extra

The goal of this library is to be the simplest and purest adaptation of [Symfony Event-Dispatcher](https://github.com/symfony/event-dispatcher) to [Nette Framework](https://github.com/nette/).

As you can see only one `Extension` class is provided. Nette has many single packages and here comes the [`event-dispatcher-extra`](https://github.com/contributte/event-dispatcher-extra) package.

This extra repository contains useful events for **application**, **latte** and many others. [Take a look](https://github.com/contributte/event-dispatcher-extra).
