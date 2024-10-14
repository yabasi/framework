<?php

namespace Yabasi\Validation;

use Yabasi\Config\Config;

class ValidationRuleLoader
{
    protected array $rules = [];

    public function __construct(Config $config)
    {
        $this->rules = $config->get('validation', []);
    }

    public function getRules(string $model, string $action): array
    {
        return $this->rules[$model][$action] ?? [];
    }
}