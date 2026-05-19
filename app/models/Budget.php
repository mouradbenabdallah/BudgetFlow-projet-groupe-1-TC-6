<?php

declare(strict_types=1);

/**
 * Budget Model
 * 
 * Handles all database operations for the budgets table.
 * Supports personal and shared budgets with member management.
 * 
 * Budget types:
 * - personal: owned by a single user
 * - shared: can have multiple members
 */
class Budget
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
     * Fetch all budgets owned by or shared with a user.
     * 
     * Returns budgets where the user is:
     * - The owner (b.owner_id)
     * - A member (budget_members table)
     * 
     * Also pre-aggregates expenses to avoid duplicate sums on shared budgets.
     * 
     * @param int $userId The user ID
     * @return array<array<string, mixed>> List of budget records with spent amount
     */
    public function findByUser(int $userId): array
    {
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

    /**
     * Fetch a budget by its ID.
     * 
     * @param int $id Budget ID
     * @return array<string, mixed>|null Budget record or null if not found
     */
    public function findById(int $id): ?array
    {
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
             WHERE b.id = :id"
        );
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        $result = $statement->fetch();

        return $result ?: null;
    }

    /**
     * Create a new budget.
     * 
     * @param array<string, mixed> $data Budget data (name, type, period, amount_limit, start_date, owner_id)
     * @return int ID of the newly created budget
     */
    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO budgets (owner_id, name, type, period, amount_limit, start_date)
             VALUES (:owner_id, :name, :type, :period, :amount_limit, :start_date)
             RETURNING id"
        );
        $statement->bindValue(':owner_id', $data['owner_id'], PDO::PARAM_INT);
        $statement->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $statement->bindValue(':type', $data['type'], PDO::PARAM_STR);
        $statement->bindValue(':period', $data['period'], PDO::PARAM_STR);
        $statement->bindValue(':amount_limit', $data['amount_limit'] ?? null, $data['amount_limit'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $statement->bindValue(':start_date', $data['start_date'] ?? date('Y-m-d'), PDO::PARAM_STR);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    /**
     * Update an existing budget.
     * 
     * @param int $id Budget ID to update
     * @param array<string, mixed> $data Updated budget data
     * @return bool True on success
     */
    public function update(int $id, array $data): bool
    {
        $statement = $this->pdo->prepare(
            "UPDATE budgets
             SET name = :name,
                 type = :type,
                 period = :period,
                 amount_limit = :amount_limit,
                 start_date = :start_date
             WHERE id = :id"
        );
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $statement->bindValue(':type', $data['type'], PDO::PARAM_STR);
        $statement->bindValue(':period', $data['period'], PDO::PARAM_STR);
        $statement->bindValue(':amount_limit', $data['amount_limit'] ?? null, $data['amount_limit'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $statement->bindValue(':start_date', $data['start_date'] ?? date('Y-m-d'), PDO::PARAM_STR);

        return $statement->execute();
    }

    /**
     * Delete a budget.
     * 
     * @param int $id Budget ID to delete
     * @return bool True on success
     */
    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare("DELETE FROM budgets WHERE id = :id");
        $statement->bindValue(':id', $id, PDO::PARAM_INT);

        return $statement->execute();
    }

    /**
     * Get all members of a shared budget.
     * 
     * @param int $budgetId The budget ID
     * @return array<array<string, mixed>> List of member records with user info
     */
    public function getMembers(int $budgetId): array
    {
        $statement = $this->pdo->prepare(
            "SELECT u.id, u.name, u.email
             FROM budget_members bm
             JOIN users u ON u.id = bm.user_id
             WHERE bm.budget_id = :budget_id
             ORDER BY u.name ASC"
        );
        $statement->bindValue(':budget_id', $budgetId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Add a member to a shared budget.
     * 
     * @param int $budgetId The budget ID
     * @param int $userId The user ID to add
     * @return bool True on success
     */
    public function addMember(int $budgetId, int $userId): bool
    {
        try {
            $statement = $this->pdo->prepare(
                "INSERT INTO budget_members (budget_id, user_id)
                 VALUES (:budget_id, :user_id)"
            );
            $statement->bindValue(':budget_id', $budgetId, PDO::PARAM_INT);
            $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $statement->execute();

            return true;
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                return false;
            }
            throw $e;
        }
    }

    /**
     * Remove a member from a shared budget.
     * 
     * @param int $budgetId The budget ID
     * @param int $userId The user ID to remove
     * @return bool True on success
     */
    public function removeMember(int $budgetId, int $userId): bool
    {
        $statement = $this->pdo->prepare(
            "DELETE FROM budget_members
             WHERE budget_id = :budget_id AND user_id = :user_id"
        );
        $statement->bindValue(':budget_id', $budgetId, PDO::PARAM_INT);
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);

        return $statement->execute();
    }

    /**
     * Calculate total expenses for a specific budget.
     * 
     * @param int $budgetId The budget ID
     * @return float Total amount spent on expenses
     */
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

    /**
     * Calculate total income for a specific budget.
     * 
     * @param int $budgetId The budget ID
     * @return float Total amount of income
     */
    public function getTotalIncome(int $budgetId): float
    {
        $statement = $this->pdo->prepare(
            "SELECT COALESCE(SUM(amount), 0) AS total
             FROM transactions
             WHERE budget_id = :budget_id
               AND type = 'income'"
        );
        $statement->bindValue(':budget_id', $budgetId, PDO::PARAM_INT);
        $statement->execute();

        return (float) $statement->fetchColumn();
    }

    /**
     * Get category breakdown of expenses for a budget.
     * 
     * @param int $budgetId The budget ID
     * @return array<array<string, mixed>> List of categories with totals
     */
    public function getCategoryBreakdown(int $budgetId): array
    {
        $statement = $this->pdo->prepare(
            "SELECT c.id, c.name, c.color, COALESCE(SUM(t.amount), 0) AS total
             FROM categories c
             LEFT JOIN transactions t ON t.category_id = c.id
                                       AND t.budget_id = :budget_id
                                       AND t.type = 'expense'
             GROUP BY c.id
             HAVING COALESCE(SUM(t.amount), 0) > 0
             ORDER BY total DESC"
        );
        $statement->bindValue(':budget_id', $budgetId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Check if a budget belongs to a user (as owner or member).
     * 
     * @param int $budgetId The budget ID
     * @param int $userId The user ID
     * @return bool True if user has access to the budget
     */
    public function belongsToUser(int $budgetId, int $userId): bool
    {
        $budget = $this->findById($budgetId);
        if ($budget === null) {
            return false;
        }

        if ((int) ($budget['owner_id'] ?? 0) === $userId) {
            return true;
        }

        $members = $this->getMembers($budgetId);
        foreach ($members as $member) {
            if ((int) ($member['id'] ?? 0) === $userId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user is the owner of a budget.
     * 
     * @param int $budgetId The budget ID
     * @param int $userId The user ID
     * @return bool True if user is the owner
     */
    public function isOwner(int $budgetId, int $userId): bool
    {
        $budget = $this->findById($budgetId);
        if ($budget === null) {
            return false;
        }

        return (int) ($budget['owner_id'] ?? 0) === $userId;
    }

    /**
     * Find a user by email address.
     * 
     * @param string $email User email
     * @return array<string, mixed>|null User record or null if not found
     */
    public function findUserByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT id, name, email, role, is_active
             FROM users
             WHERE LOWER(email) = LOWER(:email)"
        );
        $statement->bindValue(':email', $email, PDO::PARAM_STR);
        $statement->execute();

        $result = $statement->fetch();

        return $result ?: null;
    }

    /**
     * Get transactions for a specific budget with optional type filter.
     * 
     * @param int $budgetId The budget ID
     * @param string|null $type Filter by type ('income' or 'expense'), null for all
     * @return array<array<string, mixed>> List of transactions with user and category info
     */
    public function getTransactions(int $budgetId, ?string $type = null): array
    {
        $sql = "
            SELECT t.id, t.type, t.amount, t.description, t.date, t.created_at,
                   u.id AS user_id, u.name AS user_name, u.email AS user_email,
                   c.id AS category_id, c.name AS category_name, c.color AS category_color
            FROM transactions t
            JOIN users u ON u.id = t.user_id
            LEFT JOIN categories c ON c.id = t.category_id
            WHERE t.budget_id = :budget_id
        ";

        if ($type !== null) {
            $sql .= " AND t.type = :type";
        }

        $sql .= " ORDER BY t.date DESC, t.id DESC";

        $statement = $this->pdo->prepare($sql);
        $statement->bindValue(':budget_id', $budgetId, PDO::PARAM_INT);
        if ($type !== null) {
            $statement->bindValue(':type', $type, PDO::PARAM_STR);
        }
        $statement->execute();

        return $statement->fetchAll();
    }
}
