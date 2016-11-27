<?php

namespace Contributte\EventDispatcher\Events\Application;

use Contributte\EventDispatcher\Events\BaseEvent;
use Nette\Application\Application;
use Nette\Application\IResponse;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
final class ResponseEvent extends BaseEvent
{

    /** @var Application */
    private $application;

    /** @var IResponse */
    private $response;

    /**
     * @param Application $application
     * @param IResponse $response
     */
    public function __construct(Application $application, IResponse $response)
    {
        $this->application = $application;
        $this->response = $response;
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @return IResponse
     */
    public function getResponse()
    {
        return $this->response;
    }

}
