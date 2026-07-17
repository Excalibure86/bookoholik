<?php

namespace App\Controllers;

use App\Config\Database;

/**
 * Genres Controller
 */
class GenresController extends BaseController
{
    /**
     * GET /api/genres
     */
    public function index(array $params): void
    {
        $db = Database::getConnection();
        $stmt = $db->query('SELECT * FROM genres ORDER BY name');
        $this->json(['data' => $stmt->fetchAll()]);
    }

    /**
     * POST /api/genres
     */
    public function store(array $params): void
    {
        $data = $this->getRequestBody();

        if (empty($data['name'])) {
            $this->json(['error' => 'Genre name is required.'], 422);
            return;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('
            INSERT INTO genres (name, name_ar, name_fr) 
            VALUES (:name, :name_ar, :name_fr)
            ON CONFLICT (name) DO NOTHING
            RETURNING *
        ');
        $stmt->execute([
            'name' => $this->sanitize($data['name']),
            'name_ar' => $this->sanitize($data['name_ar'] ?? ''),
            'name_fr' => $this->sanitize($data['name_fr'] ?? ''),
        ]);

        $genre = $stmt->fetch();
        if (!$genre) {
            $this->json(['error' => 'Genre already exists.'], 409);
            return;
        }

        $this->json(['message' => 'Genre created.', 'data' => $genre], 201);
    }

    /**
     * PUT /api/genres/{id}
     */
    public function update(array $params): void
    {
        $data = $this->getRequestBody();
        $db = Database::getConnection();

        $stmt = $db->prepare('SELECT * FROM genres WHERE id = :id');
        $stmt->execute(['id' => $params['id']]);
        $genre = $stmt->fetch();

        if (!$genre) {
            $this->json(['error' => 'Genre not found.'], 404);
            return;
        }

        $fields = ['name', 'name_ar', 'name_fr'];
        $updates = [];
        $bindings = ['id' => $params['id']];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "{$field} = :{$field}";
                $bindings[$field] = $this->sanitize($data[$field]);
            }
        }

        if (empty($updates)) {
            $this->json(['error' => 'No fields to update.'], 422);
            return;
        }

        $sql = "UPDATE genres SET " . implode(', ', $updates) . " WHERE id = :id RETURNING *";
        $stmt = $db->prepare($sql);
        $stmt->execute($bindings);
        $updatedGenre = $stmt->fetch();

        $this->json(['message' => 'Genre updated.', 'data' => $updatedGenre]);
    }

    /**
     * DELETE /api/genres/{id}
     */
    public function destroy(array $params): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM genres WHERE id = :id');
        $stmt->execute(['id' => $params['id']]);

        if ($stmt->rowCount() === 0) {
            $this->json(['error' => 'Genre not found.'], 404);
            return;
        }

        $this->json(['message' => 'Genre deleted.']);
    }
}
