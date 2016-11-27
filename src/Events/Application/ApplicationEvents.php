<?php

namespace Contributte\EventDispatcher\Events\Application;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
interface ApplicationEvents
{

    /**
     * Occurs before the application loads presenter
     */
    const ON_STARTUP = 'nette.application.startup';

    /**
     * Occurs before the application shuts down
     */
    const ON_SHUTDOWN = 'nette.application.shutdown';

    /**
     * Occurs when a new request is ready for dispatch;
     */
    const ON_REQUEST = 'nette.application.request';

    /**
     * Occurs when a presenter is created
     */
    const ON_PRESENTER = 'nette.application.presenter';

    /**
     * Occurs when a new response is received
     */
    const ON_RESPONSE = 'nette.application.response';

    /**
     * Occurs when an unhandled exception occurs in the application
     */
    const ON_ERROR = 'nette.application.error';

}
