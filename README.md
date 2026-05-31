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

### Application Web de Gestion Collaborative de Budget Personnel

<br/>

[![PHP](https://img.shields.io/badge/PHP-8.3-7A86B8?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?style=for-the-badge&logo=postgresql&logoColor=white)](https://postgresql.org)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://docker.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![Nginx](https://img.shields.io/badge/Nginx-Alpine-009639?style=for-the-badge&logo=nginx&logoColor=white)](https://nginx.org)
[![Ollama](https://img.shields.io/badge/Ollama-llama3.2:1b-6C63FF?style=for-the-badge)](https://ollama.com)

<br/>

[![ITEAM University](https://img.shields.io/badge/ITEAM_University-Projet_Semestriel-6C63FF?style=for-the-badge)](https://iteam-u.tn)
[![Groupe](https://img.shields.io/badge/Groupe_1-TC--6-22D3A5?style=for-the-badge)]()
[![Année](https://img.shields.io/badge/Année-2025--2026-FFB547?style=for-the-badge)]()
[![Status](https://img.shields.io/badge/Status-Terminé-22D3A5?style=for-the-badge)]()

<br/>

> _« Gérez votre argent intelligemment. Ensemble. »_

<br/>

---

</div>

## 📋 Table des matières

- [🎯 Présentation](#-présentation)
- [✨ Fonctionnalités](#-fonctionnalités)
- [🤖 Assistant IA](#-assistant-ia)
- [📄 Rapport PDF](#-rapport-pdf)
- [🏗️ Architecture](#️-architecture)
- [🗄️ Base de données](#️-base-de-données)
- [🚀 Lancement rapide](#-lancement-rapide)
- [🔐 Sécurité](#-sécurité)
- [📧 Emails automatiques](#-emails-automatiques)
- [🛠️ Stack technique](#️-stack-technique)
- [👥 Équipe](#-équipe)

---

## 🎯 Présentation

**BudgetFlow** est une application web développée dans le cadre du **Projet Semestriel de 1ère année ING** à ITEAM University. Elle permet à des individus ou des groupes de suivre leurs finances personnelles de manière simple, sécurisée et collaborative — avec un assistant IA intégré et une génération de rapports PDF.

### Problème résolu

| ❌ Problème | ✅ Solution BudgetFlow |
|-------------|------------------------|
| Outils financiers trop complexes | Interface dark mode élégante et intuitive |
| Pas de gestion collaborative | Budgets partagés multi-utilisateurs avec invitations |
| Aucune alerte de dépassement | Alertes visuelles + emails automatiques à 80% et 100% |
| Données dispersées | Tableau de bord centralisé avec graphiques Chart.js |
| Pas de vue d'ensemble | Rapport PDF complet — mensuel ou annuel |
| Pas de conseils personnalisés | Assistant IA financier (LLM local, données privées) |

### Contexte académique

```
Établissement  : ITEAM University — Tunis, Tunisie
Formation      : 1ère année Ingénierie (cours aménagés)
Module         : Projet Semestriel
Méthodologie   : Modèle en cascade
Année          : 2025 – 2026
```

---

## ✨ Fonctionnalités

<details>
<summary><b>👤 Gestion des utilisateurs</b></summary>
<br/>

- ✅ Inscription avec validation manuelle par l'administrateur
- ✅ Connexion sécurisée avec sessions PHP
- ✅ Deux rôles distincts : `user` et `admin`
- ✅ Modification du profil, email, téléphone et mot de passe
- ✅ Demande de suppression de compte avec workflow admin
- ✅ Dark mode persistant (localStorage)

</details>

<details>
<summary><b>💳 Transactions</b></summary>
<br/>

- ✅ Ajouter des revenus et des dépenses
- ✅ Affecter une transaction à une catégorie colorée
- ✅ Modifier et supprimer ses transactions
- ✅ Historique complet avec filtres période / catégorie / budget
- ✅ Montants en Dinar Tunisien (DT)

</details>

<details>
<summary><b>📊 Budgets</b></summary>
<br/>

- ✅ Budgets personnels ou partagés
- ✅ Périodes : hebdomadaire, mensuel, personnalisé
- ✅ Plafond de dépenses configurable (ou sans limite)
- ✅ Barre de progression en temps réel
- ✅ Alerte visuelle à 80% et dépassement en rouge

</details>

<details>
<summary><b>🤝 Collaboration</b></summary>
<br/>

- ✅ Inviter des membres par email sur un budget partagé
- ✅ Chaque transaction identifie son auteur
- ✅ Vision commune des dépenses du groupe
- ✅ Retrait de membres par le propriétaire

</details>

<details>
<summary><b>📈 Tableau de bord</b></summary>
<br/>

- ✅ Solde, revenus et dépenses du mois en temps réel
- ✅ Graphique camembert des dépenses par catégorie (Chart.js)
- ✅ Courbe d'évolution temporelle des dépenses
- ✅ Top transactions récentes
- ✅ Résumé des budgets actifs

</details>

<details>
<summary><b>🛡️ Panneau d'administration</b></summary>
<br/>

- ✅ Validation des comptes utilisateurs en attente
- ✅ Gestion des rôles (promouvoir / rétrograder)
- ✅ Réinitialisation de mot de passe
- ✅ Export CSV des utilisateurs
- ✅ Supervision des budgets partagés
- ✅ Statistiques globales de la plateforme
- ✅ Envoi d'emails groupés à tous les utilisateurs

</details>

---

## 🤖 Assistant IA

BudgetFlow intègre un **assistant financier conversationnel** alimenté par **Ollama** (LLM local) — aucune donnée ne quitte le serveur.

```
Utilisateur ──POST /api/chat──▶ AiController ──cURL──▶ Ollama (Docker)
                                     │                  llama3.2:1b
                                     │ contexte financier injecté
                                     ▼
                              Solde · Dépenses · Budgets de l'utilisateur
```

**Caractéristiques :**

- Bouton flottant animé (design wavy vert) sur toutes les pages utilisateur
- Modal Bootstrap dark avec interface de chat complète
- L'IA connaît le solde, les dépenses et les budgets en temps réel
- Historique conservé côté client uniquement — zéro écriture en base
- Modèle `llama3.2:1b` — tourne entièrement dans Docker (CPU)

**Démarrage :**

```bash
# Télécharger le modèle (une seule fois — ~1.3 Go)
docker exec budgetflow_ollama ollama pull llama3.2:1b

# Vérifier
docker exec budgetflow_ollama ollama list

# Tester l'API
curl http://localhost:11434/api/tags
```

> Documentation complète → [`documentation/ai.md`](documentation/ai.md)

---

## 📄 Rapport PDF

Génère un rapport financier complet directement dans le navigateur — **sans librairie externe**, en PHP pur.

**Contenu du rapport :**

| Section | Détail |
|---------|--------|
| 📊 Statistiques | Revenus, dépenses, solde net, nombre de transactions |
| 💳 Transactions | Tableau détaillé avec date, catégorie, budget, montant |
| 💰 Budgets | Barre de progression, statut (OK / Proche / Dépassé) |
| 🏷️ Catégories | Répartition en barres avec pourcentages |

**Fonctionnement :**

```
GET  /rapport          → Formulaire (type : mensuel / annuel, sections à inclure)
POST /rapport/generer  → Page HTML optimisée impression → window.print() → PDF
```

> Bouton de génération avec design **wavy vert animé** + `pdf.gif` intégré.

---

## 🏗️ Architecture

```
┌────────────────────────────────────────────────────────────────────┐
│                         Navigateur Client                           │
│               HTML5 + Bootstrap 5.3 + Chart.js + JS                │
└───────────────────────────┬────────────────────────────────────────┘
                            │ HTTP :8000
┌───────────────────────────▼────────────────────────────────────────┐
│                        Docker Network : budgetflow                  │
│                                                                     │
│  ┌─────────────┐    ┌──────────────────┐    ┌──────────────────┐   │
│  │    Nginx    │───▶│   PHP 8.3-FPM    │───▶│  PostgreSQL 16   │   │
│  │  :8000→80   │    │   MVC natif      │    │  Alpine  :5432   │   │
│  └─────────────┘    └────────┬─────────┘    └──────────────────┘   │
│                              │ cURL                                  │
│                     ┌────────▼─────────┐                            │
│                     │     Ollama       │                            │
│                     │  llama3.2:1b     │                            │
│                     │   :11434         │                            │
│                     └──────────────────┘                            │
└────────────────────────────────────────────────────────────────────┘
```

### Structure du projet

```
budgetflow/
├── public/
│   ├── index.php              ← Point d'entrée UNIQUE (router + require_once)
│   ├── style.css              ← Classes bf-* custom
│   ├── script.js              ← Dark mode, sidebar
│   └── animations/            ← ai-chat.gif · pdf.gif
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
│   │   └── RapportController.php     ← GET /rapport · POST /rapport/generer
│   ├── models/
│   │   ├── User.php · Budget.php · Transaction.php · Category.php
│   └── views/
│       ├── layouts/  app.php · admin.php · guest.php
│       ├── partials/ sidebar.php · ai-assistant.php
│       ├── rapport/  index.php · print.php
│       └── emails/   *.php (templates emails)
│
├── core/
│   ├── Database.php   ← Singleton PDO PostgreSQL
│   ├── Router.php     ← Routing GET/POST
│   ├── Session.php    ← Sessions + flash messages (instance)
│   ├── Auth.php       ← Contrôle des rôles (statique)
│   ├── CSRF.php       ← Protection CSRF (statique)
│   └── Mailer.php     ← PHPMailer wrapper
│
├── config/config.php          ← Configuration centrale
├── database/schema.sql        ← Schéma + données initiales
├── docker/  php.Dockerfile · nginx.conf · ollama-init.sh
└── documentation/
    ├── SKILL.md   ← Référence technique complète
    ├── design.md  ← Design system BudgetFlow
    ├── ai.md      ← Intégration Ollama
    ├── docker.md  ← Guide Docker détaillé
    └── work.md    ← Guide de lancement
```

### Routes complètes

<details>
<summary>Voir toutes les routes</summary>
<br/>

**Authentification**

| Méthode | Route | Contrôleur |
|---------|-------|-----------|
| `GET` | `/login` | `AuthController::showLogin` |
| `POST` | `/login` | `AuthController::login` |
| `GET` | `/register` | `AuthController::showRegister` |
| `POST` | `/register` | `AuthController::register` |
| `POST` | `/logout` | `AuthController::logout` |

**Utilisateur** `[role: user]`

| Méthode | Route | Contrôleur |
|---------|-------|-----------|
| `GET` | `/dashboard` | `DashboardController::index` |
| `GET/POST` | `/transactions` | `TransactionController` |
| `GET/POST` | `/budgets` + `/budgets/shared` | `BudgetController` |
| `GET/POST` | `/categories` | `CategoryController` |
| `GET/POST` | `/profile` | `ProfileController` |
| `GET` | `/rapport` | `RapportController::index` |
| `POST` | `/rapport/generer` | `RapportController::generer` |
| `POST` | `/api/chat` | `AiController::chat` |

**Administration** `[role: admin]`

| Méthode | Route | Contrôleur |
|---------|-------|-----------|
| `GET` | `/admin` | `AdminController::index` |
| `GET` | `/admin/users` | `AdminController::users` |
| `GET` | `/admin/users/export` | `AdminController::exportUsers` |
| `POST` | `/admin/users/validate` | `AdminController::validateUser` |
| `POST` | `/admin/users/role` | `AdminController::changeRole` |
| `POST` | `/admin/users/delete` | `AdminController::deleteUser` |
| `POST` | `/admin/users/reset-password` | `AdminController::resetPassword` |
| `GET` | `/admin/budgets` | `AdminController::budgets` |
| `GET/POST` | `/admin/send-email` | `AdminController::sendBulkEmail` |

</details>

---

## 🗄️ Base de données

**PostgreSQL 16** — 5 tables uniquement.

```
users ──────────────────────────────────────────────────────────┐
  id · name · email · password (bcrypt) · role · is_active       │
  phone · preferences (JSONB) · last_login_at · created_at       │
                                                                  │
categories ─────────────────────────────────────────┐           │
  id · user_id (FK→users) · name · color · is_default│           │
                                                      │           │
budgets ──────────────────────────────────────────┐  │           │
  id · owner_id (FK→users) · name · type · period │  │           │
  amount_limit · start_date · created_at           │  │           │
       │                                           │  │           │
       │  budget_members ──────────────────────────┘  │           │
       │    id · budget_id (FK) · user_id (FK)        │           │
       │    UNIQUE(budget_id, user_id)                 │           │
       │                                               │           │
transactions ──────────────────────────────────────┘  │           │
  id · budget_id (FK) · user_id (FK→users)             │           │
  category_id (FK→categories) ─────────────────────────┘           │
  type (income/expense) · amount · description · date ──────────────┘
```

### Catégories par défaut

| Icône | Catégorie | Couleur |
|-------|-----------|---------|
| 🛒 | Alimentation | `#22D3A5` |
| 🚗 | Transport | `#60A5FA` |
| 🏠 | Logement | `#F472B6` |
| ❤️ | Santé | `#FF6B6B` |
| 🎮 | Loisirs | `#FFB547` |
| 📚 | Études | `#A78BFA` |
| 📦 | Autre | `#8B90A7` |

---

## 🚀 Lancement rapide

### Prérequis

```
✅ Git
✅ Docker Engine ou Docker Desktop
```

### Installation

```bash
# 1. Cloner le projet
git clone https://github.com/mouradbenabdallah/budgetflow.git
cd budgetflow

# 2. Démarrer les 4 services
docker compose up -d --build

# 3. Télécharger le modèle IA (une seule fois — ~1.3 Go)
docker exec budgetflow_ollama ollama pull llama3.2:1b

# 4. Ouvrir dans le navigateur
#    http://localhost:8000
```

### Compte administrateur par défaut

```
Email    : admin@budgetflow.local
Password : password
```

> ⚠️ Changez le mot de passe en production.

### Commandes du quotidien

```bash
# État des 4 containers
docker compose ps

# Logs en temps réel
docker compose logs -f

# Logs d'un service précis
docker compose logs -f php

# Accès PostgreSQL
docker compose exec postgres psql -U budgetflow -d budgetflow

# Reset complet (supprime toutes les données)
docker compose down -v && docker compose up -d --build

# Synchroniser un fichier modifié
docker cp fichier.php budgetflow_php:/var/www/html/fichier.php
```

---

## 🔐 Sécurité

```
✅  Mots de passe bcrypt  — password_hash() / password_verify()
✅  Sessions sécurisées   — session_regenerate_id(true) après login
✅  Protection CSRF       — token sur tous les formulaires POST
✅  PDO préparé           — zéro injection SQL possible
✅  Contrôle d'accès      — Auth::requireRole() en première ligne de chaque route
✅  Anti-XSS              — htmlspecialchars() sur toutes les sorties HTML
✅  Compte inactif        — activation obligatoire par l'administrateur
✅  Données IA locales    — Ollama dans Docker, aucun appel externe
```

---

## 📧 Emails automatiques

PHPMailer + Gmail SMTP (STARTTLS 587)

| Événement | Destinataire |
|-----------|-------------|
| Validation de compte | Utilisateur |
| Invitation budget partagé | Membre invité |
| Budget à 80% | Membres du budget |
| Budget dépassé (100%+) | Membres du budget |
| Demande de suppression de compte | Administrateurs |
| Confirmation de suppression | Utilisateur |
| Email groupé admin | Tous les utilisateurs actifs |

---

## 🛠️ Stack technique

| Couche | Technologie | Version |
|--------|-------------|---------|
| **Frontend** | HTML5 + Bootstrap + Bootstrap Icons | 5.3.3 / 1.11 |
| **Graphiques** | Chart.js | 4.4.6 |
| **Backend** | PHP natif — MVC maison | 8.3 |
| **Base de données** | PostgreSQL | 16 Alpine |
| **Serveur web** | Nginx | Alpine |
| **Conteneurisation** | Docker + Compose | v2 |
| **Emails** | PHPMailer + Gmail SMTP | STARTTLS 587 |
| **IA locale** | Ollama + llama3.2:1b | Docker CPU |
| **Polices** | DM Sans · Plus Jakarta Sans · JetBrains Mono | Google Fonts |

---

## 📊 Avancement du projet

```
 Auth + Rôles + Admin          ████████████████████  100%  ✅
 Dashboard + Charts            ████████████████████  100%  ✅
 Transactions + Catégories     ████████████████████  100%  ✅
 Budgets + Collaboration       ████████████████████  100%  ✅
 Profil + Emails + Alertes     ████████████████████  100%  ✅
 Assistant IA (Ollama)         ████████████████████  100%  ✅
 Rapport PDF                   ████████████████████  100%  ✅
 Design System Dark Mode       ████████████████████  100%  ✅
```

---

## 👥 Équipe

<div align="center">

<br/>

| | |
|:---:|:---:|
| <img src="https://github.com/mouradbenabdallah.png" width="90" style="border-radius:50%"/> | <img src="https://github.com/Aziz481450.png" width="90" style="border-radius:50%"/> |
| **Mourad Ben Abdallah** | **Aziz Ben Hmida** |
| Auth · Dashboard · Catégories | Transactions · Budgets · Admin |
| Emails · IA · Rapport PDF · Design | Profil · Frontend · Tests |
| [![GitHub](https://img.shields.io/badge/GitHub-mouradbenabdallah-181717?style=flat&logo=github)](https://github.com/mouradbenabdallah) | [![GitHub](https://img.shields.io/badge/GitHub-Aziz481450-181717?style=flat&logo=github)](https://github.com/Aziz481450) |

<br/>

[![ITEAM](https://img.shields.io/badge/ITEAM_University-Tunis,_Tunisie-6C63FF?style=for-the-badge)](https://iteam-u.tn)

<br/>

---

**BudgetFlow** — Projet Semestriel · ITEAM University · 2025–2026

*Mourad Ben Abdallah & Aziz Ben Hmida*

<br/>

![visitors](https://visitor-badge.laobi.icu/badge?page_id=mouradbenabdallah.budgetflow)

</div>
