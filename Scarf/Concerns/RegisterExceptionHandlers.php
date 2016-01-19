<?php

namespace Scarf\Concerns;

use Error;
use Exception;
use ErrorException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

trait RegisterExceptionHandlers
{
    /**
     * Set the error handling for the application.
     */
    protected function registerErrorHandling()
    {
        error_reporting(-1);

        set_error_handler(function ($level, $message, $file = '', $line = 0) {
            if (error_reporting() & $level) {
                throw new ErrorException($message, 0, $level, $file, $line);
            }
        });

        set_exception_handler(function ($e) {
            $this->handleUncaughtException($e);
        });

        register_shutdown_function(function () {
            $this->handleShutdown();
        });
    }

    /**
     * Handle the application shutdown routine.
     */
    protected function handleShutdown()
    {
        if (!is_null($error = error_get_last()) and $this->isFatalError($error['type'])) {
            $this->handleUncaughtException(new ErrorException(
                $error['message'], $error['type'], 0, $error['file'], $error['line']
            ));
        }
    }

    /**
     * Determine if the error type is fatal.
     *
     * @param int $type
     *
     * @return bool
     */
    protected function isFatalError($type)
    {
        $errorCodes = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE];

        if (defined('FATAL_ERROR')) {
            $errorCodes[] = FATAL_ERROR;
        }

        return in_array($type, $errorCodes);
    }

    /**
     * Handle an uncaught exception instance.
     *
     * @param \Throwable $e
     */
    protected function handleUncaughtException($e)
    {
        $handler = $this->resolveExceptionHandler();

        if ($e instanceof Error) {
            $e = new ErrorException($e);
        }

        $handler->report($e);

        $handler->render(null, $e)->send();
    }

    /**
     * Get the exception handler from the container.
     *
     * @return mixed
     */
    protected function resolveExceptionHandler()
    {
        return $this->make('Illuminate\Contracts\Debug\ExceptionHandler');
    }

    /**
     * Get the Monolog handler for the application.
     *
     * @return \Monolog\Handler\AbstractHandler
     */
    protected function getMonologHandler()
    {
        return (new StreamHandler($this->make('config')->get('app.log_path'), Logger::DEBUG))
                            ->setFormatter(new LineFormatter(null, null, true, true));
    }
}
