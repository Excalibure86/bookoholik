<?php

namespace App\Controllers;

use App\Config\Database;

/**
 * Lending Controller
 * Manages book lending/borrowing operations
 */
class LendingController extends BaseController
{
    /**
     * GET /api/lending
     * List all active lending records
     */
    public function index(array $params): void
    {
        $query = $this->getQueryParams();
        $db = Database::getConnection();

        $page = max(1, (int)($query['page'] ?? 1));
        $perPage = min(100, max(1, (int)($query['per_page'] ?? 25)));
        $offset = ($page - 1) * $perPage;

        $statusFilter = $query['status'] ?? 'all';
        $conditions = [];
        $bindings = [];

        if ($statusFilter !== 'all') {
            $conditions[] = "lr.status = :status";
            $bindings['status'] = $statusFilter;
        }

        if (!empty($query['borrower'])) {
            $conditions[] = "lr.borrower_name ILIKE :borrower";
            $bindings['borrower'] = '%' . $query['borrower'] . '%';
        }

        // Check for overdue records and update status
        $db->exec("UPDATE lending_records SET status = 'overdue' WHERE status = 'active' AND due_date < CURRENT_DATE");

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $countSql = "SELECT COUNT(*) as total FROM lending_records lr {$whereClause}";
        $stmt = $db->prepare($countSql);
        $stmt->execute($bindings);
        $total = (int)$stmt->fetch()['total'];

        $sql = "SELECT lr.*, b.title as book_title, b.author as book_author
                FROM lending_records lr
                JOIN books b ON b.id = lr.book_id
                {$whereClause}
                ORDER BY lr.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        $this->json([
            'data' => $stmt->fetchAll(),
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int)ceil($total / $perPage),
            ]
        ]);
    }

    /**
     * POST /api/lending
     * Lend a book to someone
     */
    public function store(array $params): void
    {
        $data = $this->getRequestBody();

        $errors = $this->validateRequired($data, ['book_id', 'borrower_name', 'due_date']);
        if ($errors) {
            $this->json(['errors' => $errors], 422);
            return;
        }

        $db = Database::getConnection();

        // Check if book exists
        $stmt = $db->prepare('SELECT id, title FROM books WHERE id = :id');
        $stmt->execute(['id' => $data['book_id']]);
        $book = $stmt->fetch();

        if (!$book) {
            $this->json(['error' => 'Book not found.'], 404);
            return;
        }

        // Check if book is already lent out
        $stmt = $db->prepare("SELECT id FROM lending_records WHERE book_id = :book_id AND status IN ('active', 'overdue')");
        $stmt->execute(['book_id' => $data['book_id']]);
        if ($stmt->fetch()) {
            $this->json(['error' => 'Book is already lent out.'], 409);
            return;
        }

        // Validate due date
        $dueDate = $data['due_date'];
        if (strtotime($dueDate) <= time()) {
            $this->json(['error' => 'Due date must be in the future.'], 422);
            return;
        }

        $authUser = $this->getAuthUser();

        $stmt = $db->prepare('
            INSERT INTO lending_records (book_id, borrower_name, borrower_contact, lent_date, due_date, notes, lent_by)
            VALUES (:book_id, :borrower_name, :borrower_contact, :lent_date, :due_date, :notes, :lent_by)
            RETURNING *
        ');
        $stmt->execute([
            'book_id' => $data['book_id'],
            'borrower_name' => $this->sanitize($data['borrower_name']),
            'borrower_contact' => $this->sanitize($data['borrower_contact'] ?? ''),
            'lent_date' => $data['lent_date'] ?? date('Y-m-d'),
            'due_date' => $dueDate,
            'notes' => $data['notes'] ?? '',
            'lent_by' => $authUser['id'] ?? null,
        ]);

        $record = $stmt->fetch();

        $this->json(['message' => 'Book lent successfully.', 'data' => $record], 201);
    }

    /**
     * POST /api/lending/{id}/return
     * Mark a book as returned
     */
    public function returnBook(array $params): void
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            UPDATE lending_records 
            SET status = 'returned', returned_date = CURRENT_DATE, updated_at = NOW() 
            WHERE id = :id AND status IN ('active', 'overdue')
            RETURNING *
        ");
        $stmt->execute(['id' => $params['id']]);
        $record = $stmt->fetch();

        if (!$record) {
            $this->json(['error' => 'Lending record not found or already returned.'], 404);
            return;
        }

        $this->json(['message' => 'Book marked as returned.', 'data' => $record]);
    }

    /**
     * PUT /api/lending/{id}
     * Update lending record (extend due date, update notes, etc.)
     */
    public function update(array $params): void
    {
        $data = $this->getRequestBody();
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM lending_records WHERE id = :id");
        $stmt->execute(['id' => $params['id']]);
        $record = $stmt->fetch();

        if (!$record) {
            $this->json(['error' => 'Lending record not found.'], 404);
            return;
        }

        $updates = [];
        $bindings = ['id' => $params['id']];

        if (!empty($data['due_date'])) {
            $updates[] = "due_date = :due_date";
            $bindings['due_date'] = $data['due_date'];
        }
        if (isset($data['notes'])) {
            $updates[] = "notes = :notes";
            $bindings['notes'] = $data['notes'];
        }
        if (!empty($data['borrower_contact'])) {
            $updates[] = "borrower_contact = :borrower_contact";
            $bindings['borrower_contact'] = $this->sanitize($data['borrower_contact']);
        }

        if (empty($updates)) {
            $this->json(['error' => 'No fields to update.'], 422);
            return;
        }

        $updates[] = "updated_at = NOW()";
        $sql = "UPDATE lending_records SET " . implode(', ', $updates) . " WHERE id = :id RETURNING *";

        $stmt = $db->prepare($sql);
        $stmt->execute($bindings);
        $updated = $stmt->fetch();

        $this->json(['message' => 'Lending record updated.', 'data' => $updated]);
    }

    /**
     * DELETE /api/lending/{id}
     */
    public function destroy(array $params): void
    {
        $db = Database::getConnection();

        $stmt = $db->prepare('DELETE FROM lending_records WHERE id = :id');
        $stmt->execute(['id' => $params['id']]);

        if ($stmt->rowCount() === 0) {
            $this->json(['error' => 'Lending record not found.'], 404);
            return;
        }

        $this->json(['message' => 'Lending record deleted.']);
    }

    /**
     * GET /api/lending/overdue
     * Get overdue books
     */
    public function overdue(array $params): void
    {
        $db = Database::getConnection();

        // Update overdue status
        $db->exec("UPDATE lending_records SET status = 'overdue' WHERE status = 'active' AND due_date < CURRENT_DATE");

        $stmt = $db->query("
            SELECT lr.*, b.title as book_title, b.author as book_author
            FROM lending_records lr
            JOIN books b ON b.id = lr.book_id
            WHERE lr.status = 'overdue'
            ORDER BY lr.due_date ASC
        ");

        $this->json(['data' => $stmt->fetchAll()]);
    }
}
