<?php

namespace Yabasi\Http;

class Request
{
    protected $get;
    protected $post;
    protected $server;
    protected $files;
    protected $cookies;
    protected $headers;
    protected $content;
    protected $rawContent;
    protected $attributes = [];
    protected $jsonData;

    public function __construct()
    {
        $this->get      = $_GET;
        $this->post     = $_POST;
        $this->server   = $_SERVER;
        $this->files    = $_FILES;
        $this->cookies  = $_COOKIE;
        $this->headers  = $this->getallheaders();
        $this->parseJsonContent();
    }

    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public function isMethod($method): bool
    {
        return $this->getMethod() === strtoupper($method);
    }

    public function getUri()
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    public function getQueryString()
    {
        return $this->server['QUERY_STRING'] ?? '';
    }

    public function get($key, $default = null)
    {
        return $this->get[$key] ?? $default;
    }

    public function post($key, $default = null)
    {
        if ($this->jsonData !== null) {
            return $this->jsonData[$key] ?? $default;
        }
        return $this->post[$key] ?? $default;
    }

    public function input($key, $default = null)
    {
        return $this->jsonData[$key] ?? $this->post[$key] ?? $this->get[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    public function only($keys): array
    {
        return array_intersect_key($this->all(), array_flip((array) $keys));
    }

    public function except($keys): array
    {
        return array_diff_key($this->all(), array_flip((array) $keys));
    }

    public function has($key): bool
    {
        return isset($this->all()[$key]);
    }

    public function hasAny($keys): bool
    {
        foreach ((array) $keys as $key) {
            if ($this->has($key)) {
                return true;
            }
        }
        return false;
    }

    public function filled($key): bool
    {
        $value = $this->input($key);
        return $value !== null && $value !== '';
    }

    public function file($key)
    {
        return $this->files[$key] ?? null;
    }

    public function hasFile($key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] !== UPLOAD_ERR_NO_FILE;
    }

    public function cookie($key, $default = null)
    {
        return $this->cookies[$key] ?? $default;
    }

    public function header($key, $default = null)
    {
        return $this->headers[$key] ?? $default;
    }

    public function bearerToken()
    {
        $header = $this->header('Authorization', '');
        if (strncmp($header, 'Bearer ', 7) === 0) {
            return substr($header, 7);
        }
    }

    public function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public function userAgent()
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    public function isSecure(): bool
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') || $this->server['SERVER_PORT'] == 443;
    }

    protected function parseJsonContent(): void
    {
        $contentType = $this->getHeader('Content-Type', '');
        if (stripos($contentType, 'application/json') !== false) {
            $content = $this->getRawContent();
            $this->jsonData = json_decode($content, true) ?? [];
        }
    }

    public function getContent()
    {
        if ($this->content === null) {
            $rawContent = $this->getRawContent();
            if (!empty($rawContent)) {
                $this->content = json_decode($rawContent, true) ?? [];
            } else {
                $this->content = [];
            }
        }
        return $this->content;
    }

    public function getRawContent(): string
    {
        if ($this->rawContent === null) {
            $this->rawContent = file_get_contents('php://input') ?: '';
        }
        return $this->rawContent;
    }

    protected function getallheaders(): array
    {
        $headers = [];
        foreach ($this->server as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

    public function setAttribute($key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute($key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader($name, $default = null)
    {
        return $this->headers[$name] ?? $default;
    }

    public function getJsonData()
    {
        return $this->jsonData;
    }

    public function sendRequest($method, $url, $options = [])
    {
        $curl = curl_init();

        $headers = $options['headers'] ?? [];
        $body = $options['body'] ?? null;

        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
        ];

        if (!empty($headers)) {
            $curlOptions[CURLOPT_HTTPHEADER] = array_map(
                fn($key, $value) => "$key: $value",
                array_keys($headers),
                $headers
            );
        }

        if ($body !== null) {
            $curlOptions[CURLOPT_POSTFIELDS] = is_array($body) ? http_build_query($body) : $body;
        }

        curl_setopt_array($curl, $curlOptions);

        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        return [
            'body' => $response,
            'statusCode' => $statusCode
        ];
    }
}