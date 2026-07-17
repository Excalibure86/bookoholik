<?php

namespace App\Controllers;

use App\Config\Database;

/**
 * Reports Controller
 * Generate various reports on the book collection
 */
class ReportsController extends BaseController
{
    /**
     * GET /api/reports/summary
     * Overall collection summary
     */
    public function summary(array $params): void
    {
        $db = Database::getConnection();

        $stats = [];

        // Total books
        $stmt = $db->query('SELECT COUNT(*) as total FROM books');
        $stats['total_books'] = (int)$stmt->fetch()['total'];

        // Read vs unread
        $stmt = $db->query('SELECT read_status, COUNT(*) as count FROM books GROUP BY read_status');
        $readStats = $stmt->fetchAll();
        $stats['books_read'] = 0;
        $stats['books_unread'] = 0;
        foreach ($readStats as $row) {
            if ($row['read_status']) {
                $stats['books_read'] = (int)$row['count'];
            } else {
                $stats['books_unread'] = (int)$row['count'];
            }
        }

        // By language
        $stmt = $db->query('SELECT language, COUNT(*) as count FROM books GROUP BY language ORDER BY count DESC');
        $stats['by_language'] = $stmt->fetchAll();

        // Currently lent out
        $stmt = $db->query("SELECT COUNT(*) as total FROM lending_records WHERE status IN ('active', 'overdue')");
        $stats['books_lent'] = (int)$stmt->fetch()['total'];

        // Overdue
        $stmt = $db->query("SELECT COUNT(*) as total FROM lending_records WHERE status = 'overdue'");
        $stats['books_overdue'] = (int)$stmt->fetch()['total'];

        // Total users
        $stmt = $db->query('SELECT COUNT(*) as total FROM users WHERE is_active = TRUE');
        $stats['total_users'] = (int)$stmt->fetch()['total'];

        $this->json(['data' => $stats]);
    }

