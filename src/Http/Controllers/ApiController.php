<?php

namespace Yabasi\Http\Controllers;

use Yabasi\Controller\Controller;
use Yabasi\Http\Response;

class ApiController extends Controller
{
    protected function respondWithData($data, $statusCode = 200): Response
    {
        return $this->json(['data' => $data], $statusCode);
    }

    protected function respondWithError($message, $statusCode): Response
    {
        return $this->json(['error' => $message], $statusCode);
    }

    protected function respondWithSuccess($message, $statusCode = 200): Response
    {
        return $this->json(['success' => $message], $statusCode);
    }

    protected function respondWithValidationError($errors): Response
    {
        return $this->json(['errors' => $errors], 422);
    }

    protected function respondWithPaginatedData($data, int $page, int $perPage, int $total): Response
    {
        return Response::json([
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage)
            ],
            'data' => $data
        ]);
    }
}