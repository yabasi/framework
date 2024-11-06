<?php

namespace Yabasi\Logging;

use Exception;
use Yabasi\Config\Config;

class CustomExceptionHandler implements ExceptionHandlerInterface
{
    protected $debug;
    protected $config;

    public function __construct(bool $debug)
    {
        $this->debug = $debug;
        $this->config = new Config();
    }

    public function handle(Exception $exception): void
    {
        if ($this->debug) {
            $this->renderDebugPage($exception);
        } else {
            error_log($exception->getMessage());
            $this->renderProductionErrorPage();
        }
    }

    private function renderProductionErrorPage(): void
    {
        $currentYear = date('Y');

        echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - Yabasi Framework</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
    <body class="bg-gray-50 min-h-screen flex flex-col">
        <main class="flex-grow flex items-center justify-center p-4">
            <div class="max-w-lg w-full text-center">
                <!-- Error Icon -->
                <div class="mb-8">
                    <div class="inline-block p-6 bg-red-100 rounded-full">
                        <svg class="w-16 h-16 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" 
                                  stroke-linejoin="round" 
                                  stroke-width="2" 
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>

                <!-- Error Message -->
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Oops! Something went wrong.</h1>
                <p class="text-lg text-gray-600 mb-8">
                    We apologize for the inconvenience. Our team has been notified and is working on fixing the issue.
                </p>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                    <a href="/" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition-colors duration-200">
                        <i class="fas fa-home mr-2"></i>
                        Return Home
                    </a>
                    <button onclick="window.location.reload()" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                        <i class="fas fa-redo mr-2"></i>
                        Try Again
                    </button>
                </div>

                <!-- Support Info -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">Need Help?</h2>
                    <p class="text-gray-600 mb-4">
                        If the problem persists, please contact our support team or check our status page.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3 justify-center text-sm">
                        <span class="hidden sm:inline text-gray-300">|</span>
                        <a href="https://yabasi.com/docs" target="_blank" class="text-indigo-600 hover:text-indigo-800 flex items-center justify-center">
                            <i class="fas fa-life-ring mr-1"></i>
                            Support Center
                        </a>
                        <span class="hidden sm:inline text-gray-300">|</span>
                        <a href="mailto:abbas@yabasi.com" class="text-indigo-600 hover:text-indigo-800 flex items-center justify-center">
                            <i class="fas fa-envelope mr-1"></i>
                            Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="py-4 text-center text-sm text-gray-500">
            <div class="container mx-auto px-4">
                <p>&copy; {$currentYear} Yabasi Framework. All rights reserved.</p>
            </div>
        </footer>
    </body>
    </html>
    HTML;
    }

    private function renderDebugPage(Exception $exception): void
    {
        $errorMessage = htmlspecialchars($exception->getMessage());
        $errorFile = htmlspecialchars($exception->getFile());
        $errorLine = $exception->getLine();
        $stackTrace = $this->formatStackTrace($exception->getTraceAsString());
        $exceptionClass = get_class($exception);
        $timestamp = date('Y-m-d H:i:s');

        echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Yabasi Debug - {$exceptionClass}</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            .stack-trace::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }
            .stack-trace::-webkit-scrollbar-track {
                background: #1f2937;
            }
            .stack-trace::-webkit-scrollbar-thumb {
                background: #4b5563;
                border-radius: 4px;
            }
            .stack-trace::-webkit-scrollbar-thumb:hover {
                background: #6b7280;
            }
        </style>
    </head>
    <body class="bg-gray-100 text-gray-900 min-h-screen">
        <!-- Top Bar -->
        <div class="bg-red-600 text-white shadow-lg">
            <div class="container mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="bg-white bg-opacity-20 rounded-lg p-2">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold">{$exceptionClass}</h1>
                            <p class="text-red-100 text-sm">{$errorMessage}</p>
                        </div>
                    </div>
                    <div class="text-right text-sm text-red-100">
                        <p>Yabasi Framework v{$this->getFrameworkVersion()}</p>
                        <p>{$timestamp}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <div class="lg:col-span-3 space-y-6">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-semibold mb-4 flex items-center">
                            <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                            Error Location
                        </h2>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center text-sm">
                                <i class="fas fa-file-code text-gray-400 mr-2"></i>
                                <span class="font-mono text-gray-600">{$errorFile}</span>
                                <span class="mx-2">:</span>
                                <span class="font-mono text-red-600 font-bold">line {$errorLine}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-semibold mb-4 flex items-center">
                            <i class="fas fa-layer-group text-blue-500 mr-2"></i>
                            Stack Trace
                        </h2>
                        <div class="stack-trace bg-gray-900 rounded-lg p-4 overflow-x-auto" style="max-height: 600px;">
                            <pre class="text-sm font-mono text-gray-200">{$stackTrace}</pre>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-semibold mb-4 flex items-center">
                            <i class="fas fa-info-circle text-indigo-500 mr-2"></i>
                            Request Details
                        </h2>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">HTTP Method</p>
                                <p class="mt-1 font-mono text-sm">{$_SERVER['REQUEST_METHOD']}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">URI</p>
                                <p class="mt-1 font-mono text-sm break-all">{$_SERVER['REQUEST_URI']}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">IP Address</p>
                                <p class="mt-1 font-mono text-sm">{$_SERVER['REMOTE_ADDR']}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-semibold mb-4 flex items-center">
                            <i class="fas fa-cog text-green-500 mr-2"></i>
                            Environment
                        </h2>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">PHP Version</p>
                                <p class="mt-1 font-mono text-sm">{$this->getPHPVersion()}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Framework</p>
                                <p class="mt-1 font-mono text-sm">Yabasi {$this->getFrameworkVersion()}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Server</p>
                                <p class="mt-1 font-mono text-sm">{$_SERVER['SERVER_SOFTWARE']}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg shadow-sm p-6">
                        <a href="https://github.com/yabasi/yabasi/issues" target="_blank" 
                           class="flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 transition-colors">
                            <i class="fab fa-github mr-2"></i>
                            Report Issue
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-8 border-t border-gray-200">
            <div class="container mx-auto px-4 py-4">
                <div class="text-sm text-gray-500 flex items-center justify-between">
                    <span>Debug Mode Enabled</span>
                    <a href="https://yabasi.com/docs" target="_blank" 
                       class="text-indigo-600 hover:text-indigo-800">
                        Debugging Guide <i class="fas fa-external-link-alt ml-1"></i>
                    </a>
                </div>
            </div>
        </footer>
    </body>
    </html>
    HTML;
    }

    private function formatStackTrace(string $stackTrace): string
    {
        $lines = explode("\n", $stackTrace);
        $formattedTrace = '';

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $line = preg_replace('/^#(\d+)/', '<span class="text-yellow-400">#$1</span>', $line);

            $line = preg_replace('/([a-zA-Z0-9\/\-_.]+\.php)/', '<span class="text-blue-400">$1</span>', $line);

            $line = preg_replace('/:(\d+)/', ':<span class="text-red-400">$1</span>', $line);

            $line = preg_replace('/([a-zA-Z0-9_]+)(?=::)/', '<span class="text-green-400">$1</span>', $line);
            $line = preg_replace('/->([a-zA-Z0-9_]+)/', '-><span class="text-green-400">$1</span>', $line);

            $formattedTrace .= $line . "\n";
        }

        return $formattedTrace;
    }

    private function getPHPVersion(): string
    {
        return PHP_VERSION;
    }

    private function getFrameworkVersion(): string
    {
        try {
            return $this->config->get('app.version', '1.0.21');
        } catch (Exception $e) {
            return '1.0.21';
        }
    }
}