-- Schéma initial BudgetFlow.
-- Ce fichier est exécuté automatiquement par PostgreSQL seulement lors de la création du volume.

-- Comptes utilisateurs : inscription, connexion, rôle et validation admin.
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(150) UNIQUE,
    password VARCHAR(255),
    role VARCHAR(10) DEFAULT 'user' CHECK (role IN ('user', 'admin')),
    is_active BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Catégories globales ou personnelles. user_id = NULL signifie catégorie par défaut.
CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(80),
    color VARCHAR(7) DEFAULT '#6C63FF',
    is_default BOOLEAN DEFAULT false
);

-- Budgets individuels ou partagés créés par un utilisateur propriétaire.
CREATE TABLE IF NOT EXISTS budgets (
    id SERIAL PRIMARY KEY,
    owner_id INT REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(100),
    type VARCHAR(10) CHECK (type IN ('personal', 'shared')),
    period VARCHAR(10) CHECK (period IN ('weekly', 'monthly', 'custom')),
    amount_limit DECIMAL(12, 2),
    start_date DATE DEFAULT CURRENT_DATE,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Revenus et dépenses rattachés à un budget, un utilisateur et une catégorie.
CREATE TABLE IF NOT EXISTS transactions (
    id SERIAL PRIMARY KEY,
    budget_id INT REFERENCES budgets(id) ON DELETE CASCADE,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    category_id INT REFERENCES categories(id) ON DELETE SET NULL,
    type VARCHAR(10) CHECK (type IN ('income', 'expense')),
    amount DECIMAL(12, 2) CHECK (amount > 0),
    description VARCHAR(255),
    date DATE DEFAULT CURRENT_DATE,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Membres invités dans un budget partagé.
CREATE TABLE IF NOT EXISTS budget_members (
    id SERIAL PRIMARY KEY,
    budget_id INT REFERENCES budgets(id) ON DELETE CASCADE,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (budget_id, user_id)
);

-- Compte admin de développement.
-- Mot de passe en clair pour tester : password
INSERT INTO users (name, email, password, role, is_active)
VALUES (
    'Administrateur BudgetFlow',
    'admin@budgetflow.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    true
)
ON CONFLICT (email) DO NOTHING;

-- Catégories par défaut visibles par tous les utilisateurs.
INSERT INTO categories (user_id, name, color, is_default)
VALUES
    (NULL, 'Alimentation', '#00ED64', true),
    (NULL, 'Transport', '#006CFA', true),
    (NULL, 'Logement', '#00684A', true),
    (NULL, 'Santé', '#1EAEDB', true),
    (NULL, 'Loisirs', '#B8C4C2', true),
    (NULL, 'Études', '#3D4F58', true),
    (NULL, 'Autre', '#6C63FF', true);
