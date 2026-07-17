<?php

namespace App\Middleware;

/**
 * Admin-only middleware
 */
class AdminMiddleware
{
    public function handle(): bool
    {
        // First check auth
        $authMiddleware = new AuthMiddleware();
        $result = $authMiddleware->handle();

        if ($result === false) {
            return false;
        }

        $user = $_REQUEST['auth_user'] ?? null;

        if (!$user || ($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. Admin privileges required.']);
            return false;
        }

        return true;
    }
}
