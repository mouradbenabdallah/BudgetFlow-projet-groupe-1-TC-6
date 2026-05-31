# PROMPT 5 — Panneau d'Administration
## BudgetFlow — PHP 8.3 natif + PostgreSQL + Bootstrap 5

---

## Contexte — Ce qui existe déjà (Fonctions 1→4 terminées)

```
core/           — Database, Router, Session, Auth, CSRF, Mailer complets
assets/css/app.css          — toutes les classes bf-*
app/views/layouts/app.php   — layout complet avec sidebar
app/models/    — User, Budget, Transaction, Category complets
app/controllers/ — Auth, Dashboard, Transaction, Category, Budget complets
public/index.php — toutes les routes user définies
```

**Rappel rôle admin :**
- L'admin n'est PAS un utilisateur ordinaire
- Il ne crée pas de budgets, ni de transactions
- Il supervise uniquement : valide comptes, gère rôles, voit statistiques globales
- Redirigé vers `/admin` après login (déjà géré dans AuthController)

---

## Objectif — Fonction 5 : Panneau Admin complet

Implémenter toutes les pages et actions du panneau d'administration.

---

## Périmètre exact

```
1. Layout admin (sidebar différente de l'utilisateur)
   └── app/views/layouts/admin.php     — layout avec sidebar admin

2. Contrôleur unique
   └── app/controllers/AdminController.php

3. Vues admin
   ├── app/views/admin/index.php         — tableau de bord admin
   ├── app/views/admin/users.php         — gestion utilisateurs
   └── app/views/admin/budgets.php       — supervision budgets partagés
```

---

## Layout admin `app/views/layouts/admin.php`

Identique au layout `app.php` MAIS sidebar différente :

```
Sidebar admin (même style : #1A1D27, 256px) :
  Logo : 💰 BudgetFlow
  Badge "Administration" sous le logo (couleur #FFB547)

  Navigation admin :
    🏠 Vue d'ensemble    → /admin
    👥 Utilisateurs      → /admin/users
    📊 Budgets partagés  → /admin/budgets
    ─────────────────────
    🚪 Déconnexion       → /logout

  Bas sidebar : "Admin" badge + nom admin connecté

Topbar :
  Titre de la page
  Badge "Administrateur" en #FFB547
```

---

## Routes à ajouter

```php
// Toutes protégées par role=admin
$router->get('/admin',                    [AdminController::class, 'index']);
$router->get('/admin/users',              [AdminController::class, 'users']);
$router->post('/admin/users/validate',    [AdminController::class, 'validateUser']);
$router->post('/admin/users/role',        [AdminController::class, 'changeRole']);
$router->post('/admin/users/delete',      [AdminController::class, 'deleteUser']);
$router->get('/admin/budgets',            [AdminController::class, 'budgets']);
```

---

## Fonction 5A — Tableau de bord admin `/admin`

### `AdminController::index()`

Calculer les statistiques globales :

```php
$stats = [
  'total_users'        => COUNT users WHERE role='user',
  'pending_users'      => COUNT users WHERE is_active=false AND role='user',
  'total_budgets'      => COUNT budgets,
  'shared_budgets'     => COUNT budgets WHERE type='shared',
  'total_transactions' => COUNT transactions,
  'total_volume'       => SUM transactions.amount WHERE type='expense',

  // Derniers inscrits en attente
  'pending_list'       => 5 derniers users WHERE is_active=false, ORDER BY created_at DESC

  // Activité récente (10 dernières transactions toutes users)
  'recent_activity'    => JOIN transactions + users + budgets, LIMIT 10
]
```

### Vue `admin/index.php`

```
Titre : "Vue d'ensemble"

Section 1 — 4 cartes statistiques (row)
  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
  │ Utilisateurs │ │ En attente   │ │ Budgets      │ │ Transactions │
  │     42       │ │     3        │ │    18        │ │    234       │
  │   actifs     │ │ ⚠ valider   │ │ (5 partagés) │ │  total       │
  └──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘
  Carte "En attente" : fond orange si > 0, sinon normal
  Lien sur carte "En attente" → /admin/users?filter=pending

Section 2 — Comptes en attente de validation
  Tableau des 5 derniers inscrits en attente :
    Nom | Email | Date inscription | [✅ Valider] [🗑 Refuser]
  Si aucun : message "✓ Aucun compte en attente"

Section 3 — Activité récente
  Tableau 10 dernières transactions :
    Date | Utilisateur | Budget | Catégorie | Montant
  Lecture seule — pas d'actions
```

---

## Fonction 5B — Gestion utilisateurs `/admin/users`

### `AdminController::users()`

```php
// Filtres GET
$filter = $_GET['filter'] ?? 'all';
// all | pending | active | admin

$users = User::findAllWithFilter($filter);
// Inclure pour chaque user :
// nombre de budgets, nombre de transactions, date dernière activité
```

### Vue `admin/users.php`

