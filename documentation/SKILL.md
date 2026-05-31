# BudgetFlow — Skill OpenCode
## Application Web de Gestion Collaborative de Budget Personnel
### ITEAM University — Projet Semestriel 1 ING

---

## Quand utiliser ce skill

Utilise ce skill pour **toute tâche** liée au projet BudgetFlow :
- Écrire ou modifier du code PHP natif (controllers, models, views, core)
- Créer ou modifier des vues HTML/Bootstrap
- Travailler sur la base de données PostgreSQL
- Configurer Docker / Ollama
- Implémenter des fonctionnalités du cahier des charges
- Corriger des bugs
- Ajouter des routes dans `public/index.php`

---

## Stack technique — NON NÉGOCIABLE

| Couche | Technologie |
|--------|-------------|
| Frontend | HTML5 + Bootstrap 5.3 (CDN) + Bootstrap Icons 1.11 + JavaScript vanilla + Chart.js 4 |
| Backend | PHP 8.3 natif — **aucun framework** (pas Laravel, pas Symfony) |
| Base de données | PostgreSQL 16 |
| Emails | PHPMailer + Gmail SMTP |
| IA | Ollama local (llama3.2:1b) via cURL |
| Docker | PHP-FPM Alpine + Nginx Alpine + PostgreSQL 16 Alpine + Ollama |
| Architecture | MVC maison — pas d'autoloader, `require_once` manuel |

---

## Structure du projet

```
budgetflow/
├── public/
│   ├── index.php              ← Point d'entrée UNIQUE — router + require_once de tout
│   ├── style.css              ← Classes bf-* custom
│   ├── script.js              ← JS global (dark mode, sidebar)
│   ├── img/                   ← Images, favicon
│   └── animations/            ← GIFs (ai-chat.gif, pdf.gif)
│
├── app/
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── TransactionController.php
│   │   ├── CategoryController.php
│   │   ├── BudgetController.php
│   │   ├── ProfileController.php
│   │   ├── AdminController.php
│   │   ├── AiController.php          ← POST /api/chat → Ollama
│   │   └── RapportController.php     ← GET /rapport, POST /rapport/generer
│   │
│   ├── models/
│   │   ├── User.php
│   │   ├── Budget.php
│   │   ├── Transaction.php
│   │   └── Category.php
│   │
│   └── views/
│       ├── layouts/
│       │   ├── app.php        ← Layout sidebar + topbar (utilisateurs)
│       │   ├── admin.php      ← Layout sidebar admin
│       │   └── guest.php      ← Layout pages auth (login/register)
│       ├── partials/
│       │   ├── sidebar.php    ← Navigation principale
│       │   └── ai-assistant.php  ← Bouton flottant IA + modal chat
│       ├── auth/
│       ├── dashboard/
│       ├── transactions/
│       ├── budgets/
│       ├── categories/
│       ├── profile/
│       ├── rapport/
│       │   ├── index.php      ← Formulaire de génération
│       │   └── print.php      ← Page d'impression HTML → PDF
│       ├── admin/
│       └── emails/
│
├── core/
│   ├── Database.php           ← Singleton PDO PostgreSQL
│   ├── Router.php             ← Routing GET/POST
│   ├── Session.php            ← Sessions + flash messages (instance)
│   ├── Auth.php               ← Vérification rôles (statique)
│   ├── CSRF.php               ← Protection CSRF (statique)
│   └── Mailer.php             ← PHPMailer wrapper
│
├── config/
│   └── config.php             ← Configuration centrale (DB, mail, ollama)
│
├── database/
│   └── schema.sql             ← Schéma PostgreSQL + données initiales
│
└── docker/
    ├── php.Dockerfile
    ├── nginx.conf
    └── ollama-init.sh
```

---

## Base de données — 5 tables

```sql
users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,       -- bcrypt hash
    role VARCHAR(10) DEFAULT 'user' CHECK (role IN ('user','admin')),
    is_active BOOLEAN DEFAULT false,
    phone VARCHAR(20),
    preferences JSONB,
    last_login_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW()
)

categories (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,  -- NULL = catégorie système
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
    amount_limit DECIMAL(12,2),           -- NULL = sans plafond
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

## Routes — table complète

```
AUTH
GET  /                         → home.php (page publique)
GET  /login                    → AuthController::showLogin
POST /login                    → AuthController::login
GET  /register                 → AuthController::showRegister
POST /register                 → AuthController::register
POST /logout                   → AuthController::logout

