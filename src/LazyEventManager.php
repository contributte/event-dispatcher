<?php

namespace Contributte\EventDispatcher;

use Contributte\EventDispatcher\Exceptions\Logical\InvalidStateException;
use Nette\DI\Container;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
class LazyEventManager extends EventManager
{

    /** @var Container */
    private $container;

    /** @var array */
    private $services = [];

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string|NULL $eventName
     * @return array
     */
    public function getListeners($eventName = NULL)
    {
        if (isset($this->services[$eventName])) {
            foreach ($this->services[$eventName] as $serviceName) {
                // Obtain service from container
                $listener = $this->container->getService($serviceName);

                // Just for sure, validate type
                if ($listener instanceof EventSubscriber) {
                    $this->addSubscriber($listener);
                } else {
                    throw new InvalidStateException('Unsupported type of subscriber');
                }
            }

            // Unset already attached listeners
            unset($this->services[$eventName]);
        }

        return parent::getListeners($eventName);
    }


    /**
     * @param string $eventName
     * @param string $serviceName
     * @return void
     */
    public function addSubscriberLazy($eventName, $serviceName)
    {
        if (empty($this->services[$eventName])) {
            $this->services[$eventName] = [];
        }

        $this->services[$eventName][] = $serviceName;
    }

}
