# BudgetFlow — Skill OpenCode
## Application Web de Gestion Collaborative de Budget Personnel
### ITEAM University — Projet Semestriel 1 ING

---

## Quand utiliser ce skill

Utilise ce skill pour TOUTE tâche liée au projet BudgetFlow :
- Écrire ou modifier du code PHP natif (controllers, models, views, core)
- Créer ou modifier des vues HTML/Bootstrap
- Travailler sur la base de données PostgreSQL
- Configurer Docker
- Implémenter des fonctionnalités du cahier des charges
- Corriger des bugs
- Ajouter des routes dans public/index.php

---

## Stack technique — NON NÉGOCIABLE

```
Frontend  : HTML5 + Bootstrap 5.3.2 (CDN) + JavaScript vanilla + Chart.js
Backend   : PHP 8.3 natif — AUCUN framework (pas Laravel, pas Symfony)
Base de données : PostgreSQL 16
Mails     : PHPMailer + Resend SMTP
Docker    : PHP-FPM + Nginx Alpine + PostgreSQL 16 Alpine
Architecture : MVC maison
```

---

## Structure du projet

```
budgetflow/
├── public/
│   └── index.php              ← Point d'entrée UNIQUE — router
├── app/
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── TransactionController.php
│   │   ├── CategoryController.php
│   │   ├── BudgetController.php
│   │   ├── ProfileController.php
│   │   └── AdminController.php
│   ├── models/
│   │   ├── User.php
│   │   ├── Budget.php
│   │   ├── Transaction.php
│   │   └── Category.php
│   └── views/
│       ├── layouts/
│       │   ├── app.php        ← Layout sidebar + topbar (utilisateur)
│       │   ├── admin.php      ← Layout sidebar admin
│       │   └── guest.php      ← Layout pages auth (login/register)
│       ├── partials/
│       │   └── sidebar.php
│       ├── auth/
│       │   ├── login.php
│       │   └── register.php
│       ├── dashboard/
│       │   └── index.php
│       ├── transactions/
│       │   ├── index.php
│       │   └── form.php
│       ├── budgets/
│       │   ├── index.php
│       │   ├── form.php
│       │   └── show.php
│       ├── categories/
│       │   └── index.php
│       ├── profile/
│       │   └── index.php
│       ├── admin/
│       │   ├── index.php
│       │   ├── users.php
│       │   └── budgets.php
│       └── emails/
│           ├── account_validated.php
│           ├── budget_invitation.php
│           ├── budget_alert.php
│           ├── deletion_request_admin.php
│           └── deletion_confirmed.php
├── core/
│   ├── Database.php           ← Singleton PDO PostgreSQL
│   ├── Router.php             ← Routing GET/POST
│   ├── Session.php            ← Sessions + flash messages
│   ├── Auth.php               ← Vérification rôles
│   ├── CSRF.php               ← Protection CSRF
│   └── Mailer.php             ← PHPMailer wrapper
├── config/
│   └── config.php             ← Configuration centrale
├── assets/
│   ├── css/app.css            ← Classes bf-* custom
│   └── js/charts.js           ← Chart.js dark theme config
├── database/
│   └── schema.sql             ← Schéma PostgreSQL complet
└── docker/
    ├── php.Dockerfile
    └── nginx.conf
```

---

## Base de données — 5 tables exactes

```sql
users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,       -- bcrypt UNIQUEMENT
    role VARCHAR(10) DEFAULT 'user' CHECK (role IN ('user','admin')),
    is_active BOOLEAN DEFAULT false,      -- activé par admin
    created_at TIMESTAMP DEFAULT NOW()
)

categories (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,  -- NULL = défaut système
    name VARCHAR(80) NOT NULL,
    color VARCHAR(7) DEFAULT '#6C63FF',
    is_default BOOLEAN DEFAULT false
)

budgets (
    id SERIAL PRIMARY KEY,
    owner_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(10) CHECK (type IN ('personal','shared')),
    period VARCHAR(10) CHECK (period IN ('weekly','monthly','custom')),
    amount_limit DECIMAL(12,2),           -- nullable = sans limite
    start_date DATE DEFAULT CURRENT_DATE,
    created_at TIMESTAMP DEFAULT NOW()
)

transactions (
    id SERIAL PRIMARY KEY,
    budget_id INT NOT NULL REFERENCES budgets(id) ON DELETE CASCADE,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    category_id INT REFERENCES categories(id) ON DELETE SET NULL,
    type VARCHAR(10) CHECK (type IN ('income','expense')),
    amount DECIMAL(12,2) CHECK (amount > 0),
    description VARCHAR(255),
    date DATE DEFAULT CURRENT_DATE,
    created_at TIMESTAMP DEFAULT NOW()
)

budget_members (
    id SERIAL PRIMARY KEY,
    budget_id INT NOT NULL REFERENCES budgets(id) ON DELETE CASCADE,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(budget_id, user_id)
)
```

---

## Routing — convention complète

