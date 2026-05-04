<?php

class Category
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findAll(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, user_id, name, color, is_default
             FROM categories
             ORDER BY is_default DESC, name ASC'
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    public function findByUser(int $userId): array
    {
        // Les catégories par défaut ont user_id à NULL et restent visibles par tous.
        $statement = $this->pdo->prepare(
            'SELECT id, user_id, name, color, is_default
             FROM categories
             WHERE user_id IS NULL OR user_id = :user_id
             ORDER BY is_default DESC, name ASC'
        );
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
