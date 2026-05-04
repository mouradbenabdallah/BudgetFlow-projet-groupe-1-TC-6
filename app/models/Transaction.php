<?php

class Transaction
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findByUser(int $userId, int $limit = 8): array
    {
        $statement = $this->pdo->prepare(
            "SELECT t.id,
                    t.type,
                    t.amount,
                    t.description,
                    t.date,
                    t.created_at,
                    COALESCE(c.name, 'Sans catégorie') AS category_name,
                    COALESCE(c.color, '#8B90A7') AS category_color,
                    b.name AS budget_name
             FROM transactions t
             LEFT JOIN categories c ON c.id = t.category_id
             JOIN budgets b ON b.id = t.budget_id
             WHERE t.user_id = :user_id
             ORDER BY t.date DESC, t.created_at DESC
             LIMIT :limit"
        );
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function sumByType(int $userId, string $type, string $startDate, string $endDate): float
    {
        if (!in_array($type, ['income', 'expense'], true)) {
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
               AND t.date BETWEEN :start_date AND :end_date'
        );
        $statement->bindValue(':member_user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':transaction_user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':owner_user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':type', $type, PDO::PARAM_STR);
        $statement->bindValue(':start_date', $startDate, PDO::PARAM_STR);
        $statement->bindValue(':end_date', $endDate, PDO::PARAM_STR);
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
}
