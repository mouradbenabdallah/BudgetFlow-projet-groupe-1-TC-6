# PROMPT 4 — Budgets & Collaboration
## BudgetFlow — PHP 8.3 natif + PostgreSQL + Bootstrap 5

---

## Contexte — Ce qui existe déjà (Fonctions 1, 2, 3 terminées)

```
core/           — Database, Router, Session, Auth, CSRF, Mailer complets
assets/css/app.css          — toutes les classes bf-*
app/views/layouts/app.php   — layout complet avec sidebar
app/models/User.php         — complet
app/models/Budget.php       — findByUser, getTotalSpent (à étendre)
app/models/Transaction.php  — complet
app/models/Category.php     — complet
app/controllers/DashboardController.php
app/controllers/TransactionController.php
app/controllers/CategoryController.php
public/index.php — routes: /login /register /logout /dashboard
                            /transactions/* /categories/*
```

---

## Objectif — Fonction 4 : Budgets + Collaboration

Implémenter la gestion complète des budgets (créer, modifier, voir le détail,
supprimer) et la fonctionnalité collaborative (inviter des membres, voir les
transactions du groupe, identifier l'auteur de chaque transaction).

---

## Périmètre exact

```
1. Budgets
   ├── app/controllers/BudgetController.php
   ├── app/views/budgets/index.php     — liste des budgets
   ├── app/views/budgets/form.php      — créer / modifier
   └── app/views/budgets/show.php      — détail budget + membres + transactions

2. Collaboration
   └── (intégré dans BudgetController et budgets/show.php)
       POST /budgets/invite → inviter un membre
       POST /budgets/remove-member → retirer un membre
```

---

## Fonction 4A — Liste des budgets `/budgets`

### Routes

```php
$router->get('/budgets',            [BudgetController::class, 'index']);
$router->get('/budgets/create',     [BudgetController::class, 'showCreate']);
$router->post('/budgets/create',    [BudgetController::class, 'create']);
$router->get('/budgets/show',       [BudgetController::class, 'show']);
$router->get('/budgets/edit',       [BudgetController::class, 'showEdit']);
$router->post('/budgets/edit',      [BudgetController::class, 'edit']);
$router->post('/budgets/delete',    [BudgetController::class, 'delete']);
$router->post('/budgets/invite',    [BudgetController::class, 'invite']);
$router->post('/budgets/remove-member', [BudgetController::class, 'removeMember']);
```

### Page liste `index.php`

```
En-tête : "Mes Budgets" + bouton "＋ Nouveau budget"

Deux sections :

Section 1 — Mes budgets personnels (type = 'personal' et owner_id = user_id)
  Grille 3 colonnes (desktop) de cartes bf-card :
    ┌────────────────────────────────┐
    │ Nom du budget        [personal]│
    │ Période : Mensuel              │
    │ ████████░░░░  75% — 750/1000€ │
    │ Statut : ✓ Maîtrisé           │
    │              [Voir] [Modifier] │
    └────────────────────────────────┘
  Barre de progression colorée dynamiquement

Section 2 — Budgets partagés (type = 'shared', owner OU member)
  Même grille avec en plus :
    Avatars des membres (cercles initiales, max 3 affichés + "+N")
    Badge "Propriétaire" si owner, "Membre" si member

Si aucun budget : illustration + message + bouton créer
```

### Formulaire créer/modifier `form.php`

```
Champs :

1. Nom du budget (bf-input, required, max 100 chars)

2. Type (toggle buttons) :
   [👤 Personnel]  [👥 Partagé]
   Style identique au toggle income/expense de la Fonction 3

3. Période (select bf-input) :
   - Mensuel (monthly)
   - Hebdomadaire (weekly)
   - Personnalisé (custom)

4. Date de début (bf-input, type=date)
   Affiché seulement si période = 'custom'
   Sinon : auto-calculé (1er du mois en cours ou lundi en cours)

5. Plafond global (bf-input, type=number, optional)
   Préfixe "€"
   Placeholder "Aucune limite"

6. Bouton "Créer le budget" / "Enregistrer" (bf-btn-primary, full width)

JS :
  - Toggle type : mettre à jour hidden input
  - Afficher/masquer champ date selon période choisie
```

---

## Fonction 4B — Détail d'un budget `/budgets/show?id=X`

### Page `show.php` — structure complète

```
En-tête :
  Nom du budget + badge type (personal/shared)
  Sous-titre : période + date début
  Boutons : [✏️ Modifier] [🗑 Supprimer] (seulement si owner)

Section 1 — Statistiques du budget (3 cartes)
  Revenus totaux | Dépenses totales | Solde
  + Barre de progression plafond global si amount_limit défini

Section 2 — Répartition par catégorie
  Graphique doughnut Chart.js (même config dark que dashboard)
  + liste catégories avec montants à droite

Section 3 — Membres (seulement si type = 'shared')
  Titre "Membres du budget"
  Liste des membres :
    [Avatar initiales] Nom — Email   [🗑 Retirer] (seulement si owner et pas soi-même)
  Formulaire inline "Inviter un membre" (si owner) :
    Input email + bouton "Inviter" → POST /budgets/invite
    CSRF token inclus

Section 4 — Transactions de ce budget
  Tableau complet :
    Date | Auteur (avatar + nom) | Catégorie | Description | Montant
  L'auteur est identifié par user_id → afficher son nom
  Filtre par type (tous / revenus / dépenses) via tabs Bootstrap
  Bouton "＋ Ajouter une transaction" → /transactions/create?budget_id=X
```

---

## Fonction 4C — Collaboration : inviter un membre

### `POST /budgets/invite`

```
Params POST : budget_id, email, csrf_token

Traitement :
1. Vérifier CSRF
2. Vérifier que l'utilisateur est owner du budget
3. Vérifier que le budget est de type 'shared'
4. Chercher l'utilisateur par email dans la table users

   Si trouvé ET is_active = true :
     → Vérifier qu'il n'est pas déjà membre (UNIQUE constraint)
     → INSERT INTO budget_members (budget_id, user_id)
     → Envoyer email d'invitation (Mailer::sendBudgetInvitation())
     → Flash success "Membre ajouté avec succès"

   Si trouvé ET is_active = false :
     → Flash warning "Cet utilisateur n'a pas encore activé son compte"

   Si non trouvé :
     → Flash warning "Aucun compte trouvé avec cet email.
       L'utilisateur doit d'abord créer un compte BudgetFlow."

5. Redirect vers /budgets/show?id=X
```

### `POST /budgets/remove-member`

```
Params POST : budget_id, user_id, csrf_token

Traitement :
1. Vérifier CSRF
2. Vérifier que l'utilisateur connecté est owner
3. Vérifier qu'on ne supprime pas le owner lui-même
4. DELETE FROM budget_members WHERE budget_id = ? AND user_id = ?
5. Flash success + redirect /budgets/show?id=X
```

---

## Alertes visuelles budget

Calculer dans `BudgetController::show()` :
```php
$percent = $budget['amount_limit'] > 0
    ? ($budget['spent'] / $budget['amount_limit']) * 100
    : 0;

$status = match(true) {
    $percent >= 100 => 'danger',   // #FF4D4D
    $percent >= 80  => 'warning',  // #FFB547
    default         => 'ok',       // #22D3A5
};
```

Afficher dans la vue :
```html
<!-- Si warning (≥80%) -->
<div class="bf-alert" style="background:rgba(255,181,71,0.1);
     border-color:rgba(255,181,71,0.3); color:#FFB547;">
  ⚡ Attention : vous avez consommé <?= $percent ?>% de votre budget
</div>

<!-- Si danger (≥100%) -->
<div class="bf-alert" style="background:rgba(255,77,77,0.1);
     border-color:rgba(255,77,77,0.3); color:#FF4D4D;">
  🚨 Budget dépassé de <?= number_format($overAmount, 2, ',', ' ') ?> €
</div>
```

---

## Méthodes à compléter dans `app/models/Budget.php`

```php
findByUser(int $userId): array
// SELECT budgets dont owner_id = userId OU dans budget_members

findById(int $id): ?array

create(array $data): int

update(int $id, array $data): bool

delete(int $id): bool

getMembers(int $budgetId): array
// JOIN avec users pour retourner [id, name, email] de chaque membre

addMember(int $budgetId, int $userId): bool

removeMember(int $budgetId, int $userId): bool

getTotalSpent(int $budgetId): float
// SUM des transactions type=expense de ce budget

getTotalIncome(int $budgetId): float

getCategoryBreakdown(int $budgetId): array
// Répartition dépenses par catégorie pour ce budget

belongsToUser(int $budgetId, int $userId): bool
// Vérifie owner_id = userId OU dans budget_members
```

---

## Design — appliquer design.md EXACTEMENT

- Avatars membres : cercle 36px, fond `rgba(108,99,255,0.15)`, texte `#6C63FF`,
  initiales en majuscules, font-weight 600
- Barres progression : hauteur 8px, border-radius 999px, couleur dynamique
- Tabs transactions : Bootstrap nav-tabs stylisées dark
- Formulaire invite : inline dans la page (pas modal), compact

---

## Ordre de génération

```
1. app/models/Budget.php              (version complète)
2. app/controllers/BudgetController.php
3. app/views/budgets/index.php
4. app/views/budgets/form.php
5. app/views/budgets/show.php
6. public/index.php                   (ajout des 9 nouvelles routes)
```

---

## Règles absolues

1. Fichiers complets
2. `Auth::requireRole('user')` en première ligne de chaque méthode
3. Vérifier ownership sur chaque action destructive (edit/delete/invite/remove)
4. CSRF sur tous les POST
5. `htmlspecialchars()` sur toutes les sorties
6. Commentaires en français
