<?php

namespace App\Controllers;

use App\Config\Database;
use Firebase\JWT\JWT;

/**
 * Authentication Controller
 * Handles login, register, and token management
 */
class AuthController extends BaseController
{
    /**
     * POST /api/auth/login
     */
    public function login(array $params): void
    {
        $data = $this->getRequestBody();

        $errors = $this->validateRequired($data, ['username', 'password']);
        if ($errors) {
            $this->json(['errors' => $errors], 422);
            return;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM users WHERE (username = :username OR email = :username) AND is_active = TRUE');
        $stmt->execute(['username' => $data['username']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            $this->json(['error' => 'Invalid credentials.'], 401);
            return;
        }

        $token = $this->generateToken($user);

        $this->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role'],
            ]
        ]);
    }

    /**
     * POST /api/auth/register
     */
    public function register(array $params): void
    {
        $data = $this->getRequestBody();

        $errors = $this->validateRequired($data, ['username', 'email', 'password', 'full_name']);
        if ($errors) {
            $this->json(['errors' => $errors], 422);
            return;
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->json(['error' => 'Invalid email format.'], 422);
            return;
        }

        // Validate password strength
        if (strlen($data['password']) < 8) {
            $this->json(['error' => 'Password must be at least 8 characters long.'], 422);
            return;
        }

        $db = Database::getConnection();

        // Check for existing user
        $stmt = $db->prepare('SELECT id FROM users WHERE username = :username OR email = :email');
        $stmt->execute(['username' => $data['username'], 'email' => $data['email']]);
        if ($stmt->fetch()) {
            $this->json(['error' => 'Username or email already exists.'], 409);
            return;
        }

        // Create user
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $role = $data['role'] ?? 'user';

        // Only admin can create admin users
        $authUser = $this->getAuthUser();
        if ($role === 'admin' && (!$authUser || $authUser['role'] !== 'admin')) {
            $role = 'user';
        }

        $stmt = $db->prepare('
            INSERT INTO users (username, email, password_hash, full_name, role) 
            VALUES (:username, :email, :password_hash, :full_name, :role) 
            RETURNING id, username, email, full_name, role, created_at
        ');
        $stmt->execute([
            'username' => $this->sanitize($data['username']),
            'email' => $data['email'],
            'password_hash' => $passwordHash,
            'full_name' => $this->sanitize($data['full_name']),
            'role' => $role,
        ]);

        $newUser = $stmt->fetch();

        $this->json([
            'message' => 'Registration successful',
            'user' => $newUser,
        ], 201);
    }

    /**
     * GET /api/auth/me
     */
    public function me(array $params): void
    {
        $authUser = $this->getAuthUser();
        $db = Database::getConnection();

        $stmt = $db->prepare('SELECT id, username, email, full_name, role, created_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $authUser['id']]);
        $user = $stmt->fetch();

        if (!$user) {
            $this->json(['error' => 'User not found.'], 404);
            return;
        }

        $this->json(['user' => $user]);
    }

    /**
     * PUT /api/auth/password
     */
    public function changePassword(array $params): void
    {
        $data = $this->getRequestBody();
        $authUser = $this->getAuthUser();

        $errors = $this->validateRequired($data, ['current_password', 'new_password']);
        if ($errors) {
            $this->json(['errors' => $errors], 422);
            return;
        }

        if (strlen($data['new_password']) < 8) {
            $this->json(['error' => 'New password must be at least 8 characters long.'], 422);
            return;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = :id');
        $stmt->execute(['id' => $authUser['id']]);
        $user = $stmt->fetch();

        if (!password_verify($data['current_password'], $user['password_hash'])) {
            $this->json(['error' => 'Current password is incorrect.'], 401);
            return;
        }

        $newHash = password_hash($data['new_password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['hash' => $newHash, 'id' => $authUser['id']]);

        $this->json(['message' => 'Password changed successfully.']);
    }

    /**
     * Generate JWT token
     */
    private function generateToken(array $user): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? 'your-jwt-secret-key-change-in-production';
        $payload = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60), // 24 hours
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }
}
