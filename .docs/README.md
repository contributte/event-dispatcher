# Event Dispatcher

## Content :gift:

- [Usage - how to register](#usage)
- [Configuration - how to configure](#configuration)
- [Subscriber - example subscriber](#subscriber)
- [Bridges - nette bridges](#bridges)

## Usage :tada:

```yaml
extensions:
    events: Contributte\EventDispatcher\DI\EventDispatcherExtensions
```

Extension looks for all subscribers in DIC implementing `Symfony\Component\EventDispatcher\EventSubscriberInterface`. And automatically adds them to the event dispatcher. 
That's all. You don't have to be worried.

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

## Bridges

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