UTILISATEUR
GET  /dashboard                → DashboardController::index           [user]
GET  /transactions             → TransactionController::index         [user]
GET  /transactions/create      → TransactionController::showCreate    [user]
POST /transactions/create      → TransactionController::create        [user]
GET  /transactions/edit        → TransactionController::showEdit      [user]
POST /transactions/edit        → TransactionController::edit          [user]
POST /transactions/delete      → TransactionController::delete        [user]
GET  /categories               → CategoryController::index            [user]
POST /categories/create        → CategoryController::create           [user]
POST /categories/edit          → CategoryController::edit             [user]
POST /categories/delete        → CategoryController::delete           [user]
GET  /budgets                  → BudgetController::index              [user]
GET  /budgets/shared           → BudgetController::sharedIndex        [user]
GET  /budgets/create           → BudgetController::showCreate         [user]
POST /budgets/create           → BudgetController::create             [user]
GET  /budgets/show             → BudgetController::show               [user]
GET  /budgets/edit             → BudgetController::showEdit           [user]
POST /budgets/edit             → BudgetController::edit               [user]
POST /budgets/delete           → BudgetController::delete             [user]
POST /budgets/invite           → BudgetController::invite             [user]
POST /budgets/remove-member    → BudgetController::removeMember       [user]
GET  /profile                  → ProfileController::index             [user]
POST /profile/update-info      → ProfileController::updateInfo        [user]
POST /profile/update-password  → ProfileController::updatePassword    [user]
POST /profile/request-deletion → ProfileController::requestDeletion   [user]
GET  /rapport                  → RapportController::index             [user]
POST /rapport/generer          → RapportController::generer           [user]
POST /api/chat                 → AiController::chat                   [user]

ADMIN
GET  /admin                         → AdminController::index          [admin]
GET  /admin/users                   → AdminController::users          [admin]
GET  /admin/users/export            → AdminController::exportUsers    [admin]
POST /admin/users/validate          → AdminController::validateUser   [admin]
POST /admin/users/role              → AdminController::changeRole     [admin]
POST /admin/users/delete            → AdminController::deleteUser     [admin]
POST /admin/users/reset-password    → AdminController::resetPassword  [admin]
GET  /admin/budgets                 → AdminController::budgets        [admin]
GET  /admin/profile                 → AdminController::profile        [admin]
GET  /admin/send-email              → AdminController::showSendEmail  [admin]
POST /admin/send-email              → AdminController::sendBulkEmail  [admin]
POST /admin/test-email              → AdminController::testEmail      [admin]
```

---

## Rôles utilisateurs

### Utilisateur (`role = 'user'`)
- Accède uniquement à ses propres données
- Interdit sur toutes les routes `/admin`
- Redirigé vers `/dashboard` après login
- Peut utiliser l'assistant IA (`/api/chat`)
- Peut générer des rapports PDF (`/rapport`)

### Administrateur (`role = 'admin'`)
- Accède uniquement à `/admin`
- Ne crée pas de budgets ni transactions personnels
- Valide comptes, gère rôles, supervise budgets, envoie des emails groupés
- Redirigé vers `/admin` après login
- L'assistant IA est masqué pour les admins

---

## Core classes — API

### Database.php
```php
Database::getInstance(): PDO
// Singleton — connexion PostgreSQL
// PDO::ATTR_ERRMODE        => ERRMODE_EXCEPTION
// PDO::ATTR_DEFAULT_FETCH_MODE => FETCH_ASSOC
```

### Session.php — INSTANCE (pas statique)
```php
$session = new Session();
$session->setFlash(string $type, string $message): void
$session->getFlash(string $type): ?string   // consomme et supprime
$session->set(string $key, mixed $value): void
$session->get(string $key, mixed $default = null): mixed
$session->destroy(): void
```
> ⚠️ Session est une **instance**, pas une classe statique.  
> Utiliser `(new Session())->setFlash(...)` depuis les controllers.

### Auth.php — STATIQUE
```php
Auth::isLoggedIn(): bool
Auth::getUser(): ?array                    // ['id','name','email','role']
Auth::requireLogin(): void                 // redirect /login si non connecté
Auth::requireRole(string $role): void      // redirect si mauvais rôle
```

### CSRF.php — STATIQUE
```php
CSRF::generateToken(): string
CSRF::validateToken(string $token): bool
CSRF::getTokenField(): string              // retourne <input type="hidden" ...>
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

## Design System — variables CSS

