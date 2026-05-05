# PROMPT 3 — Transactions & Catégories
## BudgetFlow — PHP 8.3 natif + PostgreSQL + Bootstrap 5

---

## Contexte — Ce qui existe déjà (Fonctions 1 + 2 terminées)

```
core/          — Database, Router, Session, Auth, CSRF complets
config/        — config.php complet
assets/css/    — app.css avec toutes les classes bf-*
assets/js/     — charts.js
app/models/    — User, Budget, Transaction, Category
app/views/layouts/app.php    — layout sidebar + topbar complet
app/views/partials/sidebar.php
app/views/dashboard/index.php
app/controllers/DashboardController.php
public/index.php — routes: /login /register /logout /dashboard
```

---

## Objectif — Fonction 3 : Transactions + Catégories

Implémenter la gestion complète des transactions (liste, ajout, modification,
suppression) et la gestion des catégories personnalisées.

---

## Périmètre exact

```
1. Transactions
   ├── app/controllers/TransactionController.php
   ├── app/views/transactions/index.php    — liste filtrée
   └── app/views/transactions/form.php     — formulaire ajout/modification

2. Catégories
   ├── app/controllers/CategoryController.php
   └── app/views/categories/index.php      — liste + gestion
```

---

## Fonction 3A — Gestion des transactions

### Routes à ajouter

```php
$router->get('/transactions',          [TransactionController::class, 'index']);
$router->get('/transactions/create',   [TransactionController::class, 'showCreate']);
$router->post('/transactions/create',  [TransactionController::class, 'create']);
$router->get('/transactions/edit',     [TransactionController::class, 'showEdit']);
$router->post('/transactions/edit',    [TransactionController::class, 'edit']);
$router->post('/transactions/delete',  [TransactionController::class, 'delete']);
```

### Page liste `/transactions` — `index.php`

**Barre de filtres (GET params) :**
```
?type=all|income|expense
?budget_id=X
?category_id=X
?month=YYYY-MM   (défaut : mois en cours)
```

**Affichage :**
```
En-tête :
  Titre "Transactions" + bouton "＋ Nouvelle transaction" → /transactions/create

Résumé du mois filtré (3 mini-cartes) :
  Total revenus | Total dépenses | Solde

Tableau transactions :
  Colonnes : Date | Type (badge) | Catégorie (dot+nom) | Description | Budget | Montant
  Tri : date DESC
  Actions par ligne : ✏️ Modifier | 🗑 Supprimer
  Supprimer : confirmation JS (confirm('Supprimer cette transaction ?'))
  Pagination : 20 par page (GET param ?page=N)

Si aucune transaction : illustration vide + message + bouton créer
```

**Badges type :**
```html
<!-- Revenu -->
<span style="background:rgba(34,211,165,0.15); color:#22D3A5;
             padding:3px 10px; border-radius:999px; font-size:11px; font-weight:600;">
  ↑ Revenu
</span>
<!-- Dépense -->
<span style="background:rgba(255,107,107,0.15); color:#FF6B6B;
             padding:3px 10px; border-radius:999px; font-size:11px; font-weight:600;">
  ↓ Dépense
</span>
```

### Formulaire `/transactions/create` et `/transactions/edit` — `form.php`

```
Champs :
  1. Toggle Type : boutons "💰 Revenu" / "💸 Dépense"
     Style actif : fond #6C63FF | inactif : fond #222636
     Stocké dans hidden input name="type"

  2. Montant (bf-input, type=number, step=0.01, min=0.01, required)
     Préfixe "€" dans le champ (input-group Bootstrap)

  3. Date (bf-input, type=date, défaut=aujourd'hui)

  4. Budget (select bf-input)
     Options : budgets dont l'utilisateur est owner OU member
     Placeholder "Sélectionner un budget"

  5. Catégorie (select bf-input avec dot coloré JS)
     Options : catégories par défaut + catégories personnelles de l'utilisateur
     Un point coloré devant chaque option (JS dynamique)

  6. Description (bf-input, type=text, optional, max=255)

  Bouton : "Enregistrer la transaction" (bf-btn-primary, full width)
  Lien retour : "← Retour aux transactions"

En mode édition :
  Pré-remplir tous les champs avec les valeurs existantes
  Vérifier que la transaction appartient bien à l'utilisateur connecté
  (sinon 403)
```

### Traitement POST create/edit

```
Validation server-side :
  - type : doit être 'income' ou 'expense'
  - amount : numérique, > 0
  - date : format date valide, pas dans le futur lointain
  - budget_id : doit appartenir à l'utilisateur (owner ou member)
  - category_id : optionnel, mais si fourni doit exister
  - description : max 255 chars, XSS escape

Si erreur → flash message + redirect vers formulaire avec données GET
Si succès → flash success + redirect vers /transactions
```

### Suppression POST

