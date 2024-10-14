<?php

namespace Yabasi\Controller;

use Exception;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Yabasi\Container\Container;
use Yabasi\Http\Response;
use Yabasi\View\Template;

abstract class Controller
{
    protected Container $container;
    protected Template $template;

    /**
     * @throws Exception
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->template = $container->get(Template::class);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    protected function view(string $view, array $data = []): Response
    {
        $content = $this->template->render($view, $data);
        $response = new Response();
        $response->setContent($content);
        return $response;
    }

    protected function json(array $data, int $status = 200): Response
    {
        $response = new Response();
        $response->setContent(json_encode($data));
        $response->setHeader('Content-Type', 'application/json');
        $response->setStatusCode($status);
        return $response;
    }
}