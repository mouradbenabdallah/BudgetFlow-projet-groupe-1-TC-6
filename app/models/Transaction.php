<?php

declare(strict_types=1);

/**
 * Transaction Model
 * 
 * Handles all database operations for transactions including
 * filtering, aggregation, and CRUD operations.
 * 
 * Supports personal and shared budget transactions.
 * A user can see transactions if they are:
 * - The transaction creator (t.user_id)
 * - The budget owner (b.owner_id)
 * - A member of the budget (budget_members)
 */
class Transaction
{
    private PDO $pdo;

    /**
     * Constructor - Get database instance.
     */
    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Fetch transactions for a user with optional filters.
     * 
     * This method has two calling conventions:
     * 1. findByUser($userId, $limit) - Quick fetch of recent transactions
     * 2. findByUser($userId, $filtersArray) - Filtered, paginated results
     * 
     * @param int $userId The user ID
     * @param array|int $filters Filter array or limit integer
     * @return array<array<string, mixed>> List of transaction records with category_name, budget_name
     */
    public function findByUser(int $userId, array|int $filters = []): array
    {
        // Quick mode: just get N recent transactions
        if (is_int($filters)) {
            $statement = $this->pdo->prepare(
                "SELECT t.id,
                        t.type,
                        t.amount,
                        t.description,
                        t.date,
                        t.created_at,
                        COALESCE(c.name, :missing_category) AS category_name,
                        COALESCE(c.color, :missing_color) AS category_color,
                        b.name AS budget_name
                 FROM transactions t
                 LEFT JOIN categories c ON c.id = t.category_id
                 JOIN budgets b ON b.id = t.budget_id
                 LEFT JOIN budget_members bm
                   ON bm.budget_id = t.budget_id
                  AND bm.user_id = :member_user_id
                 WHERE (t.user_id = :user_id OR b.owner_id = :owner_user_id OR bm.user_id IS NOT NULL)
                 ORDER BY t.date DESC, t.created_at DESC, t.id DESC
                 LIMIT :limit"
            );
            $statement->bindValue(':member_user_id', $userId, PDO::PARAM_INT);
            $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $statement->bindValue(':owner_user_id', $userId, PDO::PARAM_INT);
            $statement->bindValue(':limit', $filters, PDO::PARAM_INT);
            $statement->bindValue(':missing_category', 'Sans catégorie', PDO::PARAM_STR);
            $statement->bindValue(':missing_color', '#8B90A7', PDO::PARAM_STR);
            $statement->execute();

            return $statement->fetchAll();
        }

        // Full mode: with filters and pagination
        $normalized = $this->normalizeFilters($filters);
        $where = ['(t.user_id = :user_id OR b.owner_id = :owner_user_id OR bm.user_id IS NOT NULL)'];
        $params = [':user_id' => $userId, ':owner_user_id' => $userId, ':member_user_id' => $userId];

        if ($normalized['type'] !== 'all') {
            $where[] = 't.type = :type';
            $params[':type'] = $normalized['type'];
        }

        if ($normalized['budget_id'] !== null) {
            $where[] = 't.budget_id = :budget_id';
            $params[':budget_id'] = $normalized['budget_id'];
        }

        if ($normalized['category_id'] !== null) {
            $where[] = 't.category_id = :category_id';
            $params[':category_id'] = $normalized['category_id'];
        }

        if ($normalized['month_start'] !== null && $normalized['month_end'] !== null) {
            $where[] = 't.date >= :month_start AND t.date < :month_end';
            $params[':month_start'] = $normalized['month_start'];
            $params[':month_end'] = $normalized['month_end'];
        }

        $statement = $this->pdo->prepare(
            "SELECT t.id,
                t.budget_id,
                t.user_id,
                t.category_id,
                t.type,
                t.amount,
                t.description,
                t.date,
                t.created_at,
                     COALESCE(c.name, :missing_category) AS category_name,
                     COALESCE(c.color, :missing_color) AS category_color,
                b.name AS budget_name,
                b.type AS budget_type,
                b.owner_id AS budget_owner_id
             FROM transactions t
             LEFT JOIN categories c ON c.id = t.category_id
             JOIN budgets b ON b.id = t.budget_id
             LEFT JOIN budget_members bm
               ON bm.budget_id = t.budget_id
              AND bm.user_id = :member_user_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY t.date DESC, t.created_at DESC, t.id DESC
             LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $statement->bindValue(':missing_category', 'Sans catégorie', PDO::PARAM_STR);
        $statement->bindValue(':missing_color', '#8B90A7', PDO::PARAM_STR);
        $statement->bindValue(':limit', $normalized['limit'], PDO::PARAM_INT);
        $statement->bindValue(':offset', $normalized['offset'], PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Sum transactions by type for a user within a date range.
     * 
     * Convenience method that extracts month from date range and calls sumByTypeAndUser().
     * 
     * @param int $userId The user ID
     * @param string $type 'income' or 'expense'
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return float Total amount
     */
    public function sumByType(int $userId, string $type, string $startDate, string $endDate): float
    {
        return $this->sumByTypeAndUser($userId, $type, $this->extractMonthFromRange($startDate, $endDate));
    }

    /**
     * Sum transactions by type and user for a specific month.
     * 
     * Includes transactions from:
     * - Personal budgets owned by user
     * - Shared budgets where user is owner or member
     * 
     * @param int $userId The user ID
     * @param string $type 'income' or 'expense'
     * @param string $month Month in YYYY-MM format
     * @return float Total amount
     */
    public function sumByTypeAndUser(int $userId, string $type, string $month): float
    {
        if (!in_array($type, ['income', 'expense'], true)) {
            return 0.0;
        }

        $range = $this->resolveMonthRange($month);
        if ($range === null) {
            return 0.0;
        }

        // Le total inclut les transactions des budgets dont l'utilisateur est propriétaire ou membre.
        $statement = $this->pdo->prepare(
            'SELECT COALESCE(SUM(t.amount), 0) AS total
             FROM transactions t
             JOIN budgets b ON b.id = t.budget_id
             LEFT JOIN budget_members bm
               ON bm.budget_id = t.budget_id
              AND bm.user_id = :member_user_id
             WHERE (t.user_id = :transaction_user_id OR b.owner_id = :owner_user_id OR bm.user_id IS NOT NULL)
               AND t.type = :type
               AND t.date >= :start_date
               AND t.date < :end_date'
        );
        $statement->bindValue(':member_user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':transaction_user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':owner_user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':type', $type, PDO::PARAM_STR);
        $statement->bindValue(':start_date', $range['start'], PDO::PARAM_STR);
        $statement->bindValue(':end_date', $range['end'], PDO::PARAM_STR);
        $statement->execute();

        return (float) $statement->fetchColumn();
    }

    /**
     * Get expense breakdown by category for a user within a date range.
     *
     * @param int $userId The user ID
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array<array<string, mixed>> Category breakdown with name, color, amount
     */
    public function byCategory(int $userId, string $startDate, string $endDate): array
    {
        $statement = $this->pdo->prepare(
            "SELECT COALESCE(c.name, 'Sans catégorie') AS name,
                    COALESCE(c.color, '#8B90A7') AS color,
                    COALESCE(SUM(t.amount), 0) AS amount
             FROM transactions t
             LEFT JOIN categories c ON c.id = t.category_id
             JOIN budgets b ON b.id = t.budget_id
             LEFT JOIN budget_members bm
               ON bm.budget_id = t.budget_id
              AND bm.user_id = :member_user_id
             WHERE (t.user_id = :transaction_user_id OR b.owner_id = :owner_user_id OR bm.user_id IS NOT NULL)
               AND t.type = 'expense'
               AND t.date >= :start_date
               AND t.date < (:end_date::date + 1)
             GROUP BY c.id, c.name, c.color
             ORDER BY amount DESC"
        );
        $statement->bindValue(':member_user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':transaction_user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':owner_user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':start_date', $startDate, PDO::PARAM_STR);
        $statement->bindValue(':end_date', $endDate, PDO::PARAM_STR);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Get monthly income/expense evolution for the last 6 months.
     *
     * @param int $userId The user ID
     * @return array<array<string, mixed>> Monthly aggregation rows
     */
    public function monthlyEvolution(int $userId): array
    {
        $statement = $this->pdo->prepare(
            "SELECT TO_CHAR(date_trunc('month', t.date), 'YYYY-MM') AS month_key,
                    TO_CHAR(date_trunc('month', t.date), 'Mon YYYY') AS month,
                    COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) AS income,
                    COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) AS expense
             FROM transactions t
             JOIN budgets b ON b.id = t.budget_id
             LEFT JOIN budget_members bm
               ON bm.budget_id = t.budget_id
              AND bm.user_id = :member_user_id
             WHERE (t.user_id = :transaction_user_id OR b.owner_id = :owner_user_id OR bm.user_id IS NOT NULL)
               AND t.date >= date_trunc('month', CURRENT_DATE) - INTERVAL '5 months'
             GROUP BY date_trunc('month', t.date)
             ORDER BY date_trunc('month', t.date)"
        );
        $statement->bindValue(':member_user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':transaction_user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':owner_user_id', $userId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Count transactions for a user with filters.
     *
     * @param int $userId The user ID
     * @param array<string, mixed> $filters Filter parameters
     * @return int Transaction count
     */
    public function countByUser(int $userId, array $filters = []): int
    {
        $normalized = $this->normalizeFilters($filters);
        $where = ['(t.user_id = :user_id OR b.owner_id = :owner_user_id OR bm.user_id IS NOT NULL)'];
        $params = [':user_id' => $userId, ':owner_user_id' => $userId, ':member_user_id' => $userId];

        if ($normalized['type'] !== 'all') {
            $where[] = 't.type = :type';
            $params[':type'] = $normalized['type'];
        }

        if ($normalized['budget_id'] !== null) {
            $where[] = 't.budget_id = :budget_id';
            $params[':budget_id'] = $normalized['budget_id'];
        }

        if ($normalized['category_id'] !== null) {
            $where[] = 't.category_id = :category_id';
            $params[':category_id'] = $normalized['category_id'];
        }

        if ($normalized['month_start'] !== null && $normalized['month_end'] !== null) {
            $where[] = 't.date >= :month_start AND t.date < :month_end';
            $params[':month_start'] = $normalized['month_start'];
            $params[':month_end'] = $normalized['month_end'];
        }

        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM transactions t
             JOIN budgets b ON b.id = t.budget_id
             LEFT JOIN budget_members bm
               ON bm.budget_id = t.budget_id
              AND bm.user_id = :member_user_id
             WHERE ' . implode(' AND ', $where)
        );

        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    /**
     * Create a new transaction.
     *
     * @param array{budget_id: int, user_id: int, category_id: int|null, type: string, amount: string, description: string|null, date: string} $data
     * @return int The new transaction ID
     */
    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date)
             VALUES (:budget_id, :user_id, :category_id, :type, :amount, :description, :date)
             RETURNING id'
        );

        $statement->bindValue(':budget_id', (int) $data['budget_id'], PDO::PARAM_INT);
        $statement->bindValue(':user_id', (int) $data['user_id'], PDO::PARAM_INT);
        $this->bindNullableInt($statement, ':category_id', $data['category_id'] ?? null);
        $statement->bindValue(':type', (string) $data['type'], PDO::PARAM_STR);
        $statement->bindValue(':amount', (string) $data['amount'], PDO::PARAM_STR);
        $this->bindNullableString($statement, ':description', $data['description'] ?? null);
        $statement->bindValue(':date', (string) $data['date'], PDO::PARAM_STR);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    /**
     * Find a single transaction by ID with joined budget and category data.
     *
     * @param int $id The transaction ID
     * @return array<string, mixed>|null Transaction record or null
     */
    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT t.id,
                    t.budget_id,
                    t.user_id,
                    t.category_id,
                    t.type,
                    t.amount,
                    t.description,
                    t.date,
                    t.created_at,
                    COALESCE(c.name, :missing_category) AS category_name,
                    COALESCE(c.color, :missing_color) AS category_color,
                    b.name AS budget_name,
                    b.type AS budget_type,
                    b.owner_id AS budget_owner_id
             FROM transactions t
             LEFT JOIN categories c ON c.id = t.category_id
             JOIN budgets b ON b.id = t.budget_id
             WHERE t.id = :id
             LIMIT 1"
        );
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->bindValue(':missing_category', 'Sans catégorie', PDO::PARAM_STR);
        $statement->bindValue(':missing_color', '#8B90A7', PDO::PARAM_STR);
        $statement->execute();
        $transaction = $statement->fetch();

        return $transaction ?: null;
    }

    /**
     * Update an existing transaction.
     *
     * @param int $id The transaction ID
     * @param array<string, mixed> $data Fields to update
     * @return bool True on success
     */
    public function update(int $id, array $data): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE transactions
             SET budget_id = COALESCE(:budget_id, budget_id),
                 category_id = :category_id,
                 type = COALESCE(:type, type),
                 amount = COALESCE(:amount, amount),
                 description = :description,
                 date = COALESCE(:date, date)
             WHERE id = :id'
        );

        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindNullableInt($statement, ':budget_id', $data['budget_id'] ?? null);
        $this->bindNullableInt($statement, ':category_id', $data['category_id'] ?? null);
        $this->bindNullableString($statement, ':type', $data['type'] ?? null);
        $this->bindNullableString($statement, ':amount', isset($data['amount']) ? (string) $data['amount'] : null);
        $this->bindNullableString($statement, ':description', $data['description'] ?? null);
        $this->bindNullableString($statement, ':date', $data['date'] ?? null);

        return $statement->execute();
    }

