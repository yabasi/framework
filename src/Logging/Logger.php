<?php

namespace Yabasi\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Yabasi\Config\Config;

class Logger implements LoggerInterface
{
    protected $logFile;

    public function __construct(Config $config, string $type = 'app')
    {
        $this->logFile = $config->get("logging.{$type}", $config->get('logging.file'));
        $this->ensureLogFileExists();
    }

    protected function ensureLogFileExists(): void
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0755, true) && !is_dir($logDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $logDir));
            }
        }

        if (!file_exists($this->logFile)) {
            if (file_put_contents($this->logFile, '') === false) {
                throw new \RuntimeException(sprintf('Unable to create log file "%s"', $this->logFile));
            }
        }

        if (!is_writable($this->logFile)) {
            throw new \RuntimeException(sprintf('Log file "%s" is not writable', $this->logFile));
        }
    }

    public function log($level, $message, array $context = []): void
    {
        $date = date('Y-m-d H:i:s');
        $formattedMessage = $this->formatMessage($message, $context);

        $logMessage = "[{$date}] [{$level}] - {$formattedMessage}" . PHP_EOL;

        if (file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX) === false) {
            throw new \RuntimeException(sprintf('Unable to write to log file "%s"', $this->logFile));
        }
    }

    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    protected function sanitizeMessage($message): array|string
    {
        return str_replace(["\r", "\n"], ' ', $message);
    }

    protected function formatMessage($message, array $context = []): string
    {
        if (!empty($context)) {
            $replace = [];
            foreach ($context as $key => $val) {
                if (is_array($val) || is_object($val)) {
                    $val = json_encode($val);
                }
                $replace['{' . $key . '}'] = $val;
            }
            $message .= ' ' . strtr(json_encode($replace), ['"' => '']);
        }
        return $this->interpolate($message, $context);
    }

    protected function interpolate($message, array $context = []): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (is_array($val) || is_object($val)) {
                $val = json_encode($val);
            } elseif (!is_string($val) && !is_numeric($val)) {
                $val = var_export($val, true);
            }
            $replace['{' . $key . '}'] = $val;
        }
        return strtr($message, $replace);
    }
}