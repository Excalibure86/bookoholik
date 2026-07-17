<?php

namespace App\Controllers;

use App\Config\Database;

/**
 * Books Controller
 * CRUD operations and search for books
 */
class BooksController extends BaseController
{
    /**
     * GET /api/books
     * List books with pagination, search, and filters
     */
    public function index(array $params): void
    {
        $query = $this->getQueryParams();
        $db = Database::getConnection();

        $page = max(1, (int)($query['page'] ?? 1));
        $perPage = min(100, max(1, (int)($query['per_page'] ?? 25)));
        $offset = ($page - 1) * $perPage;

        // Build WHERE conditions
        $conditions = [];
        $bindings = [];

        // Search by keyword (title or author)
        if (!empty($query['search'])) {
            $search = '%' . $query['search'] . '%';
            $conditions[] = "(title ILIKE :search OR author ILIKE :search)";
            $bindings['search'] = $search;
        }

        // Filter by language
        if (!empty($query['language'])) {
            $conditions[] = "language = :language";
            $bindings['language'] = $query['language'];
        }

        // Filter by genre
        if (!empty($query['genre'])) {
            $conditions[] = "genre = :genre";
            $bindings['genre'] = $query['genre'];
        }

        // Filter by author
        if (!empty($query['author'])) {
            $conditions[] = "author ILIKE :author";
            $bindings['author'] = '%' . $query['author'] . '%';
        }

        // Filter by read status
        if (isset($query['read_status'])) {
            $conditions[] = "read_status = :read_status";
            $bindings['read_status'] = $query['read_status'] === 'true' || $query['read_status'] === '1';
        }

        // Filter by publication year range
        if (!empty($query['year_from'])) {
            $conditions[] = "publication_year >= :year_from";
            $bindings['year_from'] = (int)$query['year_from'];
        }
        if (!empty($query['year_to'])) {
            $conditions[] = "publication_year <= :year_to";
            $bindings['year_to'] = (int)$query['year_to'];
        }

        // Filter by location
        if (!empty($query['location_room'])) {
            $conditions[] = "location_room = :location_room";
            $bindings['location_room'] = $query['location_room'];
        }
        if (!empty($query['location_shelf'])) {
            $conditions[] = "location_shelf = :location_shelf";
            $bindings['location_shelf'] = $query['location_shelf'];
        }

        // Filter by borrowed status
        if (isset($query['borrowed'])) {
            if ($query['borrowed'] === 'true' || $query['borrowed'] === '1') {
                $conditions[] = "id IN (SELECT book_id FROM lending_records WHERE status = 'active')";
            } else {
                $conditions[] = "id NOT IN (SELECT book_id FROM lending_records WHERE status = 'active')";
            }
        }

        // Filter by series
        if (!empty($query['series_name'])) {
            $conditions[] = "series_name ILIKE :series_name";
            $bindings['series_name'] = '%' . $query['series_name'] . '%';
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        // Sorting — strict whitelist only
        $allowedSorts = ['title', 'author', 'publication_year', 'created_at', 'genre', 'language'];
        $sortBy = in_array($query['sort_by'] ?? '', $allowedSorts, true) ? $query['sort_by'] : 'created_at';
        $sortDir = strtoupper($query['sort_dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM books {$whereClause}";
        $stmt = $db->prepare($countSql);
        $stmt->execute($bindings);
        $total = (int)$stmt->fetch()['total'];

        // Get books
        $sql = "SELECT b.*, 
                    CASE WHEN lr.id IS NOT NULL THEN true ELSE false END as is_borrowed,
                    lr.borrower_name,
                    lr.due_date as lending_due_date
                FROM books b
                LEFT JOIN lending_records lr ON lr.book_id = b.id AND lr.status = 'active'
                {$whereClause}
                ORDER BY b.{$sortBy} {$sortDir}
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $books = $stmt->fetchAll();

        $this->json([
            'data' => $books,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
            ]
        ]);
    }

    /**
     * GET /api/books/{id}
     */
    public function show(array $params): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('
            SELECT b.*, 
                   CASE WHEN lr.id IS NOT NULL THEN true ELSE false END as is_borrowed,
                   lr.borrower_name,
                   lr.due_date as lending_due_date,
                   lr.lent_date
            FROM books b
            LEFT JOIN lending_records lr ON lr.book_id = b.id AND lr.status = \'active\'
            WHERE b.id = :id
        ');
        $stmt->execute(['id' => $params['id']]);
        $book = $stmt->fetch();

        if (!$book) {
            $this->json(['error' => 'Book not found.'], 404);
            return;
        }

        // Get lending history
        $stmt = $db->prepare('
            SELECT * FROM lending_records 
            WHERE book_id = :book_id 
            ORDER BY created_at DESC 
            LIMIT 10
        ');
        $stmt->execute(['book_id' => $params['id']]);
        $book['lending_history'] = $stmt->fetchAll();

        $this->json(['data' => $book]);
    }

    /**
     * POST /api/books
     */
    public function store(array $params): void
    {
        $data = $this->getRequestBody();

        $errors = $this->validateRequired($data, ['title', 'author']);
        if ($errors) {
            $this->json(['errors' => $errors], 422);
            return;
        }

        // Validate language
        $allowedLanguages = ['arabic', 'english', 'french', 'other'];
        $language = $data['language'] ?? 'arabic';
        if (!in_array($language, $allowedLanguages)) {
            $this->json(['error' => 'Invalid language. Allowed: ' . implode(', ', $allowedLanguages)], 422);
            return;
        }

        // Validate ISBN if provided
        if (!empty($data['isbn']) && !$this->validateISBN($data['isbn'])) {
            $this->json(['error' => 'Invalid ISBN format.'], 422);
            return;
        }

        $authUser = $this->getAuthUser();
        $db = Database::getConnection();

        $stmt = $db->prepare('
            INSERT INTO books (title, author, genre, publication_year, language, 
                             location_room, location_shelf, read_status, isbn, 
                             edition_house, num_pages, series_name, series_position, 
                             notes, cover_image_url, owner_id)
            VALUES (:title, :author, :genre, :publication_year, :language,
                    :location_room, :location_shelf, :read_status, :isbn,
                    :edition_house, :num_pages, :series_name, :series_position,
                    :notes, :cover_image_url, :owner_id)
            RETURNING *
        ');

        $stmt->execute([
            'title' => $this->sanitize($data['title']),
            'author' => $this->sanitize($data['author']),
            'genre' => $this->sanitize($data['genre'] ?? ''),
            'publication_year' => !empty($data['publication_year']) ? (int)$data['publication_year'] : null,
            'language' => $language,
            'location_room' => $this->sanitize($data['location_room'] ?? ''),
            'location_shelf' => $this->sanitize($data['location_shelf'] ?? ''),
            'read_status' => (bool)($data['read_status'] ?? false),
            'isbn' => $this->sanitize($data['isbn'] ?? ''),
            'edition_house' => $this->sanitize($data['edition_house'] ?? ''),
            'num_pages' => !empty($data['num_pages']) ? (int)$data['num_pages'] : null,
            'series_name' => $this->sanitize($data['series_name'] ?? ''),
            'series_position' => !empty($data['series_position']) ? (int)$data['series_position'] : null,
            'notes' => $data['notes'] ?? '',
            'cover_image_url' => $this->sanitize($data['cover_image_url'] ?? ''),
            'owner_id' => $authUser['id'] ?? null,
        ]);

        $book = $stmt->fetch();

        // Log action
        $this->logAction('create', 'book', $book['id'], $authUser['id'] ?? null);

        $this->json(['message' => 'Book added successfully.', 'data' => $book], 201);
    }

    /**
     * PUT /api/books/{id}
     */
    public function update(array $params): void
    {
        $data = $this->getRequestBody();
        $db = Database::getConnection();

        // Check if book exists
        $stmt = $db->prepare('SELECT * FROM books WHERE id = :id');
        $stmt->execute(['id' => $params['id']]);
        $book = $stmt->fetch();

        if (!$book) {
            $this->json(['error' => 'Book not found.'], 404);
            return;
        }

        // Build dynamic update
        $fields = ['title', 'author', 'genre', 'publication_year', 'language',
                   'location_room', 'location_shelf', 'read_status', 'isbn',
                   'edition_house', 'num_pages', 'series_name', 'series_position',
                   'notes', 'cover_image_url'];

        $updates = [];
        $bindings = ['id' => $params['id']];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "{$field} = :{$field}";
                if (in_array($field, ['publication_year', 'num_pages', 'series_position'])) {
                    $bindings[$field] = !empty($data[$field]) ? (int)$data[$field] : null;
                } elseif ($field === 'read_status') {
                    $bindings[$field] = (bool)$data[$field];
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
        $sql = "UPDATE books SET " . implode(', ', $updates) . " WHERE id = :id RETURNING *";

        $stmt = $db->prepare($sql);
        $stmt->execute($bindings);
        $updatedBook = $stmt->fetch();

        $authUser = $this->getAuthUser();
        $this->logAction('update', 'book', $params['id'], $authUser['id'] ?? null);

        $this->json(['message' => 'Book updated successfully.', 'data' => $updatedBook]);
    }

    /**
     * DELETE /api/books/{id}
     */
    public function destroy(array $params): void
    {
        $db = Database::getConnection();

        $stmt = $db->prepare('SELECT id FROM books WHERE id = :id');
        $stmt->execute(['id' => $params['id']]);
        if (!$stmt->fetch()) {
            $this->json(['error' => 'Book not found.'], 404);
            return;
        }

        $stmt = $db->prepare('DELETE FROM books WHERE id = :id');
        $stmt->execute(['id' => $params['id']]);

        $authUser = $this->getAuthUser();
        $this->logAction('delete', 'book', $params['id'], $authUser['id'] ?? null);

        $this->json(['message' => 'Book deleted successfully.']);
    }

    /**
     * POST /api/books/{id}/toggle-read
     */
    public function toggleRead(array $params): void
    {
        $db = Database::getConnection();

        $stmt = $db->prepare('UPDATE books SET read_status = NOT read_status, updated_at = NOW() WHERE id = :id RETURNING *');
        $stmt->execute(['id' => $params['id']]);
        $book = $stmt->fetch();

        if (!$book) {
            $this->json(['error' => 'Book not found.'], 404);
            return;
        }

        $this->json(['message' => 'Read status updated.', 'data' => $book]);
    }

    /**
     * POST /api/books/isbn-lookup
     * Look up book info by ISBN (placeholder for external API)
     */
    public function isbnLookup(array $params): void
    {
        $data = $this->getRequestBody();

        if (empty($data['isbn'])) {
            $this->json(['error' => 'ISBN is required.'], 422);
            return;
        }

        // Sanitize ISBN — allow only digits and X (for ISBN-10)
        $isbn = preg_replace('/[^0-9X]/', '', strtoupper($data['isbn']));

        // Validate ISBN format strictly
        if (strlen($isbn) !== 10 && strlen($isbn) !== 13) {
            $this->json(['error' => 'Invalid ISBN format. Must be ISBN-10 or ISBN-13.'], 422);
            return;
        }

        // Only allow requests to Open Library API (prevent SSRF)
        $url = "https://openlibrary.org/api/books?bibkeys=ISBN:{$isbn}&format=json&jscmd=data";

        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'method' => 'GET',
                'follow_location' => 0, // Don't follow redirects
                'max_redirects' => 0,
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $this->json(['error' => 'Could not look up ISBN. External service unavailable.'], 503);
            return;
        }

        $bookData = json_decode($response, true);
        $key = "ISBN:{$isbn}";

        if (empty($bookData[$key])) {
            $this->json(['error' => 'No book found for this ISBN.'], 404);
            return;
        }

        $info = $bookData[$key];
        $this->json([
            'data' => [
                'title' => $info['title'] ?? '',
                'author' => !empty($info['authors']) ? $info['authors'][0]['name'] : '',
                'publication_year' => !empty($info['publish_date']) ? (int)$info['publish_date'] : null,
                'num_pages' => $info['number_of_pages'] ?? null,
                'edition_house' => !empty($info['publishers']) ? $info['publishers'][0]['name'] : '',
                'isbn' => $isbn,
                'cover_image_url' => $info['cover']['medium'] ?? '',
            ]
        ]);
    }

    /**
     * Validate ISBN format (ISBN-10 or ISBN-13)
     */
    private function validateISBN(string $isbn): bool
    {
        $isbn = preg_replace('/[^0-9X]/', '', strtoupper($isbn));
        return strlen($isbn) === 10 || strlen($isbn) === 13;
    }

    /**
     * Log an action to audit log
     */
    private function logAction(string $action, string $entityType, string $entityId, ?string $userId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('
            INSERT INTO audit_log (user_id, action, entity_type, entity_id) 
            VALUES (:user_id, :action, :entity_type, :entity_id)
        ');
        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);
    }
}
