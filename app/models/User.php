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
            'SELECT id, name, email, role, is_active, phone, preferences, last_login_at, created_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    /**
     * Met à jour le profil visible (nom, email, téléphone) d'un utilisateur.
     *
     * @param int         $id    User ID
     * @param string      $name  Nouveau nom
     * @param string      $email Nouvel email
     * @param string|null $phone Numéro de téléphone (null pour effacer)
     */
    public function updateProfile(int $id, string $name, string $email, ?string $phone): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET name = :name, email = :email, phone = :phone WHERE id = :id'
        );
        $stmt->bindValue(':id',    $id,    PDO::PARAM_INT);
        $stmt->bindValue(':name',  $name,  PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':phone', $phone, $phone !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);

        return $stmt->execute();
    }

    /**
     * Sauvegarde les préférences (JSON) d'un utilisateur.
     *
     * @param int    $id    User ID
     * @param string $prefs JSON encodé
     */
    public function updatePreferences(int $id, string $prefs): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET preferences = :prefs WHERE id = :id');
        $stmt->bindValue(':id',    $id,    PDO::PARAM_INT);
        $stmt->bindValue(':prefs', $prefs, PDO::PARAM_STR);

        return $stmt->execute();
    }

    /**
     * Enregistre l'horodatage de la dernière connexion.
     *
     * @param int $id User ID
     */
    public function touchLastLogin(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
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
     * Retourne tous les utilisateurs filtrés avec compteurs budgets/transactions.
     *
     * @param string $filter all|pending|active|admin
     * @param int $page Page (1-indexed)
     * @param int $perPage Nombre par page
     * @return array<array<string, mixed>>
     */
    public function findAllWithFilter(string $filter, int $page = 1, int $perPage = 15): array
    {
        $where = $this->buildFilterWhere($filter);
        $offset = ($page - 1) * $perPage;

        $statement = $this->pdo->prepare(
            "SELECT u.id, u.name, u.email, u.role, u.is_active, u.created_at,
                    COUNT(DISTINCT b.id)  AS budget_count,
                    COUNT(DISTINCT t.id)  AS transaction_count,
                    MAX(t.created_at)     AS last_activity
             FROM users u
             LEFT JOIN budgets      b ON b.owner_id  = u.id
             LEFT JOIN transactions t ON t.user_id   = u.id
             WHERE {$where}
             GROUP BY u.id, u.name, u.email, u.role, u.is_active, u.created_at
             ORDER BY u.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $statement->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Compte le nombre d'utilisateurs pour un filtre donné.
     *
     * @param string $filter all|pending|active|admin
     */
    public function countByFilter(string $filter): int
    {
        $where = $this->buildFilterWhere($filter);
        $statement = $this->pdo->query("SELECT COUNT(*) FROM users u WHERE {$where}");

        return (int) $statement->fetchColumn();
    }

    /**
     * Retourne les utilisateurs en attente de validation (is_active=false, role=user).
     *
     * @param int $limit Nombre maximum
     * @return array<array<string, mixed>>
     */
    public function findAllPending(int $limit = 5): array
    {
        $statement = $this->pdo->prepare(
            "SELECT id, name, email, role, is_active, created_at
             FROM users
             WHERE is_active = false AND role = 'user'
             ORDER BY created_at DESC
             LIMIT :limit"
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Active un compte utilisateur (is_active = true).
     *
     * @param int $id User ID
     */
    public function activate(int $id): bool
    {
        $statement = $this->pdo->prepare("UPDATE users SET is_active = true WHERE id = :id");
        $statement->bindValue(':id', $id, PDO::PARAM_INT);

        return $statement->execute();
    }

    /**
     * Change le rôle d'un utilisateur.
     *
     * @param int $id User ID
     * @param string $role 'user' ou 'admin'
     */
    public function setRole(int $id, string $role): bool
    {
        $statement = $this->pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
        $statement->bindValue(':id',   $id,   PDO::PARAM_INT);
        $statement->bindValue(':role', $role, PDO::PARAM_STR);

        return $statement->execute();
    }

    /**
     * Supprime un utilisateur.
     *
     * @param int $id User ID
     */
    public function deleteById(int $id): bool
    {
        $statement = $this->pdo->prepare("DELETE FROM users WHERE id = :id");
        $statement->bindValue(':id', $id, PDO::PARAM_INT);

        return $statement->execute();
    }

    /**
     * Retourne tous les administrateurs actifs (pour les emails d'alerte).
     *
     * @return array<array<string, mixed>>
     */
    public function findAllAdmins(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, name, email FROM users WHERE role = 'admin' AND is_active = true"
        );
        return $stmt->fetchAll();
    }

    /**
     * Retourne les statistiques personnelles d'un utilisateur.
     *
     * @param int $userId
     * @return array{budgets_count:int, transactions_count:int, total_spent:float, member_since:string|null}
     */
    public function getStats(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                COUNT(DISTINCT b.id)  AS budgets_count,
                COUNT(DISTINCT t.id)  AS transactions_count,
                COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) AS total_spent,
                u.created_at          AS member_since
             FROM users u
             LEFT JOIN budgets      b ON b.owner_id = u.id
             LEFT JOIN transactions t ON t.user_id  = u.id
             WHERE u.id = :id
             GROUP BY u.created_at"
        );
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        return $row ?: [
            'budgets_count'      => 0,
            'transactions_count' => 0,
            'total_spent'        => 0.0,
            'member_since'       => null,
        ];
    }

    /**
     * Construit la clause WHERE SQL selon le filtre admin.
     */
    private function buildFilterWhere(string $filter): string
    {
        return match ($filter) {
            'pending' => "u.is_active = false AND u.role = 'user'",
            'active'  => "u.is_active = true  AND u.role = 'user'",
            'admin'   => "u.role = 'admin'",
            default   => '1=1',
        };
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