    /**
     * Delete a transaction by ID.
     *
     * @param int $id The transaction ID
     * @return bool True on success
     */
    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM transactions WHERE id = :id');
        $statement->bindValue(':id', $id, PDO::PARAM_INT);

        return $statement->execute();
    }

    /**
     * Normalize and validate filter parameters.
     * 
     * Converts raw filter input into a consistent format for database queries.
     * Defaults are applied for missing or invalid values.
     * 
     * @param array<string, mixed> $filters Raw filter input
     * @return array<string, mixed> Normalized filters with keys:
     *         - type: 'all', 'income', or 'expense'
     *         - budget_id: int or null
     *         - category_id: int or null
     *         - month: YYYY-MM format
     *         - month_start, month_end: date range for the month
     *         - page, limit, offset: pagination params
     */
    private function normalizeFilters(array $filters): array
    {
        $type = (string) ($filters['type'] ?? 'all');
        if (!in_array($type, ['all', 'income', 'expense'], true)) {
            $type = 'all';
        }

        $budgetId = $this->normalizePositiveInt($filters['budget_id'] ?? null);
        $categoryId = $this->normalizePositiveInt($filters['category_id'] ?? null);
        $month = $this->normalizeMonth((string) ($filters['month'] ?? date('Y-m')));
        $page = max(1, $this->normalizePositiveInt($filters['page'] ?? 1) ?? 1);
        $limit = max(1, $this->normalizePositiveInt($filters['limit'] ?? 20) ?? 20);
        $offset = ($page - 1) * $limit;
        $range = $this->resolveMonthRange($month);

        return [
            'type' => $type,
            'budget_id' => $budgetId,
            'category_id' => $categoryId,
            'month' => $month,
            'month_start' => $range['start'] ?? null,
            'month_end' => $range['end'] ?? null,
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Normalize a value to a positive integer or null.
     * 
     * Converts various input types to a positive integer.
     * Returns null for non-numeric values, empty strings, or zero/negative numbers.
     * 
     * @param mixed $value The raw value
     * @return int|null Positive integer or null
     */
    private function normalizePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        $integer = (int) $value;

        return $integer > 0 ? $integer : null;
    }

    /**
     * Normalize a month string to YYYY-MM format.
     * 
     * Validates that the input matches YYYY-MM pattern.
     * Falls back to current month if invalid.
     * 
     * @param string $month Raw month input
     * @return string Normalized YYYY-MM or current month
     */
    private function normalizeMonth(string $month): string
    {
        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            return date('Y-m');
        }

        return $month;
    }

    /**
     * Resolve the start and end dates for a given month.
     * 
     * Converts a YYYY-MM month string to a date range covering that entire month.
     * 
     * @param string $month Month in YYYY-MM format
     * @return array{start: string, end: string}|null Date range (Y-m-d format) or null on failure
     */
    private function resolveMonthRange(string $month): ?array
    {
        $date = DateTimeImmutable::createFromFormat('Y-m', $month);
        if ($date === false) {
            return null;
        }

        $start = $date->modify('first day of this month')->format('Y-m-d');
        $end = $date->modify('first day of next month')->format('Y-m-d');

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Extract the month from a date range (uses the start date).
     * 
     * Convenience method to get YYYY-MM from a date range.
     * 
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return string Month in YYYY-MM format (falls back to current month)
     */
    private function extractMonthFromRange(string $startDate, string $endDate): string
    {
        $start = DateTimeImmutable::createFromFormat('Y-m-d', $startDate);
        $end = DateTimeImmutable::createFromFormat('Y-m-d', $endDate);

        if ($start === false || $end === false) {
            return date('Y-m');
        }

        return $start->format('Y-m');
    }

    /**
     * Bind a nullable string value to a PDO statement parameter.
     * 
     * Binds null if value is null, otherwise binds as string.
     * 
     * @param PDOStatement $statement The prepared statement
     * @param string $key The parameter name (with colon prefix)
     * @param string|null $value The value to bind
     */
    private function bindNullableString(PDOStatement $statement, string $key, ?string $value): void
    {
        if ($value === null) {
            $statement->bindValue($key, null, PDO::PARAM_NULL);
            return;
        }

        $statement->bindValue($key, $value, PDO::PARAM_STR);
    }

    /**
     * Bind a nullable integer value to a PDO statement parameter.
     * 
     * Binds null if value is null/empty, otherwise binds as integer.
     * 
     * @param PDOStatement $statement The prepared statement
     * @param string $key The parameter name (with colon prefix)
     * @param mixed $value The value to bind
     */
    private function bindNullableInt(PDOStatement $statement, string $key, mixed $value): void
    {
        if ($value === null || $value === '') {
            $statement->bindValue($key, null, PDO::PARAM_NULL);
            return;
        }

        $statement->bindValue($key, (int) $value, PDO::PARAM_INT);
    }
}
