<?php

class User
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, email, password, role, is_active, created_at
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (name, email, password, role, is_active)
             VALUES (:name, :email, :password, :role, :is_active)
             RETURNING id'
        );

        $statement->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $statement->bindValue(':email', $data['email'], PDO::PARAM_STR);
        $statement->bindValue(':password', $data['password'], PDO::PARAM_STR);
        $statement->bindValue(':role', $data['role'] ?? 'user', PDO::PARAM_STR);
        $statement->bindValue(':is_active', (bool) ($data['is_active'] ?? false), PDO::PARAM_BOOL);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, email, role, is_active, created_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE users
             SET name = COALESCE(:name, name),
                 email = COALESCE(:email, email),
                 password = COALESCE(:password, password),
                 role = COALESCE(:role, role),
                 is_active = COALESCE(:is_active, is_active)
             WHERE id = :id'
        );

        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindNullableString($statement, ':name', $data['name'] ?? null);
        $this->bindNullableString($statement, ':email', $data['email'] ?? null);
        $this->bindNullableString($statement, ':password', $data['password'] ?? null);
        $this->bindNullableString($statement, ':role', $data['role'] ?? null);

        if (array_key_exists('is_active', $data)) {
            $statement->bindValue(':is_active', (bool) $data['is_active'], PDO::PARAM_BOOL);
        } else {
            $statement->bindValue(':is_active', null, PDO::PARAM_NULL);
        }

        return $statement->execute();
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
