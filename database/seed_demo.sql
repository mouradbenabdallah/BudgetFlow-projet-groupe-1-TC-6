-- ============================================================
--  CASHtoCASH — Données de démonstration
--  Exécuter : psql -U budgetflow -d budgetflow -f seed_demo.sql
-- ============================================================

-- ── Nouveaux utilisateurs ──────────────────────────────────
-- Mot de passe pour tous : password
INSERT INTO users (name, email, password, role, is_active, phone, created_at) VALUES
  ('Amira Belhaj',    'amira@demo.local',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', true, '+216 22 111 222', NOW() - INTERVAL '5 months'),
  ('Karim Trabelsi',  'karim@demo.local',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', true, '+216 55 333 444', NOW() - INTERVAL '4 months'),
  ('Sarra Mansouri',  'sarra@demo.local',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', true, '+216 98 555 666', NOW() - INTERVAL '3 months'),
  ('Yassine Hajri',   'yassine@demo.local','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', false, NULL,              NOW() - INTERVAL '1 week')
ON CONFLICT (email) DO NOTHING;

-- ── Catégories personnelles (Mourad = id 3) ───────────────
INSERT INTO categories (user_id, name, color, is_default) VALUES
  (3, 'Salaire',       '#00ed64', false),
  (3, 'Freelance',     '#006cfa', false),
  (3, 'Courses',       '#f59e0b', false),
  (3, 'Électricité',   '#a855f7', false),
  (3, 'Internet',      '#1eaedb', false),
  (3, 'Café & Resto',  '#e11d48', false);

-- ── Catégories personnelles (Demo Utilisateur = id 2) ─────
INSERT INTO categories (user_id, name, color, is_default) VALUES
  (2, 'Salaire',       '#00ed64', false),
  (2, 'Vêtements',     '#f59e0b', false),
  (2, 'Abonnements',   '#006cfa', false),
  (2, 'Pharmacie',     '#1eaedb', false);

-- ── Budgets ───────────────────────────────────────────────
INSERT INTO budgets (owner_id, name, type, period, amount_limit, start_date, created_at) VALUES
  -- Mourad — budgets personnels
  (3, 'Dépenses Quotidiennes',   'personal', 'monthly', 800.00,  DATE_TRUNC('month', NOW() - INTERVAL '4 months'), NOW() - INTERVAL '4 months'),
  (3, 'Budget Transport',        'personal', 'monthly', 250.00,  DATE_TRUNC('month', NOW() - INTERVAL '3 months'), NOW() - INTERVAL '3 months'),
  (3, 'Épargne Loisirs',         'personal', 'monthly', 400.00,  DATE_TRUNC('month', NOW()),                        NOW()),
  -- Demo Utilisateur — budget personnel
  (2, 'Budget Mensuel Personnel','personal', 'monthly', 1200.00, DATE_TRUNC('month', NOW() - INTERVAL '3 months'), NOW() - INTERVAL '3 months'),
  -- Budgets partagés
  (3, 'Appart Partagé',          'shared',   'monthly', 1500.00, DATE_TRUNC('month', NOW() - INTERVAL '2 months'), NOW() - INTERVAL '2 months'),
  (2, 'Voyage Hammamet',         'shared',   'custom',  2000.00, NOW() - INTERVAL '1 month',                       NOW() - INTERVAL '1 month'),
  (3, 'Coloc Montplaisir',       'shared',   'monthly', 900.00,  DATE_TRUNC('month', NOW()),                        NOW());

-- ── Membres des budgets partagés ──────────────────────────
-- Appart Partagé (budget id depends on INSERT order — use subquery)
INSERT INTO budget_members (budget_id, user_id)
SELECT b.id, u.id FROM budgets b, users u
WHERE b.name = 'Appart Partagé'
  AND u.email IN ('amira@demo.local', 'karim@demo.local')
ON CONFLICT DO NOTHING;

INSERT INTO budget_members (budget_id, user_id)
SELECT b.id, u.id FROM budgets b, users u
WHERE b.name = 'Voyage Hammamet'
  AND u.email IN ('demo@budgetflow.local', 'amira@demo.local', 'karim@demo.local')
ON CONFLICT DO NOTHING;

INSERT INTO budget_members (budget_id, user_id)
SELECT b.id, u.id FROM budgets b, users u
WHERE b.name = 'Coloc Montplaisir'
  AND u.email IN ('amira@demo.local', 'sarra@demo.local')
ON CONFLICT DO NOTHING;

-- ── Transactions — Mourad (budget: Dépenses Quotidiennes) ─
INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id = 3 AND name = 'Salaire'),
  'income', 2800.00, 'Salaire janvier', NOW() - INTERVAL '4 months' + INTERVAL '1 day'
FROM budgets b WHERE b.name = 'Dépenses Quotidiennes';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Alimentation'),
  'expense', 145.500, 'Monoprix — courses semaine', NOW() - INTERVAL '4 months' + INTERVAL '3 days'
FROM budgets b WHERE b.name = 'Dépenses Quotidiennes';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id = 3 AND name = 'Café & Resto'),
  'expense', 38.000, 'Déjeuner restaurant', NOW() - INTERVAL '4 months' + INTERVAL '5 days'
FROM budgets b WHERE b.name = 'Dépenses Quotidiennes';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id = 3 AND name = 'Électricité'),
  'expense', 89.300, 'Facture STEG', NOW() - INTERVAL '4 months' + INTERVAL '8 days'
