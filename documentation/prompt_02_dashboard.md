# PROMPT 2 — Tableau de bord (Dashboard)
## BudgetFlow — PHP 8.3 natif + PostgreSQL + Bootstrap 5

---

## Contexte — Ce qui existe déjà (Fonction 1 terminée)

La Fonction 1 est complète et fonctionnelle. Les fichiers suivants existent :

```
core/Database.php      — singleton PDO PostgreSQL (getInstance())
core/Router.php        — routing GET/POST
core/Session.php       — sessions + flash messages (setFlash/getFlash/set/get)
core/Auth.php          — requireLogin() / requireRole($role)
core/CSRF.php          — generateToken() / validateToken() / getTokenField()
config/config.php      — configuration BDD, mail, app
app/models/User.php    — findByEmail / create / findById / update
app/controllers/AuthController.php
app/views/layouts/guest.php
app/views/auth/login.php
app/views/auth/register.php
public/index.php       — router avec routes /login /register /logout
```

**Routes déjà définies dans public/index.php :**
```php
$router->get('/login',    [AuthController::class, 'showLogin']);
$router->post('/login',   [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register',[AuthController::class, 'register']);
$router->get('/logout',   [AuthController::class, 'logout']);
// /dashboard et /admin sont des placeholders à remplacer
```

---

## Objectif — Fonction 2 : Tableau de bord

Remplacer le placeholder `/dashboard` par un vrai tableau de bord complet
affichant les données financières de l'utilisateur connecté.

---

## Périmètre exact — ce que tu codes

```
1. Layout principal authentifié
   ├── app/views/layouts/app.php       — sidebar + topbar + contenu
   └── app/views/partials/sidebar.php  — navigation sidebar

2. Modèles (nouveaux)
   ├── app/models/Budget.php           — findByUser, getTotalSpent
   ├── app/models/Transaction.php      — findByUser, sumByType, byCategory
   └── app/models/Category.php         — findAll, findByUser

3. Contrôleur
   └── app/controllers/DashboardController.php

4. Vue
   └── app/views/dashboard/index.php

5. Assets
   └── assets/js/charts.js            — Chart.js configuration dark theme
```

---

## Comportement attendu — spécifications précises

### Layout `app/views/layouts/app.php`

```
Structure HTML :
├── <head> : Bootstrap 5 CDN, Google Fonts (DM Sans + Inter + JetBrains Mono),
│            Chart.js CDN, custom CSS inline ou assets/css/app.css
├── Sidebar fixe gauche (256px)
│   ├── Logo : 💰 BudgetFlow en violet #6C63FF
│   ├── Navigation (liens actifs selon URL courante) :
│   │   ├── Dashboard      → /dashboard
│   │   ├── Transactions   → /transactions
│   │   ├── Budgets        → /budgets
│   │   ├── Catégories     → /categories
│   │   └── Profil         → /profile
│   └── Bas sidebar : avatar initiales + nom + email + lien déconnexion
├── Topbar (hauteur 64px)
│   ├── Titre de la page courante (@yield('page_title'))
│   └── Badge rôle de l'utilisateur (bf-badge-user)
└── Contenu principal : @yield('content') avec padding 32px
```

Détecter la page active :
```php
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$isActive = fn($path) => $currentPath === $path ? 'active' : '';
```

### Données du dashboard

Le `DashboardController` doit calculer pour l'utilisateur connecté :

```php
// Période : mois en cours (du 1er au dernier jour du mois)
$startOfMonth = date('Y-m-01');
$endOfMonth   = date('Y-m-t');

$data = [
  // Cartes statistiques (mois en cours)
  'total_income'   => somme transactions type=income du mois,
  'total_expense'  => somme transactions type=expense du mois,
  'balance'        => total_income - total_expense,

  // Budgets actifs de l'utilisateur (owner OU member)
  'budgets'        => liste avec pour chaque budget :
                      [id, name, type, amount_limit, spent, percent, status]
                      status = 'ok' | 'warning' (≥80%) | 'danger' (≥100%)

  // 8 dernières transactions toutes catégories confondues
  'recent_transactions' => [id, type, amount, description, date,
                             category_name, category_color, budget_name]

  // Répartition dépenses par catégorie (mois en cours)
  'category_breakdown' => [['name'=>, 'amount'=>, 'color'=>], ...]

  // Évolution sur 6 derniers mois
  'monthly_evolution' => [
    ['month'=>'Jan', 'income'=>, 'expense'=>],
    ...
  ]
]
```

### Vue `app/views/dashboard/index.php`

**Section 1 — 3 cartes statistiques (row)**
```
Carte Revenus   : montant en vert #22D3A5, icône ↑, label "Ce mois"
Carte Dépenses  : montant en rouge #FF6B6B, icône ↓, label "Ce mois"
Carte Solde     : montant coloré selon positif/négatif, icône =
Chaque carte : bf-card, montant en JetBrains Mono 28px bold
```

**Section 2 — Budgets actifs (barres de progression)**
```
Pour chaque budget :
  Nom du budget + badge type (personal/shared)
  Barre Bootstrap progress customisée :
    - Verte  si percent < 80%
    - Orange si percent >= 80%
    - Rouge  si percent >= 100%
  Texte : "X,XX € / Y,YY € (Z%)"
  Si dépassé : badge rouge "⚠ Dépassé"
```

