<?php

namespace Yabasi\Logging;

use Exception;

interface ExceptionHandlerInterface
{
    public function handle(Exception $exception): void;
}
