<?php

namespace App\Controllers;

use App\Config\Database;

/**
 * Writers Controller
 * CRUD operations for writers/authors
 */
class WritersController extends BaseController
{
    /**
     * GET /api/writers
     */
    public function index(array $params): void
    {
        $query = $this->getQueryParams();
        $db = Database::getConnection();

        $conditions = [];
        $bindings = [];

        if (!empty($query['search'])) {
            $search = '%' . $query['search'] . '%';
            $conditions[] = "(name ILIKE :search OR name_ar ILIKE :search)";
            $bindings['search'] = $search;
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = "SELECT w.*, 
                    (SELECT COUNT(*) FROM books WHERE author = w.name OR author = w.name_ar) as books_count
                FROM writers w 
                {$whereClause} 
                ORDER BY w.name ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($bindings);

        $this->json(['data' => $stmt->fetchAll()]);
    }

    /**
     * GET /api/writers/{id}
     */
    public function show(array $params): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM writers WHERE id = :id');
        $stmt->execute(['id' => $params['id']]);
        $writer = $stmt->fetch();

        if (!$writer) {
            $this->json(['error' => 'Writer not found.'], 404);
            return;
        }

        // Get books by this writer
        $stmt = $db->prepare('SELECT * FROM books WHERE author = :name OR author = :name_ar ORDER BY publication_year DESC');
        $stmt->execute(['name' => $writer['name'], 'name_ar' => $writer['name_ar'] ?? '']);
        $writer['books'] = $stmt->fetchAll();

        $this->json(['data' => $writer]);
    }

    /**
     * POST /api/writers
     */
    public function store(array $params): void
    {
        $data = $this->getRequestBody();

        if (empty($data['name'])) {
            $this->json(['error' => 'Writer name is required.'], 422);
            return;
        }

        $db = Database::getConnection();

        // Check for duplicate
        $stmt = $db->prepare('SELECT id FROM writers WHERE name = :name OR (name_ar IS NOT NULL AND name_ar = :name_ar)');
        $stmt->execute(['name' => $data['name'], 'name_ar' => $data['name_ar'] ?? '']);
        if ($stmt->fetch()) {
            $this->json(['error' => 'Writer already exists.'], 409);
            return;
        }

        $stmt = $db->prepare('
            INSERT INTO writers (name, name_ar, name_fr, nationality, birth_year, death_year, biography)
            VALUES (:name, :name_ar, :name_fr, :nationality, :birth_year, :death_year, :biography)
            RETURNING *
        ');
        $stmt->execute([
            'name' => $this->sanitize($data['name']),
            'name_ar' => $this->sanitize($data['name_ar'] ?? ''),
            'name_fr' => $this->sanitize($data['name_fr'] ?? ''),
            'nationality' => $this->sanitize($data['nationality'] ?? ''),
            'birth_year' => !empty($data['birth_year']) ? (int)$data['birth_year'] : null,
            'death_year' => !empty($data['death_year']) ? (int)$data['death_year'] : null,
            'biography' => $data['biography'] ?? '',
        ]);

        $writer = $stmt->fetch();
        $this->json(['message' => 'Writer created successfully.', 'data' => $writer], 201);
    }

    /**
     * PUT /api/writers/{id}
     */
    public function update(array $params): void
    {
        $data = $this->getRequestBody();
        $db = Database::getConnection();

        $stmt = $db->prepare('SELECT * FROM writers WHERE id = :id');
        $stmt->execute(['id' => $params['id']]);
        $writer = $stmt->fetch();

        if (!$writer) {
            $this->json(['error' => 'Writer not found.'], 404);
            return;
        }

        $fields = ['name', 'name_ar', 'name_fr', 'nationality', 'birth_year', 'death_year', 'biography'];
        $updates = [];
        $bindings = ['id' => $params['id']];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "{$field} = :{$field}";
                if (in_array($field, ['birth_year', 'death_year'])) {
                    $bindings[$field] = !empty($data[$field]) ? (int)$data[$field] : null;
                } else {
                    $bindings[$field] = is_string($data[$field]) ? $this->sanitize($data[$field]) : $data[$field];
                }
            }
        }

        if (empty($updates)) {
            $this->json(['error' => 'No fields to update.'], 422);
            return;
        }

        $updates[] = "updated_at = NOW()";
        $sql = "UPDATE writers SET " . implode(', ', $updates) . " WHERE id = :id RETURNING *";

        $stmt = $db->prepare($sql);
        $stmt->execute($bindings);
        $updatedWriter = $stmt->fetch();

        $this->json(['message' => 'Writer updated successfully.', 'data' => $updatedWriter]);
    }

    /**
     * DELETE /api/writers/{id}
     */
    public function destroy(array $params): void
    {
        $db = Database::getConnection();

        $stmt = $db->prepare('SELECT id FROM writers WHERE id = :id');
        $stmt->execute(['id' => $params['id']]);
        if (!$stmt->fetch()) {
            $this->json(['error' => 'Writer not found.'], 404);
            return;
        }

        $stmt = $db->prepare('DELETE FROM writers WHERE id = :id');
        $stmt->execute(['id' => $params['id']]);

        $this->json(['message' => 'Writer deleted successfully.']);
    }
}
