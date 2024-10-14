<?php

namespace Yabasi\Validation;

use InvalidArgumentException;
use Yabasi\Database\Connection;
use Yabasi\Localization\Translator;

class Validator
{
    protected array $data = [];
    protected array $rules = [];
    protected array $messages = [];
    protected array $attributes = [];
    protected array $errors = [];
    protected array $customRules = [];
    protected $stopOnFirstFailure = false;
    protected $connection;

    public function __construct(protected Translator $translator, Connection $connection)
    {
        $this->connection = $connection;
        $this->registerBaseRules();
    }

    public function make(array $data, array $rules, array $messages = [], array $attributes = []): bool
    {
        $this->data = $data;
        $this->rules = $this->parseRules($rules);
        $this->messages = $messages;
        $this->attributes = $attributes;

        foreach ($this->rules as $attribute => $rules) {
            foreach ($rules as $rule) {
                if (!$this->validateAttribute($attribute, $rule)) {
                    if ($this->stopOnFirstFailure) {
                        return false;
                    }
                }
            }
        }

        return empty($this->errors);
    }

    protected function validateAttribute($attribute, $rule): bool
    {
        [$rule, $parameters] = $this->parseRule($rule);

        $value = $this->getValue($attribute);

        if ($this->hasConditionalRule($attribute, $rule, $parameters)) {
            return true;
        }

        $method = 'validate' . ucfirst($rule);

        if (method_exists($this, $method)) {
            $result = $this->$method($attribute, $value, $parameters);
        } elseif (isset($this->customRules[$rule])) {
            $result = $this->validateUsingCustomRule($attribute, $value, $rule, $parameters);
        } else {
            throw new InvalidArgumentException("Validation rule [$rule] does not exist.");
        }

        if (!$result) {
            $this->addError($attribute, $rule, $parameters);
        }

        return $result;
    }

    protected function parseRules(array $rules): array
    {
        foreach ($rules as $key => $rule) {
            $rules[$key] = is_string($rule) ? explode('|', $rule) : $rule;
        }

        return $rules;
    }

    protected function parseRule($rule): array
    {
        if (is_string($rule)) {
            return $this->parseStringRule($rule);
        }

        return [$rule, []];
    }

    protected function parseStringRule($rule): array
    {
        $parameters = [];

        if (strpos($rule, ':') !== false) {
            [$rule, $parameter] = explode(':', $rule, 2);
            $parameters = $this->parseParameters($parameter);
        }

        return [strtolower($rule), $parameters];
    }

    protected function parseParameters($parameter): array
    {
        return str_getcsv($parameter);
    }

