<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Authentication middleware - validates JWT tokens
 */
class AuthMiddleware
{
    public function handle(): bool|array
    {
        $token = $this->getBearerToken();

        if (!$token) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required. No token provided.']);
            return false;
        }

        try {
            $secret = $_ENV['JWT_SECRET'] ?? null;
            if (empty($secret)) {
                http_response_code(500);
                echo json_encode(['error' => 'Server misconfiguration: JWT_SECRET not set.']);
                return false;
            }
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));

            // Store user data in global scope for controllers
            $_REQUEST['auth_user'] = (array) $decoded;
            return true;
        } catch (\Exception $e) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token.']);
            return false;
        }
    }

    private function getBearerToken(): ?string
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