**Section 3 — 2 colonnes**
```
Colonne gauche (2/3) : tableau transactions récentes
  Colonnes : Date | Catégorie (dot coloré + nom) | Budget | Montant coloré
  Montant : vert si income, rouge si expense, police JetBrains Mono

Colonne droite (1/3) : graphique camembert
  Chart.js doughnut — répartition dépenses par catégorie
  Thème dark : fond transparent, légende couleurs design system
  Si aucune dépense : message "Aucune dépense ce mois"
```

**Section 4 — Graphique évolution (pleine largeur)**
```
Chart.js line chart — 6 derniers mois
Deux lignes : Revenus (#22D3A5) et Dépenses (#FF6B6B)
Fond dark, grille #2A2F45, labels #8B90A7
```

### `assets/js/charts.js` — Configuration Chart.js dark

```javascript
// Defaults globaux dark theme à appliquer :
Chart.defaults.color = '#8B90A7';
Chart.defaults.borderColor = '#2A2F45';
Chart.defaults.font.family = 'Inter';

// Exporter deux fonctions :
// initDoughnutChart(canvasId, labels, data, colors)
// initLineChart(canvasId, months, incomeData, expenseData)
```

Les données PHP sont passées à JS via :
```php
<script>
const chartData = <?= json_encode($data['category_breakdown']) ?>;
const evolutionData = <?= json_encode($data['monthly_evolution']) ?>;
</script>
```

---

## Requêtes SQL à utiliser — PDO préparées obligatoirement

```sql
-- Total revenus/dépenses du mois
SELECT COALESCE(SUM(t.amount), 0) as total
FROM transactions t
JOIN budget_members bm ON bm.budget_id = t.budget_id
WHERE bm.user_id = :user_id
  AND t.type = :type
  AND t.date BETWEEN :start AND :end;

-- Budgets de l'utilisateur (owner + member)
SELECT DISTINCT b.*, COALESCE(SUM(t.amount) FILTER (WHERE t.type='expense'), 0) as spent
FROM budgets b
LEFT JOIN budget_members bm ON bm.budget_id = b.id
LEFT JOIN transactions t ON t.budget_id = b.id
WHERE b.owner_id = :user_id OR bm.user_id = :user_id
GROUP BY b.id;

-- 8 dernières transactions
SELECT t.*, c.name as category_name, c.color as category_color, b.name as budget_name
FROM transactions t
LEFT JOIN categories c ON c.id = t.category_id
JOIN budgets b ON b.id = t.budget_id
WHERE t.user_id = :user_id
ORDER BY t.date DESC, t.created_at DESC
LIMIT 8;

-- Répartition par catégorie (mois en cours)
SELECT c.name, c.color, COALESCE(SUM(t.amount), 0) as amount
FROM transactions t
JOIN categories c ON c.id = t.category_id
WHERE t.user_id = :user_id
  AND t.type = 'expense'
  AND t.date BETWEEN :start AND :end
GROUP BY c.id, c.name, c.color
ORDER BY amount DESC;

-- Évolution 6 mois
SELECT TO_CHAR(date_trunc('month', t.date), 'Mon YYYY') as month,
       COALESCE(SUM(CASE WHEN t.type='income'  THEN t.amount ELSE 0 END), 0) as income,
       COALESCE(SUM(CASE WHEN t.type='expense' THEN t.amount ELSE 0 END), 0) as expense
FROM transactions t
WHERE t.user_id = :user_id
  AND t.date >= NOW() - INTERVAL '6 months'
GROUP BY date_trunc('month', t.date)
ORDER BY date_trunc('month', t.date);
```

---

## Routes à ajouter dans public/index.php

```php
$router->get('/dashboard', [DashboardController::class, 'index']);
```

---

## Design — appliquer design.md EXACTEMENT

- Layout app.php : sidebar `#1A1D27` 256px fixe, topbar 64px, fond `#0F1117`
- Toutes les cartes : classe `bf-card`
- Montants : `font-family: 'JetBrains Mono'`
- Barres de progression : Bootstrap `.progress` avec couleur dynamique
- Icônes sidebar : Bootstrap Icons CDN ou SVG inline

---

## Ordre de génération des fichiers

```
1. assets/css/app.css              (variables CSS + classes bf-*)
2. app/views/partials/sidebar.php
3. app/views/layouts/app.php
4. app/models/Category.php
5. app/models/Budget.php
6. app/models/Transaction.php
7. app/controllers/DashboardController.php
8. app/views/dashboard/index.php
9. assets/js/charts.js
10. public/index.php               (version mise à jour avec nouvelle route)
```

---

## Règles absolues

1. Chaque fichier est **complet** — aucun raccourci
2. **Ne pas réécrire** les fichiers de la Fonction 1 sauf `public/index.php` pour ajouter la route
3. `Auth::requireRole('user')` en première ligne de `DashboardController::index()`
4. Toutes les sorties HTML : `htmlspecialchars()`
5. Toutes les requêtes : PDO préparées uniquement
6. `number_format($amount, 2, ',', ' ')` pour afficher les montants
7. Commentaires en français