    protected function getValue($attribute)
    {
        return $this->data[$attribute] ?? null;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    protected function addError($attribute, $rule, $parameters = []): void
    {
        $message = $this->getMessage($attribute, $rule, $parameters);
        $this->errors[$attribute][] = $this->replacePlaceholders($message, $attribute, $rule, $parameters);
    }

    protected function getMessage($attribute, $rule, $parameters): string
    {
        $customMessage = $this->getCustomMessage($attribute, $rule);

        if ($customMessage) {
            return $customMessage;
        }

        $key = "validation.{$rule}";
        if ($rule === 'min' || $rule === 'max') {
            $key .= '.string';
        }

        $message = $this->translator->get($key);

        // Eğer çeviri bulunamazsa, varsayılan bir mesaj kullanalım
        if ($message === $key) {
            $message = "The {$attribute} is invalid.";
        }

        return $this->replaceAttributePlaceholder($message, $attribute);
    }

    protected function replaceAttributePlaceholder($message, $attribute): string
    {
        return str_replace(':attribute', $this->getAttributeName($attribute), $message);
    }

    protected function getCustomMessage($attribute, $rule): ?string
    {
        return $this->messages["{$attribute}.{$rule}"]
            ?? $this->messages[$rule]
            ?? null;
    }

    protected function replacePlaceholders($message, $attribute, $rule, $parameters): string
    {
        $message = str_replace(':attribute', $this->getAttributeName($attribute), $message);

        if (method_exists($this, $replacer = "replace{$rule}")) {
            $message = $this->$replacer($message, $attribute, $rule, $parameters);
        }

        return $message;
    }

    protected function getAttributeName($attribute): string
    {
        $customNames = $this->translator->get('validation.attributes', []);
        return $customNames[$attribute] ?? str_replace('_', ' ', $attribute);
    }

    public function addRule($name, callable $callback): void
    {
        $this->customRules[$name] = $callback;
    }

    protected function validateUsingCustomRule($attribute, $value, $rule, $parameters): bool
    {
        return call_user_func_array($this->customRules[$rule], [$attribute, $value, $parameters, $this]);
    }

    protected function registerBaseRules(): void
    {
        $this->addRule('required', function ($attribute, $value) {
            return $value !== null && $value !== '';
        });

        $this->addRule('email', function ($attribute, $value) {
            return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        });

        $this->addRule('min', function ($attribute, $value, $parameters) {
            $length = is_numeric($value) ? $value : mb_strlen($value);
            return $length >= $parameters[0];
        });

        $this->addRule('max', function ($attribute, $value, $parameters) {
            $length = is_numeric($value) ? $value : mb_strlen($value);
            return $length <= $parameters[0];
        });

        $this->addRule('between', function ($attribute, $value, $parameters) {
            $length = is_numeric($value) ? $value : mb_strlen($value);
            return $length >= $parameters[0] && $length <= $parameters[1];
        });

        $this->addRule('numeric', function ($attribute, $value) {
            return is_numeric($value);
        });

        $this->addRule('integer', function ($attribute, $value) {
            return filter_var($value, FILTER_VALIDATE_INT) !== false;
        });

        $this->addRule('string', function ($attribute, $value) {
            return is_string($value);
        });

        $this->addRule('array', function ($attribute, $value) {
            return is_array($value);
        });

        $this->addRule('boolean', function ($attribute, $value) {
            return in_array($value, [true, false, 0, 1, '0', '1'], true);
        });

        $this->addRule('date', function ($attribute, $value) {
            return strtotime($value) !== false;
        });

        $this->addRule('confirmed', function ($attribute, $value, $parameters) {
            $confirmationField = $attribute . '_confirmation';
            return isset($this->data[$confirmationField]) && $value === $this->data[$confirmationField];
        });

        $this->addRule('same', function ($attribute, $value, $parameters) {
            return $value === $this->getValue($parameters[0]);
        });

        $this->addRule('different', function ($attribute, $value, $parameters) {
            return $value !== $this->getValue($parameters[0]);
        });

        $this->addRule('in', function ($attribute, $value, $parameters) {
            return in_array($value, $parameters);
        });

        $this->addRule('not_in', function ($attribute, $value, $parameters) {
            return !in_array($value, $parameters);
        });

        $this->addRule('regex', function ($attribute, $value, $parameters) {
            return preg_match($parameters[0], $value);
        });

        $this->addRule('unique', function ($attribute, $value, $parameters) {
            $table = $parameters[0];
            $column = $parameters[1] ?? $attribute;
            $except = $parameters[2] ?? null;

            $query = $this->connection->query()->from($table)->where($column, '=', $value);

            if ($except !== null) {
                $query->where('id', '!=', $except);
            }

            return $query->count() === 0;
        });
    }

    public function sometimes(string $attribute, $rules, callable $callback): void
    {
        $data = $this->data;

        if (call_user_func($callback, $data)) {
            $this->rules[$attribute] = $this->mergeRules($this->rules[$attribute] ?? [], $rules);
        }
    }

    protected function mergeRules($existingRules, $newRules): array
    {
        return array_merge(
            is_string($existingRules) ? explode('|', $existingRules) : $existingRules,
            is_string($newRules) ? explode('|', $newRules) : $newRules
        );
    }

    protected function hasConditionalRule(string $attribute, string $rule, array $parameters): bool
    {
        return isset($this->conditionalRules[$attribute])
            && !call_user_func($this->conditionalRules[$attribute], $this->data);
    }

    public function stopOnFirstFailure($stop = true): self
    {
        $this->stopOnFirstFailure = $stop;
        return $this;
    }
}