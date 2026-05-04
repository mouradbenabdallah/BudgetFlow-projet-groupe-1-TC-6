<?php

class Budget
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findByUser(int $userId): array
    {
        // On pré-agrège les dépenses pour éviter les doublons sur les budgets partagés.
        $statement = $this->pdo->prepare(
            "SELECT b.id,
                    b.owner_id,
                    b.name,
                    b.type,
                    b.period,
                    b.amount_limit,
                    b.start_date,
                    b.created_at,
                    COALESCE(expenses.spent, 0) AS spent
             FROM budgets b
             LEFT JOIN (
                 SELECT budget_id, COALESCE(SUM(amount), 0) AS spent
                 FROM transactions
                 WHERE type = 'expense'
                 GROUP BY budget_id
             ) expenses ON expenses.budget_id = b.id
             WHERE b.owner_id = :owner_id
                OR EXISTS (
                    SELECT 1
                    FROM budget_members bm
                    WHERE bm.budget_id = b.id
                      AND bm.user_id = :member_id
                )
             ORDER BY b.created_at DESC, b.id DESC"
        );
        $statement->bindValue(':owner_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':member_id', $userId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function getTotalSpent(int $budgetId): float
    {
        $statement = $this->pdo->prepare(
            "SELECT COALESCE(SUM(amount), 0) AS total
             FROM transactions
             WHERE budget_id = :budget_id
               AND type = 'expense'"
        );
        $statement->bindValue(':budget_id', $budgetId, PDO::PARAM_INT);
        $statement->execute();

        return (float) $statement->fetchColumn();
    }
}
