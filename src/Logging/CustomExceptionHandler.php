<?php

namespace Yabasi\Logging;

use Exception;

class CustomExceptionHandler implements ExceptionHandlerInterface
{
    protected $debug;

    public function __construct(bool $debug)
    {
        $this->debug = $debug;
    }

    public function handle(Exception $exception): void
    {
        if ($this->debug) {
            $this->renderDebugPage($exception);
        } else {
            error_log($exception->getMessage());
            echo "An unexpected error occurred. Please try again later.";
        }
    }

    private function renderDebugPage(Exception $exception): void
    {
        $errorMessage = htmlspecialchars($exception->getMessage());
        $errorFile = htmlspecialchars($exception->getFile());
        $errorLine = $exception->getLine();
        $stackTrace = $this->formatStackTrace($exception->getTraceAsString());

        echo <<<HTML
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error - Debug Mode</title>
            <style>
                body { font-family: 'Fira Code', 'Consolas', 'Monaco', monospace; background-color: #2b2b2b; color: #a9b7c6; padding: 20px; }
                .error-container { max-width: 800px; margin: auto; background: #3c3f41; box-shadow: 0 4px 8px rgba(0,0,0,0.2); border-radius: 8px; overflow: hidden; }
                .error-header { background: #cc7832; color: #fff; padding: 20px; text-align: center; font-size: 24px; }
                .error-details { padding: 20px; }
                .error-details p { margin: 0 0 10px; }
                .error-stack { background: #2b2b2b; color: #a9b7c6; padding: 10px; border-radius: 4px; overflow-x: auto; }
                .error-stack code { color: #ffc66d; }
                .error-meta { color: #6a8759; font-size: 14px; }
                .error-meta span { display: inline-block; margin-right: 10px; }
                .line-number { color: #9876aa; }
                .function-call { color: #a9b7c6; }
                .path { color: #287bde; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-header">An Error Occurred</div>
                <div class="error-details">
                    <p><strong>Message:</strong> $errorMessage</p>
                    <p class="error-meta">
                        <span><strong>File:</strong> <span class="path">$errorFile</span></span>
                        <span><strong>Line:</strong> <span class="line-number">$errorLine</span></span>
                    </p>
                    <h3>Stack Trace:</h3>
                    <div class="error-stack">
                        <code>$stackTrace</code>
                    </div>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    private function formatStackTrace(string $stackTrace): string
    {
        return preg_replace(
            [
                '/(#\d+)/',
                '/(\b[a-zA-Z0-9_]+\(\))/',
                '/(C:\\\\[a-zA-Z0-9_\\\\\/.:-]+)/'
            ],
            [
                '<span class="line-number">$1</span>',
                '<span class="function-call">$1</span>',
                '<span class="path">$1</span>'
            ],
            $stackTrace
        ) ?? '';
    }

}
