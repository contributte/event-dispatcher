# Contributte Event Dispatcher

Integration of [Symfony EventDispatcher](https://symfony.com/doc/current/components/event_dispatcher.html) into Nette Framework.

## Content

- [Getting started](#getting-started)
  - [Setup](#setup)
  - [Configuration](#configuration)
    - [Minimal configuration](#minimal-configuration)
    - [Full configuration](#full-configuration)
    - [Configuration options](#configuration-options)
- [Subscribers](#subscribers)
  - [Creating a subscriber](#creating-a-subscriber)
  - [Event listener formats](#event-listener-formats)
  - [Prioritized listeners](#prioritized-listeners)
- [Events](#events)
  - [Creating events](#creating-events)
  - [Dispatching events](#dispatching-events)
- [Advanced](#advanced)
  - [Lazy-loading](#lazy-loading)
  - [Debugging with Tracy](#debugging-with-tracy)
  - [Logging](#logging)
- [Testing](#testing)
- [Extra](#extra)

---

# Getting started

This library provides seamless integration of Symfony's EventDispatcher component into Nette Framework. It enables event-driven architecture in your applications with automatic subscriber discovery, lazy-loading, and powerful debugging tools.

**Key concepts:**

- **Event** - A value object containing all data about something that happened in your application
- **Subscriber** - A class that listens to defined events and handles them
- **Dispatcher** - A manager class that tracks all listeners and emits events via the `dispatch` method

## Setup

```bash
composer require contributte/event-dispatcher
```

```neon
extensions:
    events: Contributte\EventDispatcher\DI\EventDispatcherExtension
```

The extension automatically discovers all services implementing `Symfony\Component\EventDispatcher\EventSubscriberInterface` and registers them with the event dispatcher. That's all you need to get started.

## Configuration

### Minimal configuration

```neon
extensions:
    events: Contributte\EventDispatcher\DI\EventDispatcherExtension
```

### Full configuration

```neon
extensions:
    events: Contributte\EventDispatcher\DI\EventDispatcherExtension

events:
    lazy: true
    autoload: true
    debug:
        panel: %debugMode%
        deep: 4
    loggers:
        - @App\Logging\EventLogger

services:
    - App\Logging\EventLogger
    - App\Subscribers\OrderSubscriber
    - App\Subscribers\UserSubscriber
```

### Configuration options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `lazy` | bool | `true` | Enable lazy-loading of subscribers (instantiated only when needed) |
| `autoload` | bool | `true` | Automatically discover and register all subscribers |
| `debug.panel` | bool | `false` | Enable Tracy debug panel for event inspection |
| `debug.deep` | int\|null | `null` | Maximum depth for dumping event data in Tracy panel |
| `loggers` | array | `[]` | PSR-3 loggers for event tracking |

---

# Subscribers

## Creating a subscriber

A subscriber is a class that implements `EventSubscriberInterface` and defines which events it listens to via the static `getSubscribedEvents()` method.

```php
namespace App\Subscribers;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class OrderLoggerSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private Logger $logger,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OrderCreatedEvent::class => 'onOrderCreated',
            OrderPaidEvent::class => 'onOrderPaid',
        ];
    }

    public function onOrderCreated(OrderCreatedEvent $event): void
    {
        $this->logger->info('Order created', ['orderId' => $event->getOrder()->getId()]);
    }

    public function onOrderPaid(OrderPaidEvent $event): void
    {
        $this->logger->info('Order paid', ['orderId' => $event->getOrder()->getId()]);
    }

}
```

Register the subscriber as a service:

```neon
services:
    - App\Subscribers\OrderLoggerSubscriber
```

> [!NOTE]
> With `autoload: true` (default), you only need to register the service. The extension automatically detects `EventSubscriberInterface` implementations.

## Event listener formats

The `getSubscribedEvents()` method supports several formats for mapping events to listeners:

### Simple method mapping

```php
public static function getSubscribedEvents(): array
{
    return [
        'order.created' => 'onOrderCreated',
        OrderPaidEvent::class => 'onOrderPaid',
    ];
}
```

### Method with priority

```php
public static function getSubscribedEvents(): array
{
    return [
        'order.created' => ['onOrderCreated', 10],
    ];
}
```

### Multiple listeners for one event

```php
public static function getSubscribedEvents(): array
{
    return [
        'order.created' => [
            ['sendConfirmationEmail', 10],
            ['notifyWarehouse', 5],
            ['updateStatistics', 0],
        ],
    ];
}
```

## Prioritized listeners

Listeners with higher priority are executed first. Default priority is `0`.

```php
public static function getSubscribedEvents(): array
{
    return [
        OrderCreatedEvent::class => [
            ['validateOrder', 100],      // Runs first
            ['reserveInventory', 50],    // Runs second
            ['sendNotification', 0],     // Runs last
        ],
    ];
}
```

---

# Events

## Creating events

Events are simple value objects that carry data about something that happened. Extend Symfony's base `Event` class:

```php
namespace App\Events;

use Symfony\Contracts\EventDispatcher\Event;

final class OrderCreatedEvent extends Event
{

    public function __construct(
        private Order $order,
    )
    {
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

}
```

### Stoppable events

If you need to prevent subsequent listeners from being called, implement `StoppableEventInterface`:

```php
namespace App\Events;

use Psr\EventDispatcher\StoppableEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class OrderValidationEvent extends Event implements StoppableEventInterface
{

    private bool $propagationStopped = false;

    public function __construct(
        private Order $order,
        private array $errors = [],
    )
    {
    }

    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

}
```

## Dispatching events

Inject `EventDispatcherInterface` into your service and use `dispatch()` to emit events:

```php
namespace App\Model;

use App\Events\OrderCreatedEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class OrderFacade
{

    public function __construct(
        private OrderRepository $orderRepository,
        private EventDispatcherInterface $eventDispatcher,
    )
    {
    }

    public function createOrder(OrderData $data): Order
    {
        $order = new Order($data);
        $this->orderRepository->save($order);

        // Dispatch event - all registered listeners will be notified
        $this->eventDispatcher->dispatch(new OrderCreatedEvent($order));

        return $order;
    }

}
```

### Dispatching with explicit event name

You can optionally provide an event name as the second argument:

```php
$this->eventDispatcher->dispatch($event, 'custom.event.name');
```

This allows multiple event types to share the same event class while being handled by different listeners.

---

# Advanced

## Lazy-loading

By default, lazy-loading is enabled (`lazy: true`). This means subscriber services are not instantiated until their events are actually dispatched.

**How it works:**

1. During compilation, the extension analyzes each subscriber's `getSubscribedEvents()` method
2. Instead of registering the subscriber directly, it creates lightweight `LazyListener` wrappers
3. When an event is dispatched, only the relevant subscriber is instantiated

**Benefits:**

- Improved application startup performance
- Reduced memory usage when not all events are dispatched
- Dependencies are only resolved when needed

```neon
events:
    lazy: true    # Default - subscribers instantiated on-demand
    lazy: false   # All subscribers instantiated at registration
```

> [!TIP]
> Keep lazy-loading enabled in production. Consider disabling it only for debugging subscriber registration issues.

## Debugging with Tracy

Enable the Tracy debug panel to inspect dispatched events and registered listeners:

```neon
events:
    debug:
        panel: %debugMode%
        deep: 4
```

The panel displays:

- **Event count** - Total number of dispatched events
- **Handled events** - Events that had at least one listener
- **Total time** - Cumulative time spent dispatching events
- **Event details** - Event class, data, and execution time
- **Registered listeners** - All listeners organized by event name

### Configuration options

| Option | Description |
|--------|-------------|
| `panel` | Enable/disable the Tracy panel (recommended: `%debugMode%`) |
| `deep` | Maximum depth for dumping event objects (`null` = Tracy default) |

![Tracy Panel](https://raw.githubusercontent.com/contributte/event-dispatcher/master/.docs/assets/tracy-panel.png)

## Logging

Track all dispatched events using PSR-3 compatible loggers:

```neon
events:
    loggers:
        - App\Logging\EventLogger
```

### Example logger implementation

```php
namespace App\Logging;

use Psr\Log\AbstractLogger;

final class EventLogger extends AbstractLogger
{

    public function __construct(
        private string $logFile,
    )
    {
    }

    public function log($level, $message, array $context = []): void
    {
        $line = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            json_encode($context)
        );

        file_put_contents($this->logFile, $line, FILE_APPEND);
    }

}
```

```neon
services:
    - App\Logging\EventLogger(%logDir%/events.log)

events:
    loggers:
        - @App\Logging\EventLogger
```

### Log output

The logger receives messages in this format:

```
EventDispatcher@App\Events\OrderCreatedEvent: event started
EventDispatcher@App\Events\OrderCreatedEvent: event dispatched
```

The context includes an `EventTrace` object with:

- `event` - The dispatched event object
- `name` - Event name/class
- `handled` - Whether any listeners were called
- `duration` - Execution time in seconds

### Multiple loggers

You can configure multiple loggers for different purposes:

```neon
events:
    loggers:
        - @App\Logging\FileLogger
        - @App\Logging\DatabaseLogger
        - @App\Logging\SlackNotifier
```

---

# Testing

## Testing subscribers

Test your subscribers by creating them directly and calling their methods:

```php
use App\Events\OrderCreatedEvent;
use App\Subscribers\OrderLoggerSubscriber;
use Mockery;
use PHPUnit\Framework\TestCase;

final class OrderLoggerSubscriberTest extends TestCase
{

    public function testOnOrderCreated(): void
    {
        $logger = Mockery::mock(Logger::class);
        $logger->shouldReceive('info')
            ->once()
            ->with('Order created', ['orderId' => 123]);

        $subscriber = new OrderLoggerSubscriber($logger);
        $order = new Order(123);

        $subscriber->onOrderCreated(new OrderCreatedEvent($order));
    }

}
```

## Testing event dispatching

Use Nette Tester or PHPUnit with a test container:

```php
use Contributte\Tester\Utils\ContainerBuilder;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tester\Assert;
use Tester\TestCase;

final class OrderFacadeTest extends TestCase
{

    public function testCreateOrderDispatchesEvent(): void
    {
        $container = ContainerBuilder::of()
            ->withCompiler(function ($compiler) {
                $compiler->addConfig(__DIR__ . '/config.test.neon');
            })
            ->build();

        $dispatcher = $container->getByType(EventDispatcherInterface::class);
        $facade = $container->getByType(OrderFacade::class);

        // Track dispatched events
        $dispatchedEvents = [];
        $dispatcher->addListener(OrderCreatedEvent::class, function ($event) use (&$dispatchedEvents) {
            $dispatchedEvents[] = $event;
        });

        $order = $facade->createOrder(new OrderData('Test'));

        Assert::count(1, $dispatchedEvents);
        Assert::type(OrderCreatedEvent::class, $dispatchedEvents[0]);
        Assert::same($order, $dispatchedEvents[0]->getOrder());
    }

}
```

## Testing with mock dispatcher

For unit tests, inject a mock dispatcher:

```php
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class OrderFacadeTest extends TestCase
{

    public function testCreateOrderDispatchesEvent(): void
    {
        $dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(OrderCreatedEvent::class));

        $repository = Mockery::mock(OrderRepository::class);
        $repository->shouldReceive('save')->once();

        $facade = new OrderFacade($repository, $dispatcher);
        $facade->createOrder(new OrderData('Test'));
    }

}
```

---

# Extra

This library provides a minimal, pure integration of [Symfony EventDispatcher](https://github.com/symfony/event-dispatcher) into [Nette Framework](https://github.com/nette/). It contains only the essential DI extension and supporting classes.

For additional pre-built events for Nette components, check out the [`contributte/event-dispatcher-extra`](https://github.com/contributte/event-dispatcher-extra) package, which provides:

- **Application events** - `StartupEvent`, `RequestEvent`, `PresenterEvent`, `ResponseEvent`, `ErrorEvent`, `ShutdownEvent`
- **Latte events** - Events for template compilation and rendering
- **Security events** - Login/logout events

```bash
composer require contributte/event-dispatcher-extra
```

> See [Symfony EventDispatcher Documentation](https://symfony.com/doc/current/components/event_dispatcher.html) for comprehensive information about the underlying component.