    /**
     * GET /api/reports/by-genre
     */
    public function byGenre(array $params): void
    {
        $db = Database::getConnection();
        $stmt = $db->query('
            SELECT genre, COUNT(*) as count, 
                   SUM(CASE WHEN read_status THEN 1 ELSE 0 END) as read_count
            FROM books 
            WHERE genre IS NOT NULL AND genre != \'\'
            GROUP BY genre 
            ORDER BY count DESC
        ');

        $this->json(['data' => $stmt->fetchAll()]);
    }

    /**
     * GET /api/reports/by-author
     */
    public function byAuthor(array $params): void
    {
        $db = Database::getConnection();
        $query = $this->getQueryParams();
        $limit = min(100, max(10, (int)($query['limit'] ?? 50)));

        $stmt = $db->prepare('
            SELECT author, COUNT(*) as count,
                   SUM(CASE WHEN read_status THEN 1 ELSE 0 END) as read_count
            FROM books 
            GROUP BY author 
            ORDER BY count DESC 
            LIMIT :limit
        ');
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $this->json(['data' => $stmt->fetchAll()]);
    }

    /**
     * GET /api/reports/by-year
     */
    public function byYear(array $params): void
    {
        $db = Database::getConnection();
        $stmt = $db->query('
            SELECT publication_year, COUNT(*) as count
            FROM books 
            WHERE publication_year IS NOT NULL
            GROUP BY publication_year 
            ORDER BY publication_year DESC
        ');

        $this->json(['data' => $stmt->fetchAll()]);
    }

    /**
     * GET /api/reports/by-location
     */
    public function byLocation(array $params): void
    {
        $db = Database::getConnection();
        $stmt = $db->query('
            SELECT location_room, location_shelf, COUNT(*) as count
            FROM books 
            WHERE location_room IS NOT NULL AND location_room != \'\'
            GROUP BY location_room, location_shelf 
            ORDER BY location_room, location_shelf
        ');

        $this->json(['data' => $stmt->fetchAll()]);
    }

    /**
     * GET /api/reports/lending-history
     */
    public function lendingHistory(array $params): void
    {
        $db = Database::getConnection();
        $query = $this->getQueryParams();

        $conditions = [];
        $bindings = [];

        if (!empty($query['from_date'])) {
            $conditions[] = "lr.lent_date >= :from_date";
            $bindings['from_date'] = $query['from_date'];
        }
        if (!empty($query['to_date'])) {
            $conditions[] = "lr.lent_date <= :to_date";
            $bindings['to_date'] = $query['to_date'];
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "SELECT lr.*, b.title as book_title, b.author as book_author
                FROM lending_records lr
                JOIN books b ON b.id = lr.book_id
                {$whereClause}
                ORDER BY lr.lent_date DESC
                LIMIT 200";

        $stmt = $db->prepare($sql);
        $stmt->execute($bindings);

        $this->json(['data' => $stmt->fetchAll()]);
    }

    /**
     * GET /api/reports/export/csv
     * Export collection as CSV
     */
    public function exportCsv(array $params): void
    {
        $db = Database::getConnection();
        $stmt = $db->query('
            SELECT title, author, genre, publication_year, language, 
                   location_room, location_shelf, read_status, isbn, 
                   edition_house, num_pages, series_name, series_position, notes
            FROM books 
            ORDER BY title
        ');
        $books = $stmt->fetchAll();

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="library_export_' . date('Y-m-d') . '.csv"');

        // BOM for UTF-8 (helps with Arabic in Excel)
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // Header row
        fputcsv($output, [
            'Title', 'Author', 'Genre', 'Publication Year', 'Language',
            'Room', 'Shelf', 'Read', 'ISBN', 'Publisher',
            'Pages', 'Series', 'Series Position', 'Notes'
        ]);

        foreach ($books as $book) {
            fputcsv($output, [
                $book['title'],
                $book['author'],
                $book['genre'],
                $book['publication_year'],
                $book['language'],
                $book['location_room'],
                $book['location_shelf'],
                $book['read_status'] ? 'Yes' : 'No',
                $book['isbn'],
                $book['edition_house'],
                $book['num_pages'],
                $book['series_name'],
                $book['series_position'],
                $book['notes'],
            ]);
        }

        fclose($output);
        exit();
    }

    /**
     * GET /api/reports/export/pdf
     * Export collection summary as PDF
     */
    public function exportPdf(array $params): void
    {
        $db = Database::getConnection();

        // Get summary data
        $stmt = $db->query('SELECT COUNT(*) as total FROM books');
        $totalBooks = $stmt->fetch()['total'];

        $stmt = $db->query('SELECT language, COUNT(*) as count FROM books GROUP BY language ORDER BY count DESC');
        $byLanguage = $stmt->fetchAll();

        $stmt = $db->query('SELECT genre, COUNT(*) as count FROM books WHERE genre != \'\' GROUP BY genre ORDER BY count DESC LIMIT 20');
        $byGenre = $stmt->fetchAll();

        $stmt = $db->query('SELECT SUM(CASE WHEN read_status THEN 1 ELSE 0 END) as read, SUM(CASE WHEN NOT read_status THEN 1 ELSE 0 END) as unread FROM books');
        $readStats = $stmt->fetch();

        // Generate HTML for PDF
        $html = '
        <html dir="ltr">
        <head><meta charset="UTF-8"><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
            h1 { color: #2c3e50; text-align: center; }
            h2 { color: #34495e; border-bottom: 1px solid #bdc3c7; padding-bottom: 5px; }
            table { width: 100%; border-collapse: collapse; margin: 10px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #3498db; color: white; }
            .stat { font-size: 18px; font-weight: bold; color: #2980b9; }
        </style></head>
        <body>
            <h1>📚 Bookoholik - Library Report</h1>
            <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
            
            <h2>Collection Summary</h2>
            <p>Total Books: <span class="stat">' . $totalBooks . '</span></p>
            <p>Read: <span class="stat">' . ($readStats['read'] ?? 0) . '</span> | 
               Unread: <span class="stat">' . ($readStats['unread'] ?? 0) . '</span></p>
            
            <h2>By Language</h2>
            <table><tr><th>Language</th><th>Count</th></tr>';

        foreach ($byLanguage as $row) {
            $html .= '<tr><td>' . htmlspecialchars(ucfirst($row['language']), ENT_QUOTES, 'UTF-8') . '</td><td>' . (int)$row['count'] . '</td></tr>';
        }
        $html .= '</table>';

        $html .= '<h2>Top Genres</h2>
            <table><tr><th>Genre</th><th>Count</th></tr>';
        foreach ($byGenre as $row) {
            $html .= '<tr><td>' . htmlspecialchars($row['genre'], ENT_QUOTES, 'UTF-8') . '</td><td>' . (int)$row['count'] . '</td></tr>';
        }
        $html .= '</table></body></html>';

        // Generate PDF using Dompdf
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="library_report_' . date('Y-m-d') . '.pdf"');
        echo $dompdf->output();
        exit();
    }
}
