<?php

class Category
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAllForUser(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT c.id, c.user_id, c.name, c.color, c.is_default,
                    COUNT(t.id) AS transaction_count,
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

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM categories WHERE id = :id');
        $statement->bindValue(':id', $id, PDO::PARAM_INT);

        return $statement->execute();
    }

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

    private function bindNullableString(PDOStatement $statement, string $key, ?string $value): void
    {
        if ($value === null) {
            $statement->bindValue($key, null, PDO::PARAM_NULL);
            return;
        }

        $statement->bindValue($key, $value, PDO::PARAM_STR);
    }
}
