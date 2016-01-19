<?php

namespace Scarf\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;

class Handler implements ExceptionHandler
{
    /**
     * Class constructor.
     *
     * @param Scarf\Scarf $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Report or log an exception.
     *
     * @param \Exception $e
     */
    public function report(Exception $e)
    {
        $this->app->make('Psr\Log\LoggerInterface')->error($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception               $e
     *
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        $data = [
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
        ];

        if ($this->app->make('config')->get('app.debug')) {
            $data['trace'] = $e->getTrace();
        }

        return $this->app->make('response')
            ->setStatusCode(500)
            ->setData($data);
    }

    /**
     * Render an exception to the console.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \Exception                                        $e
     */
    public function renderForConsole($output, Exception $e)
    {
    }
}
