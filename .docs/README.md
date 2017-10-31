# Event Dispatcher

## Content :gift:

- [Usage - how to register](#usage-tada)
- [Configuration - how to configure](#configuration-wrench)
- [Subscriber - example subscriber](#subscriber-bulb)
- [Dispatcher - dispatching events](#dispatcher-zap)
- [Bridges - nette bridges](#bridges-recycle)

## Prologue

`Contributte/EventDispatcher` brings `Symfony\EventDispatcher` to your Nette applications. 

Please take a look at official documentation: https://symfony.com/doc/current/components/event_dispatcher.html

**Subscriber** is class that listen on defined events and handle them.

**Event** is a value object that has all data.

**Dispatcher** is managing class that tracks all listeners and thru `dispatch` method emitting all events.

## Usage :tada:

At first we need to register this extension.

```yaml
extensions:
    events: Contributte\EventDispatcher\DI\EventDispatcherExtension
```

`EventDispatcherExtension` looks for all services implementing `Symfony\Component\EventDispatcher\EventSubscriberInterface`. 
And automatically adds them to the event dispatcher. That's all. You don't have to be worried.

## Configuration :wrench:

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

## Subscriber :bulb:

```php
use Contributte\EventDispatcher\EventSubscriber;
use Symfony\Component\EventDispatcher\Event;

final class OrderPaidLoggerSubscriber implements EventSubscriber
{

	/**
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return ['order.paid' => 'onLog'];
	}

	/**
	 * @param Event $event
	 * @return void
	 */
	public function onLog(Event $event)
	{
	    // Do some magic here...
	}
}
```

## Dispatcher :zap:

This little snippet explain the cycle of event dispatcher.

```php
$dispatcher = new EventDispatcher();

// Register subscriber (this happens automatically during DIC compile time)
$dispatcher->addSubscriber(new OrderLoggerSubscriber());

// Dispatching event (this should be in your service layer)
$dispatcher->dispatch('order.created', new OrderCreatedEvent());
```

## Bridges :recycle:

The goal of this library is to be the most tiniest and purest adaptation of [Symfony Event-Dispatcher](https://github.com/symfony/event-dispatcher) to [Nette Framework](https://github.com/nette/).

As you can see only one `Extension` class is provided. Nette has many single packages and here comes the bridges.

There are many bridges:

| Nette                                               | Composer                                                                              | Description                                                   |
|-----------------------------------------------------|---------------------------------------------------------------------------------------|---------------------------------------------------------------|
| [Application](https://github.com/nette/application) | [`event-application-bridge`](https://github.com/contributte/event-application-bridge) | To track onRequest, onStartup and other application's events. |
| [Security](https://github.com/nette/security)       | [`event-security-bridge`](https://github.com/contributte/event-security-bridge)       | To track onLogin and onLogout events.                         |

Include all these bridges might be little bit boring (:cry:) and for that I have made an aggregation package. The `event-bridges`
aggregate all of these nette bridges to one big bridge (:recycle:).

```
composer require event-bridges
```
