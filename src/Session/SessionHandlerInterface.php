<?php

namespace Yabasi\Session;

interface SessionHandlerInterface extends \SessionHandlerInterface
{
    public function gc($max_lifetime): int|false;
}