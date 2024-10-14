<?php

namespace Yabasi\Http;

use Yabasi\Validation\ValidationRuleLoader;
use Yabasi\Validation\Validator;

abstract class FormRequest extends Request
{
    protected $validator;
    protected $validationRuleLoader;

    public function __construct(Request $request, Validator $validator, ValidationRuleLoader $validationRuleLoader = null)
    {
        parent::__construct();
        $this->validator = $validator;
        $this->validationRuleLoader = $validationRuleLoader;
        $this->setRequestData($request);
    }

    protected function setRequestData(Request $request)
    {
        $this->method = $request->getMethod();
        $this->get = $request->get;
        $this->post = $request->post;
        $this->server = $request->server;
        $this->files = $request->files;
        $this->cookies = $request->cookies;
        $this->headers = $request->headers;
    }

    abstract public function rules(): array;

    public function validate(): bool
    {
        return $this->validator->make($this->all(), $this->rules(), $this->messages());
    }

    public function messages(): array
    {
        return [];
    }

    public function errors(): array
    {
        return $this->validator->errors();
    }
}