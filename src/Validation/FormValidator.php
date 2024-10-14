<?php

namespace Yabasi\Validation;

use Yabasi\Http\Request;
use Yabasi\Localization\Translator;

class FormValidator
{
    protected $request;
    protected $translator;
    protected $rules = [];
    protected $messages = [];
    protected $attributes = [];
    protected $errors = [];
    protected $customRules = [];

    public function __construct(Request $request, Translator $translator)
    {
        $this->request = $request;
        $this->translator = $translator;
    }

    public function validate(array $rules, array $messages = [], array $attributes = []): bool
    {
        $this->rules = $rules;
        $this->messages = $messages;
        $this->attributes = $attributes;

        foreach ($this->rules as $field => $fieldRules) {
            $this->validateField($field, $fieldRules);
        }

        return empty($this->errors);
    }

    protected function validateField($field, $fieldRules)
    {
        $value = $this->request->input($field);
        $rules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

        foreach ($rules as $rule) {
            $this->applyRule($field, $value, $rule);
        }
    }

    protected function applyRule($field, $value, $rule)
    {
        $ruleParts = explode(':', $rule);
        $ruleName = $ruleParts[0];
        $ruleParams = isset($ruleParts[1]) ? explode(',', $ruleParts[1]) : [];

        $method = 'validate' . ucfirst($ruleName);

        if (method_exists($this, $method)) {
            if (!$this->$method($field, $value, $ruleParams)) {
                $this->addError($field, $ruleName);
            }
        } elseif (isset($this->customRules[$ruleName])) {
            if (!call_user_func($this->customRules[$ruleName], $field, $value, $ruleParams, $this)) {
                $this->addError($field, $ruleName);
            }
        } else {
            throw new \InvalidArgumentException("Validation rule [$ruleName] does not exist.");
        }
    }

    protected function addError($field, $rule)
    {
        $message = $this->getMessage($field, $rule);
        $this->errors[$field][] = $this->replacePlaceholders($message, $field, $rule);
    }

    protected function getMessage($field, $rule)
    {
        $customMessage = $this->messages[$field . '.' . $rule] ?? $this->messages[$rule] ?? null;

        if ($customMessage) {
            return $customMessage;
        }

        return $this->translator->get('validation.' . $rule);
    }

    protected function replacePlaceholders($message, $field, $rule)
    {
        $attribute = $this->attributes[$field] ?? $field;
        return str_replace([':attribute', ':rule'], [$attribute, $rule], $message);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function addRule($name, callable $callback)
    {
        $this->customRules[$name] = $callback;
    }

    // Temel doğrulama kuralları
    protected function validateRequired($field, $value): bool
    {
        return $value !== null && $value !== '';
    }

    protected function validateEmail($field, $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateMin($field, $value, $params): bool
    {
        $min = $params[0];
        return strlen($value) >= $min;
    }

    protected function validateMax($field, $value, $params): bool
    {
        $max = $params[0];
        return strlen($value) <= $max;
    }

    protected function validateNumeric($field, $value): bool
    {
        return is_numeric($value);
    }

}