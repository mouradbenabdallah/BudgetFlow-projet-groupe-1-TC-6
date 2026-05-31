<?php

declare(strict_types=1);

/**
 * Contrôleur Administration.
 *
 * Supervise les utilisateurs, les rôles, les budgets partagés et la communication.
 * Toutes les méthodes exigent le rôle 'admin' via Auth::requireRole().
 *
 * Routes déclarées dans public/index.php :
 *   GET  /admin                    → index()
 *   POST /admin/test-email         → testEmail()
 *   GET  /admin/users              → users()
 *   GET  /admin/users/export       → exportUsers()
 *   POST /admin/users/validate     → validateUser()
 *   POST /admin/users/role         → changeRole()
 *   POST /admin/users/delete       → deleteUser()
 *   POST /admin/users/reset-password → resetPassword()
 *   GET  /admin/budgets            → budgets()
 *   GET  /admin/send-email         → showSendEmail()
 *   POST /admin/send-email         → sendBulkEmail()
 */
class AdminController
{
    private Session $session;
    private User    $userModel;
    private Budget  $budgetModel;

    public function __construct()
    {
        $this->session     = new Session();
        $this->userModel   = new User();
        $this->budgetModel = new Budget();
    }

    /** Tableau de bord : statistiques globales + comptes en attente + activité récente. */
    public function index(): void
    {
        Auth::requireRole('admin');

        $pdo   = Database::getInstance();
        $stats = [];

        $stats['total_users'] = (int) $pdo->query(
            "SELECT COUNT(*) FROM users WHERE role = 'user'"
        )->fetchColumn();

        $stats['pending_users'] = (int) $pdo->query(
            "SELECT COUNT(*) FROM users WHERE is_active = false AND role = 'user'"
        )->fetchColumn();

        $stats['total_budgets'] = (int) $pdo->query(
            "SELECT COUNT(*) FROM budgets"
        )->fetchColumn();

        $stats['shared_budgets'] = (int) $pdo->query(
            "SELECT COUNT(*) FROM budgets WHERE type = 'shared'"
        )->fetchColumn();

        $stats['total_transactions'] = (int) $pdo->query(
            "SELECT COUNT(*) FROM transactions"
        )->fetchColumn();

        $stats['total_volume'] = (float) $pdo->query(
            "SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'expense'"
        )->fetchColumn();

        // Inscriptions des 7 derniers jours (graphique).
        $stmt = $pdo->query(
            "SELECT TO_CHAR(created_at::date, 'DD/MM') AS day, COUNT(*) AS cnt
             FROM users
             WHERE created_at >= NOW() - INTERVAL '7 days' AND role = 'user'
             GROUP BY created_at::date
             ORDER BY created_at::date ASC"
        );
        $stats['registrations_chart'] = $stmt->fetchAll();

        $stats['pending_list'] = $this->userModel->findAllPending(5);

        // Données pour onglet Analytiques — 6 derniers mois.
        $stmt = $pdo->query(
            "SELECT TO_CHAR(DATE_TRUNC('month', created_at), 'Mon') AS month,
                    COUNT(*) AS users
             FROM users
             WHERE created_at >= NOW() - INTERVAL '6 months' AND role = 'user'
             GROUP BY DATE_TRUNC('month', created_at)
             ORDER BY DATE_TRUNC('month', created_at) ASC"
        );
        $stats['monthly_users'] = $stmt->fetchAll();

        $stmt = $pdo->query(
            "SELECT TO_CHAR(DATE_TRUNC('month', date), 'Mon') AS month,
                    COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END), 0) AS expenses,
                    COALESCE(SUM(CASE WHEN type='income'  THEN amount ELSE 0 END), 0) AS income
             FROM transactions
             WHERE date >= NOW() - INTERVAL '6 months'
             GROUP BY DATE_TRUNC('month', date)
             ORDER BY DATE_TRUNC('month', date) ASC"
        );
        $stats['monthly_tx'] = $stmt->fetchAll();

        $stmt = $pdo->query(
            "SELECT t.id, t.type, t.amount, t.description, t.date,
                    u.name AS user_name,
                    b.name AS budget_name,
                    c.name AS category_name
             FROM transactions t
             JOIN users u ON u.id = t.user_id
             JOIN budgets b ON b.id = t.budget_id
             LEFT JOIN categories c ON c.id = t.category_id
             ORDER BY t.created_at DESC
             LIMIT 10"
        );
        $stats['recent_activity'] = $stmt->fetchAll();

        $config = require __DIR__ . '/../../config/config.php';

        $this->render('admin/index', [
            'pageTitle'    => "Vue d'ensemble",
            'stats'        => $stats,
            'mailFrom'     => $config['mail']['from_email'] ?? '',
            'flashSuccess' => $this->session->getFlash('success'),
            'flashDanger'  => $this->session->getFlash('danger'),
            'flashInfo'    => $this->session->getFlash('info'),
        ]);
    }

    /** Envoie un email de test à l'adresse de l'administrateur connecté. */
    public function testEmail(): void
    {
        Auth::requireRole('admin');

        if (!CSRF::validateToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->session->setFlash('danger', 'Session expirée.');
            $this->redirect('/admin');
        }

        $adminUser = Auth::getUser();
        $to        = (string) ($adminUser['email'] ?? '');
        $config    = require __DIR__ . '/../../config/config.php';

        $adminName = (string) ($adminUser['name'] ?? 'Administrateur');
        $fromEmail = (string) ($config['mail']['from_email'] ?? '');
        $sentAt    = date('d/m/Y H:i:s');

        ob_start();
        require __DIR__ . '/../views/emails/test.php';
        $body = (string) ob_get_clean();

        $ok = (new Mailer())->send($to, 'Email de test — BudgetFlow', $body);

        if ($ok) {
            $this->session->setFlash('success', "Email de test envoyé à {$to}.");
        } else {
            $this->session->setFlash('danger', 'Échec de l\'envoi — vérifiez MAIL_PASSWORD dans .env et les logs PHP.');
        }

        $this->redirect('/admin');
    }

    /** Liste des utilisateurs avec filtrage, recherche et pagination. */
    public function users(): void
    {
        Auth::requireRole('admin');

        $filter  = in_array($_GET['filter'] ?? '', ['all', 'pending', 'active', 'admin'], true)
                   ? ($_GET['filter'] ?? 'all') : 'all';
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 15;

        $users        = $this->userModel->findAllWithFilter($filter, $page, $perPage);
        $totalCount   = $this->userModel->countByFilter($filter);
        $totalPages   = (int) ceil($totalCount / $perPage);
        $pendingCount = $this->userModel->countByFilter('pending');

        $this->render('admin/users', [
            'pageTitle'    => 'Utilisateurs',
            'users'        => $users,
            'filter'       => $filter,
            'page'         => $page,
            'totalPages'   => $totalPages,
            'totalCount'   => $totalCount,
            'pendingCount' => $pendingCount,
            'currentUser'  => Auth::getUser(),
            'flashSuccess' => $this->session->getFlash('success'),
            'flashDanger'  => $this->session->getFlash('danger'),
            'flashInfo'    => $this->session->getFlash('info'),
        ]);
    }

    /** Exporte les utilisateurs en CSV. */
    public function exportUsers(): void
    {
        Auth::requireRole('admin');

        $filter = in_array($_GET['filter'] ?? '', ['all', 'pending', 'active', 'admin'], true)
                  ? ($_GET['filter'] ?? 'all') : 'all';

        // Récupère tous sans pagination pour l'export.
        $users = $this->userModel->findAllWithFilter($filter, 1, 9999);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="budgetflow_users_' . date('Ymd_His') . '.csv"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        // BOM UTF-8 pour Excel.
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['ID', 'Nom', 'Email', 'Rôle', 'Statut', 'Budgets', 'Transactions', 'Inscrit le']);

        foreach ($users as $u) {
            fputcsv($out, [
                $u['id'],
                $u['name'],
                $u['email'],
                $u['role'],
                (bool) $u['is_active'] ? 'Actif' : 'En attente',
                $u['budget_count'],
                $u['transaction_count'],
                date('d/m/Y', strtotime((string) $u['created_at'])),
            ]);
        }

        fclose($out);
        exit;
    }

    /** Valide un compte en attente (is_active → true) et envoie un email. */
    public function validateUser(): void
    {
        Auth::requireRole('admin');

        if (!CSRF::validateToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->session->setFlash('danger', 'Session expirée, veuillez réessayer.');
            $this->redirect('/admin/users');
        }

        $userId = (int) ($_POST['user_id'] ?? 0);
        $user   = $this->userModel->findById($userId);

        if ($user === null) {
            $this->session->setFlash('danger', 'Utilisateur introuvable.');
            $this->redirect('/admin/users');
        }

        if ((bool) $user['is_active'] === true) {
            $this->session->setFlash('info', 'Ce compte est déjà actif.');
            $this->redirect('/admin/users');
        }

        $this->userModel->activate($userId);
        $this->sendEmail($user, 'account_validated', 'Votre compte BudgetFlow a été activé');

        $safeName = htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8');
        $this->session->setFlash('success', "Compte de {$safeName} activé. Email de confirmation envoyé.");
        $this->redirect('/admin/users');
    }

    /** Change le rôle d'un utilisateur (user ↔ admin). */
    public function changeRole(): void
    {
        Auth::requireRole('admin');

        if (!CSRF::validateToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->session->setFlash('danger', 'Session expirée, veuillez réessayer.');
            $this->redirect('/admin/users');
        }

        $adminUser = Auth::getUser();
        $userId    = (int) ($_POST['user_id'] ?? 0);
        $newRole   = (string) ($_POST['new_role'] ?? '');

        if ($userId === (int) ($adminUser['id'] ?? 0)) {
            $this->session->setFlash('danger', 'Vous ne pouvez pas modifier votre propre rôle.');
            $this->redirect('/admin/users');
        }

        if (!in_array($newRole, ['user', 'admin'], true)) {
            $this->session->setFlash('danger', 'Rôle invalide.');
            $this->redirect('/admin/users');
        }

        $user = $this->userModel->findById($userId);
        if ($user === null) {
            $this->session->setFlash('danger', 'Utilisateur introuvable.');
            $this->redirect('/admin/users');
        }

        $this->userModel->setRole($userId, $newRole);
        $this->sendEmail($user, 'role_changed', 'Modification de votre rôle BudgetFlow', ['newRole' => $newRole]);

        $safeName = htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8');
        $label    = $newRole === 'admin' ? 'Administrateur' : 'Utilisateur';
        $this->session->setFlash('success', "{$safeName} est maintenant {$label}.");
        $this->redirect('/admin/users');
    }

    /** Supprime un compte utilisateur avec email de notification si en attente. */
    public function deleteUser(): void
    {
        Auth::requireRole('admin');

        if (!CSRF::validateToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->session->setFlash('danger', 'Session expirée, veuillez réessayer.');
            $this->redirect('/admin/users');
        }

        $adminUser = Auth::getUser();
        $userId    = (int) ($_POST['user_id'] ?? 0);

        if ($userId === (int) ($adminUser['id'] ?? 0)) {
            $this->session->setFlash('danger', 'Vous ne pouvez pas supprimer votre propre compte.');
            $this->redirect('/admin/users');
        }

        $user = $this->userModel->findById($userId);
        if ($user === null) {
            $this->session->setFlash('danger', 'Utilisateur introuvable.');
            $this->redirect('/admin/users');
        }

        if ($user['role'] === 'admin') {
            $this->session->setFlash('danger', "Impossible de supprimer un compte administrateur.");
            $this->redirect('/admin/users');
        }

        // Notifier l'utilisateur si son compte était en attente (rejection).
        if (!(bool) $user['is_active']) {
            $this->sendEmail($user, 'account_rejected', 'Votre demande de compte BudgetFlow');
        }

        $this->userModel->deleteById($userId);

        $safeName = htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8');
        $this->session->setFlash('success', "Compte de {$safeName} supprimé.");
        $this->redirect('/admin/users');
    }

    /** Réinitialise le mot de passe d'un utilisateur et l'affiche une seule fois. */
    public function resetPassword(): void
    {
        Auth::requireRole('admin');

        if (!CSRF::validateToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->session->setFlash('danger', 'Session expirée.');
            $this->redirect('/admin/users');
        }

        $adminUser = Auth::getUser();
        $userId    = (int) ($_POST['user_id'] ?? 0);

        if ($userId === (int) ($adminUser['id'] ?? 0)) {
            $this->session->setFlash('danger', 'Utilisateur introuvable.');
            $this->redirect('/admin/users');
        }

        $user = $this->userModel->findById($userId);
        if ($user === null) {
            $this->session->setFlash('danger', 'Utilisateur introuvable.');
            $this->redirect('/admin/users');
        }

        // Génère un mot de passe temporaire lisible.
        $chars    = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $tempPass = '';
        for ($i = 0; $i < 10; $i++) {
            $tempPass .= $chars[random_int(0, strlen($chars) - 1)];
        }

        $this->userModel->update($userId, [
            'password' => password_hash($tempPass, PASSWORD_BCRYPT),
        ]);

        $safeName = htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8');
        $this->session->setFlash('success', "Mot de passe de {$safeName} réinitialisé → <strong>{$tempPass}</strong> (à communiquer à l'utilisateur)");
        $this->redirect('/admin/users');
    }

    /** Affiche le profil de l'administrateur connecté (même vue que l'utilisateur). */
    public function profile(): void
    {
        Auth::requireRole('admin');

        $userId = (int) (Auth::getUser()['id'] ?? 0);
        $user   = $this->userModel->findById($userId) ?? [];
        $tab    = in_array($_GET['tab'] ?? '', ['profile', 'security'], true)
                  ? ($_GET['tab'] ?? 'profile') : 'profile';

        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = :id");
        $stmt->execute(['id' => $userId]);
        $txCount = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE -amount END), 0)
             FROM transactions WHERE user_id = :id"
        );
        $stmt->execute(['id' => $userId]);
        $netBalance = (float) $stmt->fetchColumn();

        $rawPrefs = $user['preferences'] ?? '{}';
        $prefs    = json_decode((string) $rawPrefs, true) ?: [];

        $this->render('profile/index', [
            'pageTitle'    => 'Mon Profil',
            'user'         => $user,
            'tab'          => $tab,
            'txCount'      => $txCount,
            'netBalance'   => $netBalance,
            'prefs'        => $prefs,
            'profileBase'  => '/admin/profile',
            'flashSuccess' => $this->session->getFlash('success'),
            'flashDanger'  => $this->session->getFlash('danger'),
            'flashInfo'    => $this->session->getFlash('info'),
        ]);
    }

    /** Affiche le formulaire de composition d'email. */
    public function showSendEmail(): void
    {
        Auth::requireRole('admin');

        $allUsers = $this->userModel->findAllWithFilter('all', 1, 9999);

        $this->render('admin/send_email', [
            'pageTitle'    => 'Envoyer un email',
            'allUsers'     => $allUsers,
            'flashSuccess' => $this->session->getFlash('success'),
            'flashDanger'  => $this->session->getFlash('danger'),
            'flashInfo'    => $this->session->getFlash('info'),
        ]);
    }

    /** Envoie un email personnalisé à un ou plusieurs utilisateurs. */
    public function sendBulkEmail(): void
    {
        Auth::requireRole('admin');

        if (!CSRF::validateToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->session->setFlash('danger', 'Session expirée, veuillez réessayer.');
            $this->redirect('/admin/send-email');
        }

        $subject     = trim((string) ($_POST['subject']      ?? ''));
        $messageBody = trim((string) ($_POST['message_body'] ?? ''));
        $userIds     = array_filter(array_map('intval', (array) ($_POST['user_ids'] ?? [])));

        if ($subject === '' || $messageBody === '') {
            $this->session->setFlash('danger', 'Le sujet et le message sont obligatoires.');
            $this->redirect('/admin/send-email');
        }

        if (empty($userIds)) {
            $this->session->setFlash('danger', 'Veuillez sélectionner au moins un destinataire.');
            $this->redirect('/admin/send-email');
        }

        $loginUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/login';

        $mailer = new Mailer();
        $sent   = 0;
        $failed = 0;

        foreach ($userIds as $uid) {
            $user = $this->userModel->findById($uid);
            if ($user === null) {
                continue;
            }

            $to       = (string) ($user['email'] ?? '');
            $userName = (string) ($user['name']  ?? '');

            ob_start();
            require __DIR__ . '/../views/emails/admin_message.php';
            $body = (string) ob_get_clean();

            if ($mailer->send($to, $subject, $body)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        if ($failed === 0) {
            $this->session->setFlash('success', "Email envoyé avec succès à {$sent} destinataire(s).");
        } else {
            $this->session->setFlash('info', "Envoi terminé : {$sent} réussi(s), {$failed} échec(s). Vérifiez les logs PHP.");
        }

        $this->redirect('/admin/send-email');
    }

    /** Supervision de tous les budgets partagés. */
    public function budgets(): void
    {
        Auth::requireRole('admin');

        $budgets = $this->budgetModel->findAllShared();

        $this->render('admin/budgets', [
            'pageTitle'    => 'Budgets partagés',
            'budgets'      => $budgets,
            'flashSuccess' => $this->session->getFlash('success'),
            'flashDanger'  => $this->session->getFlash('danger'),
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers privés
    // -------------------------------------------------------------------------

    /**
     * Envoie un email transactionnel depuis un template.
     *
     * @param array<string, mixed> $user     Données utilisateur (email, name)
     * @param string               $template Nom du template (sans .php)
     * @param string               $subject  Sujet en clair
     * @param array<string, mixed> $extra    Variables supplémentaires pour le template
     */
    private function sendEmail(array $user, string $template, string $subject, array $extra = []): void
    {
        $to       = (string) ($user['email'] ?? '');
        $userName = (string) ($user['name']  ?? '');
        $loginUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/login';

        // Expose extra vars to the template scope.
        extract($extra, EXTR_SKIP);

        ob_start();
        require __DIR__ . '/../views/emails/' . $template . '.php';
        $body = (string) ob_get_clean();

        (new Mailer())->send($to, $subject, $body);
    }

    private function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require __DIR__ . '/../views/' . $view . '.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layouts/admin.php';
    }

    private function redirect(string $path): void
    {
        header('Location: ' . $path, true, 302);
        throw new RedirectException();
    }
}
