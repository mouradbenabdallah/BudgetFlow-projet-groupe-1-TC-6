




# PROMPT 6 — Profil, Emails & Alertes
## BudgetFlow — PHP 8.3 natif + PostgreSQL + Bootstrap 5

---

## Contexte — Ce qui existe déjà (Fonctions 1→5 terminées)

```
core/Mailer.php    — gmail déjà créé (à compléter)
app/views/emails/account_validated.php  — déjà créé en Fonction 5
Tous les controllers, models, vues des fonctions 1-5 complets
public/index.php   — toutes les routes 1-5 définies
```

---

## Objectif — Fonction 6 : Profil + Emails complets + Alertes

Trois sous-fonctions :
- **6A** — Page profil utilisateur (modifier infos, mot de passe, demande suppression)
- **6B** — Système d'emails complet (PHPMailer + Resend)
- **6C** — Alertes dépassement budget (visuel déjà fait, ajouter l'email)

---

## Périmètre exact

```
1. Profil
   ├── app/controllers/ProfileController.php
   └── app/views/profile/index.php

2. Emails
   ├── core/Mailer.php                          — complet avec toutes les méthodes
   ├── app/views/emails/account_validated.php   — déjà fait, vérifier
   ├── app/views/emails/budget_invitation.php
   ├── app/views/emails/budget_alert.php
   ├── app/views/emails/deletion_request_admin.php
   └── app/views/emails/deletion_confirmed.php

3. Alertes
   └── (intégré dans TransactionController après chaque create)
```

---

## Fonction 6A — Page profil `/profile`

### Routes

```php
$router->get('/profile',               [ProfileController::class, 'index']);
$router->post('/profile/update-info',  [ProfileController::class, 'updateInfo']);
$router->post('/profile/update-password', [ProfileController::class, 'updatePassword']);
$router->post('/profile/request-deletion', [ProfileController::class, 'requestDeletion']);
```

### Vue `profile/index.php`

```
Layout : app.php (sidebar utilisateur)
Titre : "Mon Profil"

Section 1 — Informations personnelles (bf-card)
  Avatar grand (cercle 80px, initiales, fond violet)
  Formulaire :
    Nom complet (bf-input, pré-rempli)
    Email (bf-input, pré-rempli)
    Bouton "Enregistrer les modifications" (bf-btn-primary)

Section 2 — Changer le mot de passe (bf-card)
  Formulaire :
    Mot de passe actuel (bf-input, type=password)
    Nouveau mot de passe (bf-input, type=password, min=8)
    Confirmer nouveau mot de passe (bf-input, type=password)
    Bouton "Changer le mot de passe" (bf-btn-primary)

Section 3 — Statistiques personnelles (bf-card, lecture seule)
  Membre depuis : {created_at formaté}
  Nombre de budgets : X
  Nombre de transactions : X
  Total dépensé (tous budgets) : X €

Section 4 — Zone danger (bf-card avec bordure rouge)
  Titre rouge "Zone de danger"
  Texte : "Demander la suppression de votre compte.
           Un administrateur traitera votre demande
           et vous serez notifié par email."
  Bouton "Demander la suppression" :
    Fond transparent, bordure #FF6B6B, texte #FF6B6B
    Hover : fond rgba(255,107,107,0.1)
    Confirm JS avant soumission
```

### Traitements POST profil

**`updateInfo` :**
```
Valider : name (required, 2-100 chars), email (required, email valide)
Vérifier unicité email si changé (pas d'autre user avec cet email)
UPDATE users SET name=?, email=? WHERE id=session_user_id
Flash success "Profil mis à jour."
Mettre à jour $_SESSION['name'] et $_SESSION['email']
Redirect /profile
```

**`updatePassword` :**
```
Valider : current_password (required)
Vérifier password_verify(current_password, hash_en_base)
Si faux → flash danger "Mot de passe actuel incorrect"
Valider : new_password min 8 chars
Valider : new_password === confirm_password
UPDATE users SET password=password_hash(new_password, PASSWORD_BCRYPT) WHERE id=?
Flash success "Mot de passe modifié."
Redirect /profile
```

**`requestDeletion` :**
```
1. Vérifier CSRF
2. Récupérer l'utilisateur connecté
3. Envoyer email à tous les admins :
   Mailer::sendDeletionRequestToAdmins($user)
4. Flash info "Votre demande a été envoyée à l'administrateur.
               Vous serez notifié par email de la décision."
5. Redirect /profile
```

---

## Fonction 6B — Système d'emails complet

### `core/Mailer.php` — complet

```php
class Mailer {

    private static function getMailer(): PHPMailer
    // Configurer PHPMailer avec config/config.php :
    // Host, Port, Username, Password, Encryption, FromEmail, FromName
    // CharSet = 'UTF-8'
    // isHTML(true)

    private static function renderTemplate(string $template, array $data): string
    // Charger app/views/emails/{template}.php avec extract($data)
    // Retourner le HTML généré (output buffering)

    public static function send(string $to, string $toName, string $subject, string $template, array $data): bool
    // Wrapper principal — try/catch, log en cas d'erreur

    // ── Méthodes spécifiques ──────────────────────────────

    public static function sendAccountValidated(array $user): bool
    // Template: account_validated
    // Sujet: "Votre compte BudgetFlow a été activé"

    public static function sendBudgetInvitation(array $invitee, array $inviter, array $budget): bool
    // Template: budget_invitation
    // Sujet: "[BudgetFlow] Invitation — {budget name}"

    public static function sendBudgetAlert(array $user, array $budget, float $percent, float $spent, float $limit): bool
    // Template: budget_alert
    // Sujet dynamique selon percent

    public static function sendDeletionRequestToAdmins(array $requestingUser): bool
    // Récupérer tous les admins : User::findAllAdmins()
    // Envoyer à chaque admin
    // Template: deletion_request_admin
    // Sujet: "[Admin BudgetFlow] Demande de suppression — {user name}"

    public static function sendDeletionConfirmed(array $user): bool
    // Template: deletion_confirmed
    // Sujet: "Votre compte BudgetFlow a été supprimé"
}
```

### Templates emails — règles communes

```
Tous les templates :
- HTML valide avec inline CSS uniquement (compatible Gmail)
- Largeur max 600px, centré
- Fond body : #0F1117
- Carte centrale : #1A1D27, border 1px solid #2A2F45, border-radius 16px, padding 32px
- Logo en haut : "💰 BudgetFlow" en #6C63FF, 24px, bold, centré
- Bouton CTA : fond #6C63FF, blanc, padding 12px 28px, border-radius 10px
- Footer : "© 2025 BudgetFlow — ITEAM University" en #555B75, 12px
- Police : Arial, Helvetica, sans-serif (pas Google Fonts — incompatible email)
```

### Template `budget_invitation.php`

```
Variables : $invitee (array), $inviter (array), $budget (array)

Contenu :
"Bonjour {invitee.name},"
"{inviter.name} vous invite à rejoindre le budget « {budget.name} »."
"Période : {budget.period} | Démarré le : {budget.start_date}"
"Plafond : {budget.amount_limit} €" (si défini)
CTA "Voir le budget" → /budgets/show?id={budget.id}
Note : "Si vous n'avez pas de compte, créez-en un sur BudgetFlow."
```

### Template `budget_alert.php`

```
Variables : $user (array), $budget (array), $percent (float), $spent (float), $limit (float)

Bandeau couleur dynamique en haut :
  $percent >= 100 → fond #FF4D4D (rouge)
  $percent >= 80  → fond #FFB547 (orange)

Contenu :
"Bonjour {user.name},"
"Le budget « {budget.name} » a atteint {percent}% de son plafond."
Tableau :
  Dépenses : {spent} €
  Plafond  : {limit} €
  Écart    : {limit - spent} € restants (ou "Dépassé de X €")
CTA "Voir le budget" → /budgets/show?id={budget.id}
```

### Template `deletion_request_admin.php`

```
Variables : $requestingUser (array)

Contenu :
"Bonjour Administrateur,"
"L'utilisateur {name} ({email}) a demandé la suppression de son compte."
"Inscrit le : {created_at}"
"Nombre de budgets : {count}"
CTA "Gérer les utilisateurs" → /admin/users
```

### Template `deletion_confirmed.php`

```
Variables : $user (array)

Contenu :
"Bonjour {name},"
"Votre compte BudgetFlow a été supprimé suite à votre demande."
"Vos données seront définitivement effacées."
"Merci d'avoir utilisé BudgetFlow."
Pas de CTA.
```

---

## Fonction 6C — Alertes budget par email

### Intégration dans `TransactionController::create()`

Après chaque insertion réussie d'une transaction de type `expense` :

```php
// Calculer le nouveau total dépensé du budget
$budget = Budget::findById($budgetId);
$spent  = Budget::getTotalSpent($budgetId);
$limit  = $budget['amount_limit'];

if ($limit > 0) {
    $percent = ($spent / $limit) * 100;

    // Envoyer email d'alerte si seuils franchis
    // ET seulement si pas déjà envoyé récemment (stocker en session ou vérifier)
    if ($percent >= 80) {
        // Récupérer tous les membres du budget (owner + budget_members)
        $members = Budget::getMembers($budgetId);
        // Ajouter le owner si pas déjà dans members
        foreach ($members as $member) {
            try {
                Mailer::sendBudgetAlert($member, $budget, $percent, $spent, $limit);
            } catch (Exception $e) {
                error_log('Alert mail failed: ' . $e->getMessage());
                // Ne pas faire planter la requête pour un email raté
            }
        }
    }
}
```

**Guard anti-doublon (simple, sans table alerts) :**
```php
// Stocker dans session : dernière alerte envoyée par budget
$alertKey = "alert_sent_{$budgetId}";
$lastAlert = Session::get($alertKey);

// N'envoyer que si pas envoyé dans les dernières 24h
if (!$lastAlert || (time() - $lastAlert) > 86400) {
    // ... envoyer email ...
    Session::set($alertKey, time());
}
```

---

## Méthodes à ajouter dans `app/models/User.php`

```php
findAllAdmins(): array
// SELECT * FROM users WHERE role = 'admin' AND is_active = true

getStats(int $userId): array
// [budgets_count, transactions_count, total_spent, member_since]
```

---

## Ordre de génération

```
1.  core/Mailer.php                              (complet)
2.  app/views/emails/account_validated.php       (vérifier/compléter)
3.  app/views/emails/budget_invitation.php
4.  app/views/emails/budget_alert.php
5.  app/views/emails/deletion_request_admin.php
6.  app/views/emails/deletion_confirmed.php
7.  app/models/User.php                          (ajout findAllAdmins, getStats)
8.  app/controllers/ProfileController.php
9.  app/views/profile/index.php
10. app/controllers/TransactionController.php    (ajout logique alerte après create)
11. public/index.php                             (ajout des 4 routes profil)
```

---

## Règles absolues

1. Fichiers complets
2. Tous les emails : try/catch — jamais laisser un email planter une requête HTTP
3. CSS emails : inline uniquement — pas de classes Bootstrap dans les emails
4. `Auth::requireRole('user')` sur toutes les routes profil
5. CSRF sur tous les POST profil
6. `htmlspecialchars()` partout
7. Commentaires en français