```php
// AUTH
GET  /login              → AuthController::showLogin
POST /login              → AuthController::login
GET  /register           → AuthController::showRegister
POST /register           → AuthController::register
GET  /logout             → AuthController::logout

// USER
GET  /dashboard          → DashboardController::index         [role: user]
GET  /transactions       → TransactionController::index       [role: user]
GET  /transactions/create  → TransactionController::showCreate
POST /transactions/create  → TransactionController::create
GET  /transactions/edit    → TransactionController::showEdit
POST /transactions/edit    → TransactionController::edit
POST /transactions/delete  → TransactionController::delete
GET  /categories           → CategoryController::index        [role: user]
POST /categories/create    → CategoryController::create
POST /categories/edit      → CategoryController::edit
POST /categories/delete    → CategoryController::delete
GET  /budgets              → BudgetController::index          [role: user]
GET  /budgets/create       → BudgetController::showCreate
POST /budgets/create       → BudgetController::create
GET  /budgets/show         → BudgetController::show
GET  /budgets/edit         → BudgetController::showEdit
POST /budgets/edit         → BudgetController::edit
POST /budgets/delete       → BudgetController::delete
POST /budgets/invite       → BudgetController::invite
POST /budgets/remove-member → BudgetController::removeMember
GET  /profile              → ProfileController::index         [role: user]
POST /profile/update-info  → ProfileController::updateInfo
POST /profile/update-password → ProfileController::updatePassword
POST /profile/request-deletion → ProfileController::requestDeletion

// ADMIN
GET  /admin                    → AdminController::index       [role: admin]
GET  /admin/users              → AdminController::users
POST /admin/users/validate     → AdminController::validateUser
POST /admin/users/role         → AdminController::changeRole
POST /admin/users/delete       → AdminController::deleteUser
GET  /admin/budgets            → AdminController::budgets
```

---

## Rôles utilisateurs

### Utilisateur (`role = 'user'`)
- Accède uniquement à ses propres données
- Interdit sur toutes les routes `/admin`
- Redirigé vers `/dashboard` après login

### Administrateur (`role = 'admin'`)
- Accède uniquement à `/admin`
- NE crée PAS de budgets ni transactions personnels
- Valide comptes, gère rôles, supervise budgets partagés
- Redirigé vers `/admin` après login

---

## Core classes — API exacte

### Database.php
```php
Database::getInstance(): PDO
// Singleton — connexion PostgreSQL DSN : pgsql:host=db;port=5432;dbname=budgetflow
// PDO::ATTR_ERRMODE => ERRMODE_EXCEPTION
// PDO::ATTR_DEFAULT_FETCH_MODE => FETCH_ASSOC
```

### Session.php
```php
Session::setFlash(string $type, string $message): void
Session::getFlash(string $type): ?string   // consomme et supprime
Session::set(string $key, mixed $value): void
Session::get(string $key, mixed $default = null): mixed
Session::destroy(): void
```

### Auth.php
```php
Auth::isLoggedIn(): bool
Auth::getUser(): ?array                    // ['id','name','email','role']
Auth::requireLogin(): void                 // redirect /login si non connecté
Auth::requireRole(string $role): void      // redirect si mauvais rôle
```

### CSRF.php
```php
CSRF::generateToken(): string
CSRF::validateToken(string $token): bool
CSRF::getTokenField(): string              // retourne <input hidden ...>
```

### Mailer.php
```php
Mailer::send(string $to, string $toName, string $subject, string $template, array $data): bool
Mailer::sendAccountValidated(array $user): bool
Mailer::sendBudgetInvitation(array $invitee, array $inviter, array $budget): bool
Mailer::sendBudgetAlert(array $user, array $budget, float $percent, float $spent, float $limit): bool
Mailer::sendDeletionRequestToAdmins(array $requestingUser): bool
Mailer::sendDeletionConfirmed(array $user): bool
```

---

## Design System — couleurs exactes

```css
--bg-page:       #0F1117;   /* fond de page */
--bg-card:       #1A1D27;   /* cartes, sidebar */
--bg-elevated:   #222636;   /* inputs, dropdowns */
--bg-hover:      #2A2F45;   /* hover */
--accent:        #6C63FF;   /* violet — CTA */
--accent-hover:  #7B74FF;
--color-income:  #22D3A5;   /* vert — revenus */
--color-expense: #FF6B6B;   /* rouge — dépenses */
--color-warning: #FFB547;   /* orange — alerte */
--color-danger:  #FF4D4D;   /* rouge — dépassement */
--text-primary:  #F0F2F8;
--text-secondary:#8B90A7;
--text-muted:    #555B75;
--border:        #2A2F45;
```

## Design System — composants CSS obligatoires

