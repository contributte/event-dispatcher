# Contributte Event Dispatcher

## Content

- [Setup](#setup)
- [Configuration](#configuration)
- [Subscriber - example subscriber](#subscriber)
- [Dispatcher - dispatching events](#dispatcher)
- [Extra - extra Nette bridge](#extra)
- [Compatibility](#compatibility)

## Prologue

`Contributte/EventDispatcher` brings `Symfony\EventDispatcher` to your Nette applications. 

Please take a look at official documentation: https://symfony.com/doc/current/components/event_dispatcher.html

**Subscriber** is class that listen on defined events and handle them.

**Event** is a value object that has all data.

**Dispatcher** is manager class that tracks all listeners and thru `dispatch` method emits all events.

## Setup

```bash
composer require contributte/event-dispatcher
```

```yaml
extensions:
    events: Contributte\EventDispatcher\DI\EventDispatcherExtension
```

The extension looks for all services implementing `Symfony\Component\EventDispatcher\EventSubscriberInterface`. 
And automatically adds them to the event dispatcher. That's all. You don't have to be worried.

## Configuration

### Autoload

If you would like to add all subscribers by yourself, you have to disable `autoload`.

```yaml
events:
    autoload: true/false
```

### Laziness

Lazy options is enabled (`true`) as default. But you can override it.

```yaml
events:
    lazy: true/false
```

## Subscriber

```php
use Contributte\EventDispatcher\EventSubscriber;
use Symfony\Component\EventDispatcher\Event;

final class OrderLoggerSubscriber implements EventSubscriber
{

	public static function getSubscribedEvents(): array
	{
		return [
			'order.created' => 'log',
			'order.updated' => 'log',
			'order.paid' => 'log',
		];
	}

	public function log(Event $event): void
	{
	    // Do some magic here...
	}
}
```

## Dispatcher

This little snippet explain the cycle of event dispatcher.

```php
$dispatcher = new EventDispatcher();

// Register subscriber (this happens automatically during DIC compile time)
$dispatcher->addSubscriber(new OrderLoggerSubscriber());

// Dispatching event (this should be in your service layer)
$dispatcher->dispatch('order.created', new OrderCreatedEvent());
$dispatcher->dispatch('order.updated', new OrderUpdatedEvent());
$dispatcher->dispatch('order.paid', new OrderPaidEvent());
```

## Extra

The goal of this library is to be the most tiniest and purest adaptation of [Symfony Event-Dispatcher](https://github.com/symfony/event-dispatcher) to [Nette Framework](https://github.com/nette/).

As you can see only one `Extension` class is provided. Nette has many single packages and here comes the [`event-dispatcher-extra`](https://github.com/contributte/event-dispatcher-extra) package.

This extra repository contains useful events for **application**, **latte** and many others. [Take a look](https://github.com/contributte/event-dispatcher-extra).

## Compatibility

How to make this extension work with other Symfony/EventDispatcher implementations.

### Kdyby/Events

Kdyby/Events has a conflict with this package because of it's `SymfonyDispatcher` proxy class. To avoid the conflict simply add this to your config.neon:

```
services:
    events.symfonyProxy:
        autowired: off
```
