<?php

namespace Core\Handler\Contracts;

use Exception;

interface ExceptionHandler
{
    /**
     * Handle an exception.
     *
     * @param Exception $e
     */
    public function handle(Exception $e);
}