```css
/* Carte */
.bf-card { background:#1A1D27; border:1px solid #2A2F45; border-radius:16px; padding:24px; }

/* Input */
.bf-input { background:#222636!important; border:1px solid #2A2F45!important;
            border-radius:10px!important; color:#F0F2F8!important; padding:12px 16px; }
.bf-input:focus { border-color:#6C63FF!important; box-shadow:0 0 0 3px rgba(108,99,255,0.15)!important; }

/* Bouton primaire */
.bf-btn-primary { background:#6C63FF; color:white; border:none; border-radius:10px;
                  padding:12px 28px; font-weight:600; transition:all 0.2s; }
.bf-btn-primary:hover { background:#7B74FF; transform:translateY(-1px); }

/* Nav sidebar */
.bf-nav-item { display:flex; align-items:center; gap:12px; padding:10px 16px;
               border-radius:10px; color:#8B90A7; text-decoration:none; font-size:14px; }
.bf-nav-item:hover { background:#2A2F45; color:#F0F2F8; }
.bf-nav-item.active { background:rgba(108,99,255,0.15); color:#6C63FF; }

/* Badges */
.bf-badge { font-size:11px; font-weight:600; padding:3px 10px; border-radius:999px; }
.bf-badge-user  { background:rgba(108,99,255,0.15); color:#6C63FF; }
.bf-badge-admin { background:rgba(255,181,71,0.15);  color:#FFB547; }

/* Alerts */
.bf-alert { border-radius:10px; padding:12px 16px; font-size:14px; border:1px solid; }
.bf-alert-success { background:rgba(34,211,165,0.1); border-color:rgba(34,211,165,0.3); color:#22D3A5; }
.bf-alert-danger  { background:rgba(255,107,107,0.1); border-color:rgba(255,107,107,0.3); color:#FF6B6B; }
.bf-alert-warning { background:rgba(255,181,71,0.1);  border-color:rgba(255,181,71,0.3);  color:#FFB547; }
.bf-alert-info    { background:rgba(96,165,250,0.1);  border-color:rgba(96,165,250,0.3);  color:#60A5FA; }
```

## Typographie

```
Titres    : DM Sans (Google Fonts), weight 600-700
Corps     : Inter (Google Fonts), weight 400-500
Montants  : JetBrains Mono (Google Fonts), weight 600
```

Import CDN à mettre dans tous les layouts :
```html
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Inter:wght@400;500&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
```

---

## Sécurité — règles absolues

```
✅ password_hash(PASSWORD_BCRYPT) pour hasher
✅ password_verify() pour vérifier
✅ session_regenerate_id(true) après chaque login
✅ CSRF::validateToken() sur CHAQUE POST
✅ CSRF::getTokenField() dans CHAQUE formulaire
✅ Auth::requireLogin() ou Auth::requireRole() en PREMIÈRE ligne de chaque controller
✅ PDO requêtes préparées UNIQUEMENT — jamais de concaténation SQL
✅ htmlspecialchars($var, ENT_QUOTES, 'UTF-8') sur TOUTES les sorties HTML
✅ Vérifier ownership sur edit/delete (user_id = session user_id)
✅ Vérifier appartenance budget avant accès (owner OU member)
```

---

## Règles de code

```
1. Commentaires en français
2. Fichiers toujours complets — jamais de "// ...reste du code"
3. number_format($amount, 2, ',', ' ') pour afficher les montants
4. date('d/m/Y', strtotime($date)) pour afficher les dates
5. Emails : try/catch obligatoire — jamais laisser planter une requête HTTP
6. CSS emails : inline uniquement (pas Bootstrap dans les emails)
7. Pagination : 20 éléments par page, paramètre GET ?page=N
8. Flash messages via Session::setFlash() / Session::getFlash()
```

---

## Configuration Docker

```yaml
# docker-compose.yml — services
app:      PHP-FPM 8.3 Alpine — port interne 9000
nginx:    Nginx Alpine — port 8000:80
db:       PostgreSQL 16 Alpine — port 5432
          DB_HOST=db (nom du service Docker, pas localhost)
          DB_NAME=budgetflow
          DB_USER=budgetflow
          DB_PASSWORD=secret
```

## Configuration Resend (emails)

```php
// config/config.php
'mail' => [
    'host'       => 'smtp.resend.com',
    'port'       => 465,
    'username'   => 'resend',
    'password'   => 'your_resend_api_key',
    'encryption' => 'ssl',
    'from_email' => 'onboarding@resend.dev',
    'from_name'  => 'BudgetFlow',
]
```

---

## Fonctionnalités — état d'avancement

| # | Fonction | Statut |
|---|----------|--------|
| 1 | Auth + Login + Register + Rôles | ✅ Terminé |
| 2 | Tableau de bord + Charts | 🔄 En cours |
| 3 | Transactions + Catégories | ⏳ À faire |
| 4 | Budgets + Collaboration | ⏳ À faire |
| 5 | Panneau Admin | ⏳ À faire |
| 6 | Profil + Emails + Alertes | ⏳ À faire |

---

## Auteurs

- **Mourad Ben Abdallah** — Auth, Dashboard, Catégories, Emails
- **Aziz Ben Hmida** — Transactions, Budgets, Admin, Profil

ITEAM University — 2024/2025
