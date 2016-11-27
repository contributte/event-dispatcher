<?php

/**
 * Test: DI\EventDispatcherExtensions
 */

use Contributte\EventDispatcher\DI\EventDispatcherExtensions;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tester\Assert;
use Tester\FileMock;
use Tests\Fixtures\FooSubscriber;

require_once __DIR__ . '/../bootstrap.php';

test(function () {
    $loader = new ContainerLoader(TEMP_DIR, TRUE);
    $class = $loader->load(function (Compiler $compiler) {
        $compiler->addExtension('events', new EventDispatcherExtensions());
        $compiler->loadConfig(FileMock::create('
        services:
            foo: Tests\Fixtures\FooSubscriber
', 'neon'));
    }, 1);

    /** @var Container $container */
    $container = new $class;

    /** @var EventDispatcherInterface $em */
    $em = $container->getByType(EventDispatcherInterface::class);

    // Subscriber is not created
    Assert::false($container->isCreated('foo'));

    // Dispatch event
    $em->dispatch('baz.baz', new Event());

    // Subscriber is still not created
    Assert::false($container->isCreated('foo'));

    /** @var FooSubscriber $subscriber */
    $subscriber = $container->getByType(FooSubscriber::class);
    Assert::equal([], $subscriber->onCall);
});


test(function () {
    $loader = new ContainerLoader(TEMP_DIR, TRUE);
    $class = $loader->load(function (Compiler $compiler) {
        $compiler->addExtension('events', new EventDispatcherExtensions());
        $compiler->loadConfig(FileMock::create('
        services:
            foo: Tests\Fixtures\FooSubscriber
', 'neon'));
    }, 2);

    /** @var Container $container */
    $container = new $class;

    /** @var EventDispatcherInterface $em */
    $em = $container->getByType(EventDispatcherInterface::class);

    // Subscriber is not created
    Assert::false($container->isCreated('foo'));

    // Dispatch event
    $event = new Event();
    $em->dispatch('foobar', $event);

    // Subscriber is already created
    Assert::true($container->isCreated('foo'));

    /** @var FooSubscriber $subscriber */
    $subscriber = $container->getByType(FooSubscriber::class);
    Assert::equal([$event], $subscriber->onCall);
});
