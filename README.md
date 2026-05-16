<div align="center">

<br/>

```
██████╗ ██╗   ██╗██████╗  ██████╗ ███████╗████████╗███████╗██╗      ██████╗ ██╗    ██╗
██╔══██╗██║   ██║██╔══██╗██╔════╝ ██╔════╝╚══██╔══╝██╔════╝██║     ██╔═══██╗██║    ██║
██████╔╝██║   ██║██║  ██║██║  ███╗█████╗     ██║   █████╗  ██║     ██║   ██║██║ █╗ ██║
██╔══██╗██║   ██║██║  ██║██║   ██║██╔══╝     ██║   ██╔══╝  ██║     ██║   ██║██║███╗██║
██████╔╝╚██████╔╝██████╔╝╚██████╔╝███████╗   ██║   ██║     ███████╗╚██████╔╝╚███╔███╔╝
╚═════╝  ╚═════╝ ╚═════╝  ╚═════╝ ╚══════╝   ╚═╝   ╚═╝     ╚══════╝ ╚═════╝  ╚══╝╚══╝
```

### 💰 Application Web de Gestion Collaborative de Budget Personnel

<br/>

[![PHP](https://img.shields.io/badge/PHP-8.3-7A86B8?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?style=for-the-badge&logo=postgresql&logoColor=white)](https://postgresql.org)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://docker.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![Nginx](https://img.shields.io/badge/Nginx-Alpine-009639?style=for-the-badge&logo=nginx&logoColor=white)](https://nginx.org)

<br/>

[![ITEAM University](https://img.shields.io/badge/ITEAM_University-Projet_Semestriel-6C63FF?style=for-the-badge)](https://iteam-u.tn)
[![Groupe](https://img.shields.io/badge/Groupe_1-TC--6-22D3A5?style=for-the-badge)]()
[![Année](https://img.shields.io/badge/Année-2025--2026-FFB547?style=for-the-badge)]()

<br/>

> _« Gérez votre argent intelligemment. Ensemble. »_

<br/>

---

</div>

## 📋 Table des matières

- [🎯 Présentation du projet](#-présentation-du-projet)
- [✨ Fonctionnalités](#-fonctionnalités)
- [🏗️ Architecture](#️-architecture)
- [🗄️ Base de données](#️-base-de-données)
- [🚀 Lancement rapide](#-lancement-rapide)
- [📸 Captures d'écran](#-captures-décran)
- [🔐 Sécurité](#-sécurité)
- [📧 Système d'emails](#-système-demails)
- [👥 Équipe](#-équipe)

---

## 🎯 Présentation du projet

**BudgetFlow** est une application web développée dans le cadre du **Projet Semestriel de 1ère année ING** à ITEAM University. Elle permet à des individus ou des groupes de gérer leurs finances personnelles de manière simple, sécurisée et collaborative.

### 🔍 Problème résolu

| ❌ Problème                      | ✅ Solution BudgetFlow                        |
| -------------------------------- | --------------------------------------------- |
| Outils financiers trop complexes | Interface simple et intuitive                 |
| Pas de gestion collaborative     | Budgets partagés multi-utilisateurs           |
| Aucune alerte de dépassement     | Notifications visuelles + emails automatiques |
| Données dispersées               | Tableau de bord centralisé avec graphiques    |

### 🎓 Contexte académique

```
Établissement  : ITEAM University
Formation      : 1ère année Ingénierie (cours aménagés)
Module         : Projet Semestriel
Méthodologie   : Modèle en cascade
Année          : 2025 – 2026
```

---

## ✨ Fonctionnalités

<details>
<summary><b>👤 Gestion des utilisateurs</b> — cliquez pour développer</summary>

<br/>

- ✅ Inscription avec validation par l'administrateur
- ✅ Connexion sécurisée avec sessions PHP
- ✅ Gestion des rôles : `user` et `admin`
- ✅ Modification du profil et mot de passe
- ✅ Demande de suppression de compte

</details>

<details>
<summary><b>💳 Gestion des transactions</b> — cliquez pour développer</summary>

<br/>

- ✅ Ajouter des revenus et des dépenses
- ✅ Affecter une transaction à une catégorie
- ✅ Modifier et supprimer ses transactions
- ✅ Filtrage par période, catégorie et budget

</details>

<details>
<summary><b>📊 Budgets</b> — cliquez pour développer</summary>

<br/>

- ✅ Créer des budgets individuels ou partagés
- ✅ Périodes : hebdomadaire, mensuel, personnalisé
- ✅ Plafond global et plafonds par catégorie
- ✅ Suivi de consommation en temps réel

</details>

<details>
<summary><b>🤝 Collaboration</b> — cliquez pour développer</summary>

<br/>

- ✅ Inviter des membres sur un budget partagé
- ✅ Identification de l'auteur de chaque transaction
- ✅ Vision commune des dépenses du groupe
- ✅ Invitation par email automatique

</details>

<details>
<summary><b>🔔 Alertes et tableau de bord</b> — cliquez pour développer</summary>

<br/>

- ✅ Alerte visuelle à 80% du budget
- ✅ Alerte email en cas de dépassement
- ✅ Graphique camembert par catégorie (Chart.js)
- ✅ Courbe d'évolution temporelle
- ✅ Solde, revenus, dépenses en temps réel

</details>

<details>
<summary><b>🛡️ Panneau d'administration</b> — cliquez pour développer</summary>

<br/>

- ✅ Validation des comptes en attente
- ✅ Gestion des rôles utilisateurs
- ✅ Supervision des budgets partagés
- ✅ Statistiques globales de la plateforme
- ✅ Traitement des demandes de suppression

</details>

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Navigateur Client                       │
│                   HTML + Bootstrap 5 + JS                    │
└────────────────────────┬────────────────────────────────────┘
                         │ HTTP
┌────────────────────────▼────────────────────────────────────┐
│                    Docker Network                            │
│  ┌─────────────┐   ┌─────────────┐   ┌──────────────────┐  │
│  │    Nginx    │──▶│  PHP 8.3    │──▶│  PostgreSQL 16   │  │
│  │   Alpine    │   │    FPM      │   │                  │  │
│  │  Port 8000  │   │ Architecture│   │   5 tables       │  │
│  │             │   │    MVC      │   │   propres        │  │
│  └─────────────┘   └─────────────┘   └──────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### 📁 Structure du projet

```
budgetflow/
├── 📂 public/
│   └── index.php              ← Point d'entrée unique (router)
├── 📂 app/
│   ├── 📂 controllers/        ← Logique métier
│   ├── 📂 models/             ← Accès données (PDO)
│   └── 📂 views/              ← Templates HTML/PHP
│       ├── 📂 layouts/        ← Layout principal + guest
│       ├── 📂 auth/           ← Login, Register
│       ├── 📂 dashboard/      ← Tableau de bord
│       ├── 📂 budgets/        ← Gestion budgets
│       ├── 📂 transactions/   ← Gestion transactions
│       ├── 📂 categories/     ← Gestion catégories
│       └── 📂 admin/          ← Panneau admin
├── 📂 core/
│   ├── Database.php           ← Singleton PDO PostgreSQL
│   ├── Router.php             ← Routing GET/POST
│   ├── Session.php            ← Sessions + flash messages
│   ├── Auth.php               ← Vérification des rôles
│   ├── CSRF.php               ← Protection CSRF
│   └── Mailer.php             ← PHPMailer wrapper
├── 📂 config/
│   └── config.php             ← Configuration centrale
├── 📂 database/
│   └── schema.sql             ← Schéma PostgreSQL complet
└── 📂 docker/
    ├── php.Dockerfile         ← Image PHP 8.3-FPM Alpine
    └── nginx.conf             ← Configuration Nginx
```

### 🔄 Routing

| Méthode | Route                  | Description            | Rôle requis      |
| ------- | ---------------------- | ---------------------- | ---------------- |
| `GET`   | `/login`               | Page de connexion      | —                |
| `POST`  | `/login`               | Traitement connexion   | —                |
| `GET`   | `/register`            | Page d'inscription     | —                |
| `POST`  | `/register`            | Traitement inscription | —                |
| `GET`   | `/dashboard`           | Tableau de bord        | `user`           |
| `GET`   | `/budgets`             | Liste des budgets      | `user`           |
| `POST`  | `/budgets/create`      | Créer un budget        | `user`           |
| `GET`   | `/transactions`        | Liste transactions     | `user`           |
| `POST`  | `/transactions/create` | Créer une transaction  | `user`           |
| `GET`   | `/categories`          | Gestion catégories     | `user`           |
| `GET`   | `/profile`             | Profil utilisateur     | `user`           |
| `GET`   | `/admin`               | Panneau admin          | `admin`          |
| `GET`   | `/logout`              | Déconnexion            | `user` / `admin` |

---

## 🗄️ Base de données

**PostgreSQL 16** — Schéma minimaliste et propre : **5 tables uniquement**

```sql
┌──────────────────┐       ┌──────────────────┐
│      users       │       │    categories    │
├──────────────────┤       ├──────────────────┤
│ id (PK)          │       │ id (PK)          │
│ name             │       │ user_id (FK)     │◄── NULL = défaut
│ email (UNIQUE)   │       │ name             │
│ password (bcrypt)│       │ color            │
│ role             │       │ is_default       │
│ is_active        │       └──────────────────┘
│ created_at       │
└────────┬─────────┘
         │ 1
         │ ∞
┌────────▼─────────┐       ┌──────────────────┐
│     budgets      │       │  budget_members  │
├──────────────────┤       ├──────────────────┤
│ id (PK)          │       │ id (PK)          │
│ owner_id (FK)    │◄──────│ budget_id (FK)   │
│ name             │       │ user_id (FK)     │
│ type             │       │ UNIQUE(b_id,u_id)│
│ period           │       └──────────────────┘
│ amount_limit     │
│ start_date       │
└────────┬─────────┘
         │ 1
         │ ∞
┌────────▼─────────┐
│   transactions   │
├──────────────────┤
│ id (PK)          │
│ budget_id (FK)   │
│ user_id (FK)     │
│ category_id (FK) │
│ type             │
│ amount           │
│ description      │
│ date             │
└──────────────────┘
```

### 🏷️ Catégories par défaut

| Catégorie       | Couleur   |
| --------------- | --------- |
| 🛒 Alimentation | `#22D3A5` |
| 🚗 Transport    | `#60A5FA` |
| 🏠 Logement     | `#F472B6` |
| ❤️ Santé        | `#FF6B6B` |
| 🎮 Loisirs      | `#FFB547` |
| 📚 Études       | `#A78BFA` |
| 📦 Autre        | `#8B90A7` |

---

## 🚀 Lancement rapide

### Prérequis

```
✅ Git
✅ Docker Desktop (Windows/Mac) ou Docker Engine (Linux)
```

### Installation en 3 commandes

```bash
# 1. Cloner le projet
git clone https://github.com/mouradbenabdallah/budgetflow.git
cd budgetflow

# 2. Lancer Docker
docker compose up -d --build

# 3. Ouvrir dans le navigateur
# http://localhost:8000
```

### Compte administrateur par défaut

```
📧 Email    : admin@budgetflow.local
🔑 Password : password
```

> ⚠️ Changez le mot de passe admin dès le premier lancement en production.

### Commandes utiles

```bash
# Voir les conteneurs
docker compose ps

# Voir les logs
docker compose logs -f

# Réinitialiser la base de données
docker compose down -v && docker compose up -d --build

# Accéder à PostgreSQL
docker compose exec postgres psql -U budgetflow -d budgetflow

# Lister les utilisateurs
docker compose exec postgres psql -U budgetflow -d budgetflow \
  -c "SELECT id, email, role, is_active FROM users;"
```

---

## 📸 Captures d'écran

<div align="center">

|         Page de connexion         |              Tableau de bord              |
| :-------------------------------: | :---------------------------------------: |
| ![Login](images/screen_login.png) | ![Dashboard](images/screen_dashboard.png) |

|          Gestion des budgets          |     Panneau d'administration      |
| :-----------------------------------: | :-------------------------------: |
| ![Budgets](images/screen_budgets.png) | ![Admin](images/screen_admin.png) |

|            Gestion des transactions             |                 Catégories                  |
| :---------------------------------------------: | :-----------------------------------------: |
| ![Transactions](images/screen_transactions.png) | ![Categories](images/screen_categories.png) |

> 📌 _Les captures seront ajoutées après finalisation de l'interface._

</div>

---

## 🔐 Sécurité

BudgetFlow implémente les bonnes pratiques de sécurité web :

```
✅  Mots de passe hashés avec bcrypt (password_hash / password_verify)
✅  Sessions sécurisées avec régénération d'ID après login
✅  Protection CSRF sur tous les formulaires POST
✅  Requêtes PDO préparées — zéro injection SQL possible
✅  Contrôle d'accès par rôle sur chaque route
✅  htmlspecialchars() sur toutes les sorties HTML (anti-XSS)
✅  Compte inactif par défaut — activation obligatoire par admin
✅  Validation des données côté serveur sur chaque formulaire
```

---

## 📧 Système d'emails

BudgetFlow envoie des emails automatiques via **PHPMailer + Resend SMTP** pour :

| Événement                    | Destinataire                 |
| ---------------------------- | ---------------------------- |
| ✅ Validation de compte      | Utilisateur                  |
| 📨 Invitation budget partagé | Membre invité                |
| ⚠️ Budget à 80%              | Membres du budget            |
| 🚨 Budget dépassé            | Membres du budget            |
| 🗑️ Demande de suppression    | Administrateur(s)            |
| ✅ Confirmation suppression  | Utilisateur                  |
| 📊 Récapitulatif mensuel     | Tous les utilisateurs actifs |

---

## 🛠️ Stack technique

| Couche               | Technologie             | Version |
| -------------------- | ----------------------- | ------- |
| **Frontend**         | HTML5 + Bootstrap       | 5.3.2   |
| **JavaScript**       | Vanilla JS + Chart.js   | ES6+    |
| **Backend**          | PHP natif (MVC maison)  | 8.3     |
| **Base de données**  | PostgreSQL              | 16      |
| **Serveur web**      | Nginx                   | Alpine  |
| **Conteneurisation** | Docker + Compose        | v2      |
| **Emails**           | PHPMailer + google mail | —       |
| **Architecture**     | MVC sans framework      | —       |

---

## 👥 Équipe

<div align="center">

<br/>

|                       <img src="https://github.com/mouradbenabdallah.png" width="80" style="border-radius:50%"/>                       |                    <img src="https://github.com/identicon.png" width="80" style="border-radius:50%"/>                    |                    <img src="https://github.com/identicon.png" width="80" style="border-radius:50%"/>                    |
| :------------------------------------------------------------------------------------------------------------------------------------: | :----------------------------------------------------------------------------------------------------------------------: | :----------------------------------------------------------------------------------------------------------------------: |
|                                                        **Mourad Ben Abdallah**                                                         |                                                    **Aziz Ben Hmida**                                                    |                                                   **Belhsan Jeiday**                                                    |
|                                                  Backend · Auth · Categories · Emails                                                  |                                       Frontend · Dashboard · Transactions · Admin                                        |                                                     _Role TBD_                                                      |
| [![GitHub](https://img.shields.io/badge/GitHub-mouradbenabdallah-181717?style=flat&logo=github)](https://github.com/mouradbenabdallah) | [![GitHub](https://img.shields.io/badge/GitHub-Aziz481450-181717?style=flat&logo=github)](https://github.com/Aziz481450) | [![Email](https://img.shields.io/badge/Email-jb14job-D14836?style=flat&logo=gmail&logoColor=white)](mailto:jb14job@gmail.com) |

<br/>

**Encadrement académique**

[![ITEAM](https://img.shields.io/badge/ITEAM_University-Tunis,_Tunisie-6C63FF?style=for-the-badge)](https://iteam-u.tn)

</div>

---

## 📄 Méthodologie

Ce projet suit le **modèle de développement en cascade (Waterfall)** :

```
1. Analyse des besoins    ██████████ 100%  ✅
2. Conception UML         ███        40%  ✅
3. Implémentation         █░░        80%  🔄
4. Tests                  █░░░░      60%  🔄
5. Déploiement            ░░░░░░     0%  ⏳
```

---

<div align="center">

<br/>

**BudgetFlow** — Projet Semestriel · ITEAM University · 2025–2026

Mourad Ben Abdallah & Aziz Ben Hmida\*

<br/>

![visitors](https://visitor-badge.laobi.icu/badge?page_id=mouradbenabdallah.budgetflow)

</div>
