<?php

namespace Yabasi\Events;

use Closure;

class EventDispatcher
{
    protected array $listeners = [];

    public function listen(string $eventName, $listener): void
    {
        $this->listeners[$eventName][] = $listener;
    }

    public function dispatch(Event $event): void
    {
        $eventName = $event->getName();
        if (isset($this->listeners[$eventName])) {
            foreach ($this->listeners[$eventName] as $listener) {
                if ($listener instanceof Closure) {
                    $listener($event);
                } elseif (is_array($listener) && count($listener) == 2) {
                    [$class, $method] = $listener;
                    if (is_string($class)) {
                        $class = new $class();
                    }
                    $class->$method($event);
                }
            }
        }
    }

    public function hasListeners(string $eventName): bool
    {
        return !empty($this->listeners[$eventName]);
    }
}