```
En-tête : "Gestion des utilisateurs"
Tabs de filtre : [Tous] [En attente (N)] [Actifs] [Admins]

Tableau utilisateurs :
  Colonnes :
    Avatar (initiales) | Nom | Email | Rôle (badge) | Statut | Inscrit le | Actions

  Colonne Statut :
    ✓ Actif     → badge vert
    ⏳ En attente → badge orange
    ✗ Inactif   → badge rouge

  Colonne Actions (selon état) :

    Si is_active = false (en attente) :
      [✅ Valider le compte]  → POST /admin/users/validate
      [🗑 Supprimer]          → POST /admin/users/delete

    Si is_active = true, role = 'user' :
      [Changer rôle → Admin]  → POST /admin/users/role
      [🗑 Supprimer]

    Si role = 'admin' et pas soi-même :
      [Changer rôle → User]
      (pas de suppression d'un admin depuis l'interface)

    Si c'est soi-même : [Moi] badge, pas d'actions

Pagination : 15 users par page
```

### `POST /admin/users/validate`

```
Params : user_id, csrf_token

1. Vérifier CSRF
2. Vérifier Auth::requireRole('admin')
3. Récupérer user par id
4. Vérifier que is_active = false (pas déjà validé)
5. UPDATE users SET is_active = true WHERE id = ?
6. Envoyer email confirmation :
   Mailer::send(
     to: $user['email'],
     subject: 'Votre compte BudgetFlow a été activé',
     body: view email avec nom, lien /login, message bienvenue
   )
7. Flash success "Compte de {nom} activé. Email de confirmation envoyé."
8. Redirect /admin/users
```

### `POST /admin/users/role`

```
Params : user_id, new_role (user|admin), csrf_token

1. Vérifier CSRF + requireRole('admin')
2. Vérifier que user_id != session user_id (ne pas changer son propre rôle)
3. Valider new_role IN ('user', 'admin')
4. UPDATE users SET role = ? WHERE id = ?
5. Flash success + redirect /admin/users
```

### `POST /admin/users/delete`

```
Params : user_id, csrf_token

1. Vérifier CSRF + requireRole('admin')
2. Vérifier que user_id != session user_id
3. Vérifier que le user n'est pas admin (protection)
4. DELETE FROM users WHERE id = ?
   (CASCADE supprime budgets, transactions, memberships)
5. Flash success + redirect /admin/users
```

---

## Fonction 5C — Supervision budgets `/admin/budgets`

### `AdminController::budgets()`

```php
// Tous les budgets partagés de la plateforme
$budgets = Budget::findAllShared();
// Inclure : owner name, member count, transaction count, total spent
```

### Vue `admin/budgets.php`

```
Titre : "Budgets partagés"
Sous-titre : "Supervision de tous les budgets collaboratifs"

Tableau :
  Budget | Propriétaire | Membres | Transactions | Dépenses totales | Plafond | Statut | Créé le

  Colonne Statut (par rapport au plafond) :
    ✓ Maîtrisé   → vert
    ⚡ Proche      → orange (≥80%)
    🚨 Dépassé    → rouge (≥100%)
    — Sans limite → gris

  Cliquer sur un budget → lien vers /budgets/show?id=X
  (l'admin peut voir n'importe quel budget)

  Lecture seule — pas de modification depuis l'admin
```

---

## Méthodes à ajouter dans `app/models/User.php`

```php
findAllWithFilter(string $filter): array
// filter: all | pending | active | admin
// Inclure count budgets et transactions pour chaque user

countByFilter(string $filter): int  // pour pagination

findAllPending(): array  // is_active = false AND role = 'user'
```

## Méthodes à ajouter dans `app/models/Budget.php`

```php
findAllShared(): array
// Tous les budgets type='shared' avec owner name, member count, spent
```

---

## Email de validation de compte

Créer le template dans `app/views/emails/account_validated.php` :

```html
<!-- Email HTML inline CSS uniquement -->
<!-- Fond #0F1117, carte #1A1D27, accent #6C63FF -->
<!-- Contenu :
  Logo BudgetFlow
  "Bonjour {name},"
  "Votre compte a été validé par un administrateur.
   Vous pouvez maintenant vous connecter."
  Bouton CTA "Se connecter" → /login
  Footer ITEAM University 2025
-->
```

---

## Design admin — spécificités

- Sidebar admin : badge "ADMIN" sous le logo en `#FFB547`
- Bouton valider compte : fond `#22D3A5`, texte blanc
- Bouton supprimer : fond transparent, bordure + texte `#FF6B6B`,
  hover fond `rgba(255,107,107,0.1)`
- Tableau : alternance légère `#1A1D27` / `#1E2130`
- Confirmation suppression : `confirm()` JS avant soumission du formulaire

---

## Ordre de génération

```
1. app/views/layouts/admin.php
2. app/models/User.php               (version complète avec nouvelles méthodes)
3. app/models/Budget.php             (ajout findAllShared)
4. app/controllers/AdminController.php
5. app/views/admin/index.php
6. app/views/admin/users.php
7. app/views/admin/budgets.php
8. app/views/emails/account_validated.php
9. public/index.php                  (ajout des 6 routes admin)
```

---

## Règles absolues

1. Fichiers complets
2. `Auth::requireRole('admin')` en PREMIÈRE ligne de chaque méthode admin
3. Jamais l'admin ne peut se supprimer lui-même
4. CSRF sur tous les POST
5. `htmlspecialchars()` partout
6. Commentaires en français
