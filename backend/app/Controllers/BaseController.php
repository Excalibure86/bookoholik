<?php

namespace App\Controllers;

/**
 * Base controller with common helper methods
 */
abstract class BaseController
{
    /**
     * Send JSON response
     */
    protected function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Get request body as array
     */
    protected function getRequestBody(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    /**
     * Get authenticated user data
     */
    protected function getAuthUser(): ?array
    {
        return $_REQUEST['auth_user'] ?? null;
    }

    /**
     * Sanitize string input
     */
    protected function sanitize(string $input): string
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate required fields
     */
    protected function validateRequired(array $data, array $fields): ?array
    {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $errors[] = "Field '{$field}' is required.";
            }
        }
        return empty($errors) ? null : $errors;
    }

    /**
     * Get query parameters
     */
    protected function getQueryParams(): array
    {
        return $_GET ?? [];
    }
}