```css
/* Fond de page */
--bg-page:        #0F1117;
--bg-card:        #1A1D27;
--bg-elevated:    #222636;
--bg-hover:       #2A2F45;

/* Accent principal (violet — CTA, boutons, focus) */
--accent:         #6C63FF;
--accent-hover:   #7B74FF;

/* Couleurs fonctionnelles */
--color-income:   #22D3A5;   /* vert — revenus, succès */
--color-expense:  #FF6B6B;   /* rouge — dépenses */
--color-warning:  #FFB547;   /* orange — alerte */
--color-danger:   #FF4D4D;   /* rouge vif — dépassement */

/* Texte */
--text-primary:   #F0F2F8;
--text-secondary: #8B90A7;
--text-muted:     #555B75;

/* Bordures */
--border:         #2A2F45;
```

## Design System — composants CSS

```css
.bf-card          { background:#1A1D27; border:1px solid #2A2F45; border-radius:16px; padding:24px; }
.bf-input         { background:#222636!important; border:1px solid #2A2F45!important; border-radius:10px!important; color:#F0F2F8!important; padding:12px 16px; }
.bf-input:focus   { border-color:#6C63FF!important; box-shadow:0 0 0 3px rgba(108,99,255,.15)!important; }
.bf-btn-primary   { background:#6C63FF; color:#fff; border:none; border-radius:10px; padding:12px 28px; font-weight:600; }
.bf-btn-primary:hover { background:#7B74FF; transform:translateY(-1px); }
.bf-sidebar-link  { display:flex; align-items:center; gap:12px; padding:10px 16px; border-radius:10px; color:#8B90A7; }
.bf-sidebar-link:hover  { background:#2A2F45; color:#F0F2F8; }
.bf-sidebar-link.active { background:rgba(108,99,255,.15); color:#6C63FF; }
.bf-badge-user    { background:rgba(108,99,255,.15); color:#6C63FF; }
.bf-badge-admin   { background:rgba(255,181,71,.15); color:#FFB547; }
.bf-alert-success { background:rgba(34,211,165,.1); border-color:rgba(34,211,165,.3); color:#22D3A5; }
.bf-alert-danger  { background:rgba(255,107,107,.1); border-color:rgba(255,107,107,.3); color:#FF6B6B; }
.bf-alert-warning { background:rgba(255,181,71,.1); border-color:rgba(255,181,71,.3); color:#FFB547; }
```

## Typographie

| Usage | Police | Poids |
|-------|--------|-------|
| Titres de page | DM Sans | 600–700 |
| Corps de texte | Plus Jakarta Sans | 400–500 |
| Montants | JetBrains Mono | 500–600 |

---

## Sécurité — règles absolues

```
✅ password_hash(PASSWORD_BCRYPT) pour hasher les mots de passe
✅ password_verify() pour vérifier
✅ session_regenerate_id(true) après chaque login
✅ CSRF::validateToken($_POST['csrf_token'] ?? '') sur CHAQUE POST
✅ CSRF::getTokenField() dans CHAQUE formulaire HTML
✅ Auth::requireRole() en PREMIÈRE ligne de chaque méthode controller protégée
✅ PDO requêtes préparées UNIQUEMENT — jamais de concaténation SQL
✅ htmlspecialchars($var, ENT_QUOTES, 'UTF-8') sur TOUTES les sorties HTML
✅ Vérifier l'ownership avant edit/delete (owner_id = session user_id)
✅ Vérifier appartenance budget avant accès (owner OU member)
```

---

## Règles de code

```
1. Commentaires en français
2. Fichiers toujours complets — jamais de "// ...reste du code"
3. number_format($amount, 2, ',', ' ') . ' DT' pour afficher les montants
4. date('d/m/Y', strtotime($date)) pour afficher les dates
5. Emails : try/catch obligatoire — ne jamais laisser planter une requête HTTP
6. CSS emails : inline uniquement (Bootstrap non supporté dans les emails)
7. Flash messages : (new Session())->setFlash() / (new Session())->getFlash()
8. Pas de require_once pour les vues dans les controllers — utiliser require
```

---

## Fonctionnalités — état d'avancement

| # | Fonctionnalité | Statut |
|---|----------------|--------|
| 1 | Auth + Login + Register + Rôles | ✅ Terminé |
| 2 | Tableau de bord + Charts | ✅ Terminé |
| 3 | Transactions + Catégories | ✅ Terminé |
| 4 | Budgets personnels + Collaboration | ✅ Terminé |
| 5 | Panneau Admin complet | ✅ Terminé |
| 6 | Profil + Emails + Alertes budgets | ✅ Terminé |
| 7 | Assistant IA (Ollama llama3.2:1b) | ✅ Terminé |
| 8 | Rapport PDF (génération HTML print) | ✅ Terminé |

---

## Auteurs

- **Mourad Ben Abdallah** — Auth, Dashboard, Catégories, Emails, IA, Rapport PDF
- **Aziz Ben Hmida** — Transactions, Budgets, Admin, Profil

ITEAM University — 2024/2025
