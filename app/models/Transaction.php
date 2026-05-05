<?php

class Transaction
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findByUser(int $userId, array|int $filters = []): array
    {
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
                 WHERE t.user_id = :user_id
                 ORDER BY t.date DESC, t.created_at DESC, t.id DESC
                 LIMIT :limit"
            );
            $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $statement->bindValue(':limit', $filters, PDO::PARAM_INT);
            $statement->bindValue(':missing_category', 'Sans catégorie', PDO::PARAM_STR);
            $statement->bindValue(':missing_color', '#8B90A7', PDO::PARAM_STR);
            $statement->execute();

            return $statement->fetchAll();
        }

        $normalized = $this->normalizeFilters($filters);
        $where = ['t.user_id = :user_id'];
        $params = [':user_id' => $userId];

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

    public function sumByType(int $userId, string $type, string $startDate, string $endDate): float
    {
        return $this->sumByTypeAndUser($userId, $type, $this->extractMonthFromRange($startDate, $endDate));
    }

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

    public function byCategory(int $userId, string $startDate, string $endDate): array
    {
        $statement = $this->pdo->prepare(
            "SELECT COALESCE(c.name, 'Sans catégorie') AS name,
                    COALESCE(c.color, '#8B90A7') AS color,
                    COALESCE(SUM(t.amount), 0) AS amount
             FROM transactions t
             LEFT JOIN categories c ON c.id = t.category_id
             WHERE t.user_id = :user_id
               AND t.type = 'expense'
               AND t.date BETWEEN :start_date AND :end_date
             GROUP BY c.id, c.name, c.color
             ORDER BY amount DESC"
        );
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':start_date', $startDate, PDO::PARAM_STR);
        $statement->bindValue(':end_date', $endDate, PDO::PARAM_STR);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function monthlyEvolution(int $userId): array
    {
        $statement = $this->pdo->prepare(
            "SELECT TO_CHAR(date_trunc('month', t.date), 'YYYY-MM') AS month_key,
                    TO_CHAR(date_trunc('month', t.date), 'Mon YYYY') AS month,
                    COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) AS income,
                    COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) AS expense
             FROM transactions t
             WHERE t.user_id = :user_id
               AND t.date >= date_trunc('month', CURRENT_DATE) - INTERVAL '5 months'
             GROUP BY date_trunc('month', t.date)
             ORDER BY date_trunc('month', t.date)"
        );
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function countByUser(int $userId, array $filters = []): int
    {
        $normalized = $this->normalizeFilters($filters);
        $where = ['t.user_id = :user_id'];
        $params = [':user_id' => $userId];

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
             WHERE ' . implode(' AND ', $where)
        );

        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $statement->execute();

        return (int) $statement->fetchColumn();
    }

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

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM transactions WHERE id = :id');
        $statement->bindValue(':id', $id, PDO::PARAM_INT);

        return $statement->execute();
    }

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

    private function normalizePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        $integer = (int) $value;

        return $integer > 0 ? $integer : null;
    }

    private function normalizeMonth(string $month): string
    {
        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            return date('Y-m');
        }

        return $month;
    }

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

    private function extractMonthFromRange(string $startDate, string $endDate): string
    {
        $start = DateTimeImmutable::createFromFormat('Y-m-d', $startDate);
        $end = DateTimeImmutable::createFromFormat('Y-m-d', $endDate);

        if ($start === false || $end === false) {
            return date('Y-m');
        }

        return $start->format('Y-m');
    }

    private function bindNullableString(PDOStatement $statement, string $key, ?string $value): void
    {
        if ($value === null) {
            $statement->bindValue($key, null, PDO::PARAM_NULL);
            return;
        }

        $statement->bindValue($key, $value, PDO::PARAM_STR);
    }

    private function bindNullableInt(PDOStatement $statement, string $key, mixed $value): void
    {
        if ($value === null || $value === '') {
            $statement->bindValue($key, null, PDO::PARAM_NULL);
            return;
        }

        $statement->bindValue($key, (int) $value, PDO::PARAM_INT);
    }
}