FROM budgets b WHERE b.name = 'Dépenses Quotidiennes';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id = 3 AND name = 'Freelance'),
  'income', 650.00, 'Mission design UI client', NOW() - INTERVAL '4 months' + INTERVAL '12 days'
FROM budgets b WHERE b.name = 'Dépenses Quotidiennes';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id = 3 AND name = 'Courses'),
  'expense', 62.750, 'Marché central', NOW() - INTERVAL '4 months' + INTERVAL '15 days'
FROM budgets b WHERE b.name = 'Dépenses Quotidiennes';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Santé'),
  'expense', 55.000, 'Consultation médecin', NOW() - INTERVAL '3 months' + INTERVAL '2 days'
FROM budgets b WHERE b.name = 'Dépenses Quotidiennes';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id = 3 AND name = 'Salaire'),
  'income', 2800.00, 'Salaire février', NOW() - INTERVAL '3 months' + INTERVAL '1 day'
FROM budgets b WHERE b.name = 'Dépenses Quotidiennes';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id = 3 AND name = 'Internet'),
  'expense', 49.900, 'Abonnement Ooredoo Fiber', NOW() - INTERVAL '3 months' + INTERVAL '4 days'
FROM budgets b WHERE b.name = 'Dépenses Quotidiennes';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id = 3 AND name = 'Café & Resto'),
  'expense', 24.500, 'Café coworking', NOW() - INTERVAL '3 months' + INTERVAL '9 days'
FROM budgets b WHERE b.name = 'Dépenses Quotidiennes';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id = 3 AND name = 'Freelance'),
  'income', 850.00, 'Projet développement web', NOW() - INTERVAL '3 months' + INTERVAL '18 days'
FROM budgets b WHERE b.name = 'Dépenses Quotidiennes';

-- Mourad — budget Transport
INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Transport'),
  'expense', 85.000, 'Carburant — plein essence', NOW() - INTERVAL '3 months' + INTERVAL '1 day'
FROM budgets b WHERE b.name = 'Budget Transport';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Transport'),
  'expense', 42.000, 'Taxi aéroport', NOW() - INTERVAL '3 months' + INTERVAL '10 days'
FROM budgets b WHERE b.name = 'Budget Transport';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Transport'),
  'expense', 78.500, 'Carburant', NOW() - INTERVAL '2 months' + INTERVAL '5 days'
FROM budgets b WHERE b.name = 'Budget Transport';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Transport'),
  'expense', 35.000, 'Péage + Parking', NOW() - INTERVAL '2 months' + INTERVAL '18 days'
FROM budgets b WHERE b.name = 'Budget Transport';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Transport'),
  'expense', 90.000, 'Révision voiture', NOW() - INTERVAL '1 month' + INTERVAL '3 days'
FROM budgets b WHERE b.name = 'Budget Transport';

-- Mourad — Épargne Loisirs (ce mois)
INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id = 3 AND name = 'Salaire'),
  'income', 2800.00, 'Salaire du mois', NOW() - INTERVAL '2 days'
FROM budgets b WHERE b.name = 'Épargne Loisirs';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Loisirs'),
  'expense', 120.000, 'Abonnement salle de sport', NOW() - INTERVAL '5 days'
FROM budgets b WHERE b.name = 'Épargne Loisirs';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Loisirs'),
  'expense', 55.000, 'Cinema + shopping', NOW() - INTERVAL '3 days'
FROM budgets b WHERE b.name = 'Épargne Loisirs';

-- ── Transactions — Demo Utilisateur ───────────────────────
INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 2,
  (SELECT id FROM categories WHERE user_id = 2 AND name = 'Salaire'),
  'income', 3200.00, 'Salaire mensuel', NOW() - INTERVAL '3 months' + INTERVAL '1 day'
FROM budgets b WHERE b.name = 'Budget Mensuel Personnel';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 2,
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Logement'),
  'expense', 550.00, 'Loyer appartement', NOW() - INTERVAL '3 months' + INTERVAL '2 days'
