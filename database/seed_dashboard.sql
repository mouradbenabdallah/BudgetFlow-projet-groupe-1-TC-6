-- Données de démonstration pour tester le dashboard.
-- Identifiants : demo@budgetflow.local / password

DO $$
DECLARE
    demo_user_id INT;
    budget_month_id INT;
    budget_shared_id INT;
    cat_food INT;
    cat_transport INT;
    cat_home INT;
    cat_health INT;
    cat_leisure INT;
    cat_studies INT;
    cat_other INT;
BEGIN
    DELETE FROM users WHERE email = 'demo@budgetflow.local';

    INSERT INTO users (name, email, password, role, is_active)
    VALUES (
        'Demo BudgetFlow',
        'demo@budgetflow.local',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'user',
        true
    )
    RETURNING id INTO demo_user_id;

    SELECT id INTO cat_food FROM categories WHERE name = 'Alimentation' ORDER BY is_default DESC LIMIT 1;
    SELECT id INTO cat_transport FROM categories WHERE name = 'Transport' ORDER BY is_default DESC LIMIT 1;
    SELECT id INTO cat_home FROM categories WHERE name = 'Logement' ORDER BY is_default DESC LIMIT 1;
    SELECT id INTO cat_health FROM categories WHERE name = 'Santé' ORDER BY is_default DESC LIMIT 1;
    SELECT id INTO cat_leisure FROM categories WHERE name = 'Loisirs' ORDER BY is_default DESC LIMIT 1;
    SELECT id INTO cat_studies FROM categories WHERE name = 'Études' ORDER BY is_default DESC LIMIT 1;
    SELECT id INTO cat_other FROM categories WHERE name = 'Autre' ORDER BY is_default DESC LIMIT 1;

    IF cat_other IS NULL THEN
        INSERT INTO categories (user_id, name, color, is_default)
        VALUES (NULL, 'Autre', '#8B90A7', true)
        RETURNING id INTO cat_other;
    END IF;

    cat_food := COALESCE(cat_food, cat_other);
    cat_transport := COALESCE(cat_transport, cat_other);
    cat_home := COALESCE(cat_home, cat_other);
    cat_health := COALESCE(cat_health, cat_other);
    cat_leisure := COALESCE(cat_leisure, cat_other);
    cat_studies := COALESCE(cat_studies, cat_other);

    INSERT INTO budgets (owner_id, name, type, period, amount_limit, start_date)
    VALUES (demo_user_id, 'Budget mensuel', 'personal', 'monthly', 9000.00, date_trunc('month', CURRENT_DATE)::date)
    RETURNING id INTO budget_month_id;

    INSERT INTO budgets (owner_id, name, type, period, amount_limit, start_date)
    VALUES (demo_user_id, 'Appartement partagé', 'shared', 'monthly', 600.00, date_trunc('month', CURRENT_DATE)::date)
    RETURNING id INTO budget_shared_id;

    INSERT INTO budget_members (budget_id, user_id)
    VALUES
        (budget_month_id, demo_user_id),
        (budget_shared_id, demo_user_id);

    INSERT INTO transactions (budget_id, user_id, category_id, type, amount, description, date)
    VALUES
        (budget_month_id, demo_user_id, cat_other, 'income', 3200.00, 'Salaire', CURRENT_DATE - 3),
        (budget_month_id, demo_user_id, cat_other, 'income', 450.00, 'Mission freelance', CURRENT_DATE - 1),
        (budget_month_id, demo_user_id, cat_home, 'expense', 920.00, 'Loyer', CURRENT_DATE - 2),
        (budget_month_id, demo_user_id, cat_food, 'expense', 210.50, 'Courses', CURRENT_DATE - 1),
        (budget_month_id, demo_user_id, cat_transport, 'expense', 76.00, 'Transport', CURRENT_DATE - 4),
        (budget_month_id, demo_user_id, cat_health, 'expense', 145.00, 'Pharmacie', CURRENT_DATE - 6),
        (budget_month_id, demo_user_id, cat_leisure, 'expense', 160.00, 'Sortie', CURRENT_DATE - 7),
        (budget_month_id, demo_user_id, cat_studies, 'expense', 430.00, 'Formation', CURRENT_DATE - 8),
        (budget_shared_id, demo_user_id, cat_home, 'expense', 260.00, 'Électricité', CURRENT_DATE - 5),
        (budget_shared_id, demo_user_id, cat_home, 'expense', 64.00, 'Internet', CURRENT_DATE - 3),
        (budget_shared_id, demo_user_id, cat_food, 'expense', 340.00, 'Courses communes', CURRENT_DATE - 2),
        (budget_month_id, demo_user_id, cat_other, 'income', 3050.00, 'Salaire M-5', (date_trunc('month', CURRENT_DATE) - INTERVAL '5 months')::date + 4),
        (budget_month_id, demo_user_id, cat_food, 'expense', 820.00, 'Dépenses M-5', (date_trunc('month', CURRENT_DATE) - INTERVAL '5 months')::date + 10),
        (budget_month_id, demo_user_id, cat_other, 'income', 3100.00, 'Salaire M-4', (date_trunc('month', CURRENT_DATE) - INTERVAL '4 months')::date + 4),
        (budget_month_id, demo_user_id, cat_food, 'expense', 980.00, 'Dépenses M-4', (date_trunc('month', CURRENT_DATE) - INTERVAL '4 months')::date + 10),
        (budget_month_id, demo_user_id, cat_other, 'income', 3100.00, 'Salaire M-3', (date_trunc('month', CURRENT_DATE) - INTERVAL '3 months')::date + 4),
        (budget_month_id, demo_user_id, cat_home, 'expense', 1220.00, 'Dépenses M-3', (date_trunc('month', CURRENT_DATE) - INTERVAL '3 months')::date + 10),
        (budget_month_id, demo_user_id, cat_other, 'income', 3180.00, 'Salaire M-2', (date_trunc('month', CURRENT_DATE) - INTERVAL '2 months')::date + 4),
        (budget_month_id, demo_user_id, cat_transport, 'expense', 1140.00, 'Dépenses M-2', (date_trunc('month', CURRENT_DATE) - INTERVAL '2 months')::date + 10),
        (budget_month_id, demo_user_id, cat_other, 'income', 3200.00, 'Salaire M-1', (date_trunc('month', CURRENT_DATE) - INTERVAL '1 month')::date + 4),
        (budget_month_id, demo_user_id, cat_leisure, 'expense', 1320.00, 'Dépenses M-1', (date_trunc('month', CURRENT_DATE) - INTERVAL '1 month')::date + 10);
END $$;


