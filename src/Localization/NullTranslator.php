<?php

namespace Yabasi\Localization;

class NullTranslator
{
    public function get($key, $replacements = [])
    {
        return $key;
    }
}