FROM budgets b WHERE b.name = 'Budget Mensuel Personnel';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 2,
  (SELECT id FROM categories WHERE user_id = 2 AND name = 'Abonnements'),
  'expense', 29.900, 'Netflix + Spotify', NOW() - INTERVAL '3 months' + INTERVAL '3 days'
FROM budgets b WHERE b.name = 'Budget Mensuel Personnel';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 2,
  (SELECT id FROM categories WHERE user_id = 2 AND name = 'Vêtements'),
  'expense', 185.000, 'Shopping Azur City', NOW() - INTERVAL '2 months' + INTERVAL '7 days'
FROM budgets b WHERE b.name = 'Budget Mensuel Personnel';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 2,
  (SELECT id FROM categories WHERE user_id = 2 AND name = 'Pharmacie'),
  'expense', 42.500, 'Médicaments', NOW() - INTERVAL '2 months' + INTERVAL '14 days'
FROM budgets b WHERE b.name = 'Budget Mensuel Personnel';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 2,
  (SELECT id FROM categories WHERE user_id = 2 AND name = 'Salaire'),
  'income', 3200.00, 'Salaire mensuel', NOW() - INTERVAL '2 months' + INTERVAL '1 day'
FROM budgets b WHERE b.name = 'Budget Mensuel Personnel';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 2,
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Alimentation'),
  'expense', 230.750, 'Courses du mois — Carrefour', NOW() - INTERVAL '2 months' + INTERVAL '5 days'
FROM budgets b WHERE b.name = 'Budget Mensuel Personnel';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 2,
  (SELECT id FROM categories WHERE user_id = 2 AND name = 'Salaire'),
  'income', 3200.00, 'Salaire mensuel', NOW() - INTERVAL '1 month' + INTERVAL '1 day'
FROM budgets b WHERE b.name = 'Budget Mensuel Personnel';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 2,
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Logement'),
  'expense', 550.00, 'Loyer appartement', NOW() - INTERVAL '1 month' + INTERVAL '2 days'
FROM budgets b WHERE b.name = 'Budget Mensuel Personnel';

-- ── Transactions — Budget partagé Appart ─────────────────
INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Logement'),
  'expense', 750.00, 'Loyer — part Mourad', NOW() - INTERVAL '2 months' + INTERVAL '1 day'
FROM budgets b WHERE b.name = 'Appart Partagé';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, (SELECT id FROM users WHERE email='amira@demo.local'),
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Logement'),
  'expense', 750.00, 'Loyer — part Amira', NOW() - INTERVAL '2 months' + INTERVAL '1 day'
FROM budgets b WHERE b.name = 'Appart Partagé';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, (SELECT id FROM users WHERE email='karim@demo.local'),
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Alimentation'),
  'expense', 185.000, 'Courses collectives', NOW() - INTERVAL '2 months' + INTERVAL '10 days'
FROM budgets b WHERE b.name = 'Appart Partagé';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Autre'),
  'expense', 320.000, 'Réparation chauffe-eau', NOW() - INTERVAL '1 month' + INTERVAL '8 days'
FROM budgets b WHERE b.name = 'Appart Partagé';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, (SELECT id FROM users WHERE email='amira@demo.local'),
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Logement'),
  'expense', 750.00, 'Loyer — part Amira', NOW() - INTERVAL '1 month' + INTERVAL '1 day'
FROM budgets b WHERE b.name = 'Appart Partagé';

-- ── Transactions — Budget partagé Voyage ─────────────────
INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 2,
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Loisirs'),
  'expense', 450.00, 'Hôtel Hammamet 3 nuits', NOW() - INTERVAL '3 weeks'
FROM budgets b WHERE b.name = 'Voyage Hammamet';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, 3,
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Transport'),
  'expense', 95.000, 'Location voiture', NOW() - INTERVAL '3 weeks' + INTERVAL '1 day'
FROM budgets b WHERE b.name = 'Voyage Hammamet';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, (SELECT id FROM users WHERE email='amira@demo.local'),
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Alimentation'),
  'expense', 220.000, 'Restaurants & activités', NOW() - INTERVAL '3 weeks' + INTERVAL '2 days'
FROM budgets b WHERE b.name = 'Voyage Hammamet';

INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date) SELECT
  b.id, (SELECT id FROM users WHERE email='karim@demo.local'),
  (SELECT id FROM categories WHERE user_id IS NULL AND name = 'Loisirs'),
  'expense', 180.000, 'Excursions & souvenirs', NOW() - INTERVAL '3 weeks' + INTERVAL '3 days'
FROM budgets b WHERE b.name = 'Voyage Hammamet';
