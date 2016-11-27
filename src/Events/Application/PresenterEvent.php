<?php

namespace Contributte\EventDispatcher\Events\Application;

use Contributte\EventDispatcher\Events\BaseEvent;
use Nette\Application\Application;
use Nette\Application\IPresenter;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
final class PresenterEvent extends BaseEvent
{

    /** @var Application */
    private $application;

    /** @var IPresenter */
    private $presenter;

    /**
     * @param Application $application
     * @param IPresenter $presenter
     */
    public function __construct(Application $application, IPresenter $presenter)
    {
        $this->application = $application;
        $this->presenter = $presenter;
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @return IPresenter
     */
    public function getPresenter()
    {
        return $this->presenter;
    }

}
