# Cahier des Charges — BudgetFlow
## Application Web de Gestion Collaborative de Budget Personnel
### ITEAM University — Projet Semestriel 1 ING

---

## 1. Contexte

Application web permettant à plusieurs utilisateurs de suivre leurs revenus,
dépenses, objectifs financiers et budgets partagés, à travers une interface
simple, sécurisée et accessible.

---

## 2. Stack technique imposé

- **Frontend** : HTML5 + Bootstrap 5 + JavaScript vanilla
- **Backend** : PHP 8.3 natif (PDO, sessions, sans framework)
- **Base de données** : PostgreSQL 16
- **Mails** : PHPMailer + Resend SMTP
- **Environnement** : Docker (PHP-FPM + Nginx + PostgreSQL)
- **Architecture** : MVC maison (sans Laravel)

---

## 3. Base de données — 5 tables uniquement

```sql
users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(150) UNIQUE,
    password VARCHAR(255),        -- bcrypt hash
    role VARCHAR(10) DEFAULT 'user' CHECK (role IN ('user','admin')),
    is_active BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT NOW()
)

categories (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,  -- NULL = défaut
    name VARCHAR(80),
    color VARCHAR(7) DEFAULT '#6C63FF',
    is_default BOOLEAN DEFAULT false
)

budgets (
    id SERIAL PRIMARY KEY,
    owner_id INT REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(100),
    type VARCHAR(10) CHECK (type IN ('personal','shared')),
    period VARCHAR(10) CHECK (period IN ('weekly','monthly','custom')),
    amount_limit DECIMAL(12,2),
    start_date DATE DEFAULT CURRENT_DATE,
    created_at TIMESTAMP DEFAULT NOW()
)

transactions (
    id SERIAL PRIMARY KEY,
    budget_id INT REFERENCES budgets(id) ON DELETE CASCADE,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    category_id INT REFERENCES categories(id) ON DELETE SET NULL,
    type VARCHAR(10) CHECK (type IN ('income','expense')),
    amount DECIMAL(12,2) CHECK (amount > 0),
    description VARCHAR(255),
    date DATE DEFAULT CURRENT_DATE,
    created_at TIMESTAMP DEFAULT NOW()
)

budget_members (
    id SERIAL PRIMARY KEY,
    budget_id INT REFERENCES budgets(id) ON DELETE CASCADE,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(budget_id, user_id)
)
```

---

## 4. Fonctionnalités — liste complète

### 4.1 Gestion des utilisateurs (FONCTION 1 — en cours)
- Inscription avec : nom, email, mot de passe
- Compte créé avec `is_active = false` par défaut
- L'admin valide le compte → `is_active = true` + mail de confirmation envoyé
- Connexion sécurisée avec session PHP
- Déconnexion
- Modification du profil et mot de passe
- Demande de suppression de compte (admin valide ou refuse)

### 4.2 Gestion des rôles
Deux rôles :

**Utilisateur (`role = 'user'`)**
- Accède uniquement à ses propres données
- Ne peut pas accéder à `/admin`
- Redirigé vers `/dashboard` après login

**Administrateur (`role = 'admin'`)**
- Accède à `/admin`
- Valide/refuse les comptes en attente
- Gère les rôles
- Consulte tous les budgets partagés
- Voit les statistiques globales
- N'est PAS un utilisateur — ne crée pas de budgets personnels
- Redirigé vers `/admin` après login

### 4.3 Gestion des transactions
- Ajouter : type (income/expense), montant, date, description, catégorie, budget
- Modifier et supprimer ses propres transactions
- Afficher la liste filtrée par budget / catégorie / période

### 4.4 Gestion des catégories
- Catégories par défaut (visibles par tous) : Alimentation, Transport,
  Logement, Santé, Loisirs, Études, Autre
- L'utilisateur peut créer ses propres catégories
- Modifier/supprimer ses catégories (pas les défaut)

### 4.5 Gestion des budgets
- Créer un budget individuel ou partagé
- Période : weekly / monthly / custom
- Plafond global optionnel
- Suivre l'évolution en temps réel

### 4.6 Collaboration
- Inviter un membre à un budget partagé → email d'invitation envoyé
- Identifier l'auteur de chaque transaction
- Consulter les dépenses communes

### 4.7 Alertes
- Alerte visuelle quand budget atteint 80%
- Alerte visuelle + email quand budget dépassé
- Indicateur : maîtrisé / proche / dépassé

### 4.8 Tableau de bord
- Total revenus / dépenses / solde
- Pourcentage budget consommé
- Répartition par catégorie (graphique Chart.js)
- Évolution dans le temps (graphique Chart.js)

### 4.9 Emails (PHPMailer +gmail default mail)
- Validation de compte par admin
- Invitation budget partagé
- Alerte dépassement budget
- Demande/confirmation suppression de compte
- Récapitulatif mensuel (cron)

---

## 5. Architecture des fichiers

```
budgetflow/
├── public/
│   └── index.php          ← point d'entrée unique (router)
├── app/
│   ├── controllers/       ← un fichier par contrôleur
│   ├── models/            ← un fichier par modèle (PDO)
│   └── views/
│       ├── layouts/       ← layout principal + guest
│       ├── partials/      ← navbar, sidebar, footer
│       ├── auth/          ← login.php, register.php
│       ├── dashboard/
│       ├── budgets/
│       ├── transactions/
│       ├── categories/
│       └── admin/
├── core/
│   ├── Database.php       ← singleton PDO PostgreSQL
│   ├── Router.php         ← routing GET/POST
│   ├── Session.php        ← gestion sessions
│   ├── Auth.php           ← vérification rôles
│   └── Mailer.php         ← PHPMailer wrapper
├── config/
│   └── config.php         ← BDD, mail, app config
├── database/
│   └── schema.sql
└── docker/
    ├── php.Dockerfile
    └── nginx.conf
```

---

## 6. Routing — convention

```
GET  /                → redirect login si non connecté, dashboard sinon
GET  /login           → page login
POST /login           → traitement login
GET  /register        → page inscription
POST /register        → traitement inscription
GET  /logout          → déconnexion
GET  /dashboard       → tableau de bord (role: user)
GET  /admin           → panneau admin (role: admin)
GET  /budgets         → liste budgets
POST /budgets/create  → créer budget
GET  /transactions    → liste transactions
POST /transactions/create → créer transaction
GET  /categories      → liste catégories
GET  /profile         → profil utilisateur
POST /profile/update  → modifier profil
```

---

## 7. Sécurité obligatoire

- Mots de passe : `password_hash()` bcrypt, vérification `password_verify()`
- Sessions : `session_start()`, régénération ID après login
- CSRF : token dans chaque formulaire POST
- Accès par rôle : vérification `$_SESSION['role']` sur chaque page protégée
- PDO : requêtes préparées uniquement — jamais de concaténation SQL
- XSS : `htmlspecialchars()` sur toutes les sorties

---

## 8. Configuration Docker

- **PHP** : php:8.3-fpm-alpine + extensions pdo, pdo_pgsql
- **Nginx** : alpine, port 8000 → 80
- **PostgreSQL** : postgres:16-alpine, port 5432
- **Volumes** : code monté en volume (hot reload), postgres_data persistant
- **Réseau** : bridge interne `budgetflow`
- **Init BDD** : schema.sql monté dans `/docker-entrypoint-initdb.d/`
