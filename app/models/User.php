<?php

declare(strict_types=1);

/**
 * User Model
 *
 * Handles all database operations for the users table including
 * authentication, registration, and profile management.
 */
class User
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Find a user by email address.
     *
     * @param string $email The email to search for
     * @return array<string, mixed>|null User record or null
     */
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

    /**
     * Create a new user.
     *
     * @param array{name: string, email: string, password: string, role?: string, is_active?: bool} $data User data
     * @return int The new user ID
     */
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

    /**
     * Find a user by ID (excludes password).
     *
     * @param int $id The user ID
     * @return array<string, mixed>|null User record or null
     */
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

    /**
     * Update user fields. Unspecified fields retain their current values.
     *
     * @param int $id The user ID
     * @param array<string, mixed> $data Fields to update
     * @return bool True on success
     */
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
