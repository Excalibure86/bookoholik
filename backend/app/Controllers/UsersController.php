<?php

namespace App\Controllers;

use App\Config\Database;

/**
 * Users Controller
 * Admin-level user management
 */
class UsersController extends BaseController
{
    /**
     * GET /api/users
     */
    public function index(array $params): void
    {
        $db = Database::getConnection();
        $stmt = $db->query('
            SELECT id, username, email, full_name, role, is_active, created_at, updated_at
            FROM users 
            ORDER BY created_at DESC
        ');

        $this->json(['data' => $stmt->fetchAll()]);
    }

    /**
     * GET /api/users/{id}
     */
    public function show(array $params): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('
            SELECT id, username, email, full_name, role, is_active, created_at, updated_at
            FROM users WHERE id = :id
        ');
        $stmt->execute(['id' => $params['id']]);
        $user = $stmt->fetch();

        if (!$user) {
            $this->json(['error' => 'User not found.'], 404);
            return;
        }

        // Get user's book count
        $stmt = $db->prepare('SELECT COUNT(*) as count FROM books WHERE owner_id = :id');
        $stmt->execute(['id' => $params['id']]);
        $user['book_count'] = (int)$stmt->fetch()['count'];

        $this->json(['data' => $user]);
    }

    /**
     * PUT /api/users/{id}
     */
    public function update(array $params): void
    {
        $data = $this->getRequestBody();
        $db = Database::getConnection();

        $stmt = $db->prepare('SELECT id FROM users WHERE id = :id');
        $stmt->execute(['id' => $params['id']]);
        if (!$stmt->fetch()) {
            $this->json(['error' => 'User not found.'], 404);
            return;
        }

        $updates = [];
        $bindings = ['id' => $params['id']];

        if (!empty($data['full_name'])) {
            $updates[] = "full_name = :full_name";
            $bindings['full_name'] = $this->sanitize($data['full_name']);
        }
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->json(['error' => 'Invalid email format.'], 422);
                return;
            }
            $updates[] = "email = :email";
            $bindings['email'] = $data['email'];
        }
        if (!empty($data['role'])) {
            $allowedRoles = ['admin', 'user', 'viewer'];
            if (!in_array($data['role'], $allowedRoles)) {
                $this->json(['error' => 'Invalid role.'], 422);
                return;
            }
            $updates[] = "role = :role";
            $bindings['role'] = $data['role'];
        }
        if (isset($data['is_active'])) {
            $updates[] = "is_active = :is_active";
            $bindings['is_active'] = (bool)$data['is_active'];
        }

        if (empty($updates)) {
            $this->json(['error' => 'No fields to update.'], 422);
            return;
        }

        $updates[] = "updated_at = NOW()";
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id RETURNING id, username, email, full_name, role, is_active";

        $stmt = $db->prepare($sql);
        $stmt->execute($bindings);
        $user = $stmt->fetch();

        $this->json(['message' => 'User updated successfully.', 'data' => $user]);
    }

    /**
     * DELETE /api/users/{id}
     */
    public function destroy(array $params): void
    {
        $authUser = $this->getAuthUser();

        // Prevent self-deletion
        if ($params['id'] === $authUser['id']) {
            $this->json(['error' => 'Cannot delete your own account.'], 403);
            return;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $params['id']]);

        if ($stmt->rowCount() === 0) {
            $this->json(['error' => 'User not found.'], 404);
            return;
        }

        $this->json(['message' => 'User deleted successfully.']);
    }
}