```
Vérifier CSRF token
Vérifier que la transaction appartient à l'utilisateur (user_id = session user_id)
Sinon : abort 403
Si ok : DELETE + redirect /transactions avec flash success
```

---

## Fonction 3B — Gestion des catégories

### Routes à ajouter

```php
$router->get('/categories',         [CategoryController::class, 'index']);
$router->post('/categories/create', [CategoryController::class, 'create']);
$router->post('/categories/edit',   [CategoryController::class, 'edit']);
$router->post('/categories/delete', [CategoryController::class, 'delete']);
```

### Page `/categories` — `index.php`

```
En-tête : "Catégories" + bouton "＋ Nouvelle catégorie"

Section 1 — Catégories par défaut (is_default = true)
  Grille de cartes (3 colonnes desktop, 2 tablette, 1 mobile)
  Chaque carte bf-card :
    Cercle coloré (couleur de la catégorie) + Nom
    Badge "Par défaut" — pas de bouton modifier/supprimer
    Couleurs : Alimentation #22D3A5, Transport #60A5FA,
               Logement #F472B6, Santé #FF6B6B,
               Loisirs #FFB547, Études #A78BFA, Autre #8B90A7

Section 2 — Mes catégories (user_id = session user_id)
  Même grille
  Chaque carte : cercle coloré + nom + boutons ✏️ Modifier | 🗑 Supprimer
  Si aucune : message "Créez votre première catégorie personnalisée"

Modal "Nouvelle catégorie" (Bootstrap modal) :
  Champ Nom (bf-input, required, max 80 chars)
  Sélecteur de couleur :
    8 cercles cliquables avec couleurs prédéfinies :
    #6C63FF #22D3A5 #FF6B6B #FFB547 #60A5FA #F472B6 #A78BFA #8B90A7
    + input color natif pour couleur personnalisée
    Stocker dans hidden input name="color"
  Bouton "Créer" (bf-btn-primary)

Modal "Modifier catégorie" :
  Mêmes champs pré-remplis
  Champ hidden name="id" avec l'id de la catégorie
```

### Traitement POST catégories

```
create :
  Valider : name requis, max 80 chars
  Valider : color format hex (#RRGGBB)
  Vérifier unicité : pas de doublon (name, user_id)
  Insérer avec user_id = session user_id, is_default = false

edit :
  Vérifier que la catégorie appartient à l'utilisateur (user_id = session)
  Même validation que create

delete :
  Vérifier CSRF + appartenance
  Vérifier que is_default = false (impossible de supprimer une catégorie par défaut)
  Si la catégorie est utilisée dans des transactions →
    message warning "Cette catégorie est utilisée par X transactions.
    Les transactions seront conservées sans catégorie."
  Supprimer → les transactions passent à category_id = NULL (ON DELETE SET NULL)
```

---

## Méthodes à ajouter dans `app/models/Transaction.php`

```php
findByUser(int $userId, array $filters = []): array
// filters: type, budget_id, category_id, month (YYYY-MM), page

sumByTypeAndUser(int $userId, string $type, string $month): float

create(array $data): int   // retourne id inséré

findById(int $id): ?array

update(int $id, array $data): bool

delete(int $id): bool

countByUser(int $userId, array $filters = []): int  // pour pagination
```

## Méthodes à ajouter dans `app/models/Category.php`

```php
findAllForUser(int $userId): array
// retourne catégories par défaut (is_default=true) + catégories de l'utilisateur

findById(int $id): ?array

create(array $data): int

update(int $id, array $data): bool

delete(int $id): bool

isUsedInTransactions(int $categoryId): int  // compte les transactions
```

---

## Design — appliquer design.md EXACTEMENT

- Tous les formulaires : bf-input, bf-btn-primary
- Toggle Income/Expense : animation JS smooth, transition 0.2s
- Tableau : fond #1A1D27, lignes hover #2A2F45, bordures #2A2F45
- Pagination : Bootstrap pagination avec couleur active #6C63FF
- Modals : fond #1A1D27, border #2A2F45, backdrop dark
- Sélecteur couleur catégorie : cercles 28px, border 2px solid transparent,
  border active #6C63FF + box-shadow glow violet

---

## Ordre de génération

```
1. app/models/Transaction.php       (version complète avec toutes les méthodes)
2. app/models/Category.php          (version complète)
3. app/controllers/TransactionController.php
4. app/views/transactions/index.php
5. app/views/transactions/form.php
6. app/controllers/CategoryController.php
7. app/views/categories/index.php
8. public/index.php                 (ajout des 7 nouvelles routes)
```

---

## Règles absolues

1. Fichiers complets — aucun `// ...reste du code`
2. `Auth::requireRole('user')` en première ligne de chaque méthode controller
3. CSRF validé sur chaque POST
4. Vérification propriété sur chaque edit/delete (user_id = session)
5. Pagination : 20 transactions par page, liens ?page=N
6. `htmlspecialchars()` sur toutes les sorties
7. Commentaires en français
