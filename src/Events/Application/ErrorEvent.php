<?php

namespace Contributte\EventDispatcher\Events\Application;

use Contributte\EventDispatcher\Events\BaseEvent;
use Contributte\EventDispatcher\Exceptions\Logical\InvalidArgumentException;
use Error;
use Exception;
use Nette\Application\Application;
use Throwable;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
final class ErrorEvent extends BaseEvent
{

    /** @var Application */
    private $application;

    /** @var Exception|Error|Throwable */
    private $throwable;

    /**
     * @param Application $application
     * @param Exception|Error $throwable
     */
    public function __construct(Application $application, $throwable)
    {
        if (!($throwable instanceof Exception) && !($throwable instanceof Error)) {
            throw new InvalidArgumentException(sprintf('Exception must be instance of Exception|Error'));
        }

        $this->application = $application;
        $this->throwable = $throwable;
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @return Error|Exception
     */
    public function getThrowable()
    {
        return $this->throwable;
    }

}
