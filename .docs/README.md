# Event Dispatcher

## Content

- [Usage - how to register](#usage)
- [Configuration - how to configure](#configuration)
- [Subscriber - example subscriber](#subscriber)

## Usage

```yaml
extensions:
    events: Contributte\EventDispatcher\DI\EventDispatcherExtensions
```

Extension looks for all subscribers in DIC implementing `Symfony\Component\EventDispatcher\EventSubscriberInterface`. And automatically adds them to the event dispatcher. 
That's all. You don't have to be worried.

If you would like to add all subscribers by yourself, you have to disable `autoload`.

```yaml
events:
    autoload: true/false
```

## Configuration

### Laziness

Lazy options is enabled (`true`) as default. But you can override it.

```yaml
events:
    lazy: true/false
```

### Nette.Application

There are several nette events on which you can listen to.

```php
use Contributte\EventDispatcher\Events\Application\ApplicationEvents;
```

- `ApplicationEvents::ON_STARTUP`
- `ApplicationEvents::ON_SHUTDOWN`
- `ApplicationEvents::ON_REQUEST`
- `ApplicationEvents::ON_PRESENTER`
- `ApplicationEvents::ON_RESPONSE`
- `ApplicationEvents::ON_ERROR`

## Subscriber

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
