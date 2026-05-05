<?php

declare(strict_types=1);

/**
 * Category Model
 *
 * Handles all database operations for the categories table.
 * Categories can be global (user_id IS NULL) or personal (user_id = X).
 */
class Category
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Fetch all categories available to a user (global + personal).
     * Includes aggregated income/expense counts and totals.
     *
     * @param int $userId The user ID
     * @return array<array<string, mixed>> List of category records
     */
    public function findAllForUser(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT c.id, c.user_id, c.name, c.color, c.is_default,
                    COALESCE(COUNT(t.id), 0) AS transaction_count,
                    COALESCE(SUM(CASE WHEN t.type = \'income\' THEN t.amount ELSE 0 END), 0) AS total_income,
                    COALESCE(SUM(CASE WHEN t.type = \'expense\' THEN t.amount ELSE 0 END), 0) AS total_expense,
                    COALESCE(SUM(t.amount), 0) AS total
             FROM categories c
             LEFT JOIN transactions t ON t.category_id = c.id
             WHERE c.user_id IS NULL OR c.user_id = :user_id
             GROUP BY c.id, c.user_id, c.name, c.color, c.is_default
             ORDER BY c.is_default DESC, c.name ASC'
        );
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Find a single category by ID.
     *
     * @param int $id The category ID
     * @return array<string, mixed>|null Category record or null
     */
    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, user_id, name, color, is_default
             FROM categories
             WHERE id = :id
             LIMIT 1'
        );
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();
        $row = $statement->fetch();

        return $row ?: null;
    }

    /**
     * Create a new personal category.
     *
     * @param array{name: string, color: string, user_id: int} $data
     * @return int The new category ID
     */
    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO categories (user_id, name, color, is_default)
             VALUES (:user_id, :name, :color, :is_default)
             RETURNING id'
        );

        $statement->bindValue(':user_id', (int) $data['user_id'], PDO::PARAM_INT);
        $statement->bindValue(':name', (string) $data['name'], PDO::PARAM_STR);
        $statement->bindValue(':color', (string) $data['color'], PDO::PARAM_STR);
        $statement->bindValue(':is_default', false, PDO::PARAM_BOOL);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    /**
     * Update category name and/or color.
     *
     * @param int $id The category ID
     * @param array{name?: string|null, color?: string|null} $data Fields to update
     * @return bool True on success
     */
    public function update(int $id, array $data): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE categories
             SET name = COALESCE(:name, name),
                 color = COALESCE(:color, color)
             WHERE id = :id'
        );

        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindNullableString($statement, ':name', $data['name'] ?? null);
        $this->bindNullableString($statement, ':color', $data['color'] ?? null);

        return $statement->execute();
    }

    /**
     * Delete a category. Transactions referencing it will have category_id set to NULL.
     *
     * @param int $id The category ID
     * @return bool True on success
     */
    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM categories WHERE id = :id');
        $statement->bindValue(':id', $id, PDO::PARAM_INT);

        return $statement->execute();
    }

    /**
     * Count transactions using a specific category.
     *
     * @param int $categoryId The category ID
     * @return int Number of transactions
     */
    public function isUsedInTransactions(int $categoryId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM transactions
             WHERE category_id = :category_id'
        );
        $statement->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    /**
     * Bind a nullable string value to a PDO statement parameter.
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
}
