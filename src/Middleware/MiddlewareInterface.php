<?php

namespace Yabasi\Middleware;

use Closure;
use Yabasi\Http\Request;
use Yabasi\Http\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response;
}