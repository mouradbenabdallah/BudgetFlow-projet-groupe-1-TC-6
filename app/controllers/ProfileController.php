<?php

declare(strict_types=1);

/**
 * Profile Controller
 *
 * Handles profile and account-security actions for both regular users
 * and admins. The same view (profile/index.php) serves both roles;
 * $profileBase ('/profile' or '/admin/profile') keeps URLs role-aware.
 *
 * POST forms include a hidden _back field to indicate where to redirect
 * after saving. Only paths in ALLOWED_BACK are accepted (open-redirect guard).
 *
 * Routes (public/index.php):
 *   GET  /profile                  → index()
 *   POST /profile/update-info      → updateInfo()
 *   POST /profile/update-password  → updatePassword()
 *   POST /profile/request-deletion → requestDeletion()
 */
class ProfileController
{
    private Session $session;
    private User    $userModel;

    /** Chemins de retour autorisés après une action POST (anti-open-redirect). */
    private const ALLOWED_BACK = [
        '/profile',
        '/profile?tab=profile',
        '/profile?tab=security',
        '/admin/profile',
        '/admin/profile?tab=profile',
        '/admin/profile?tab=security',
    ];

    public function __construct()
    {
        $this->session   = new Session();
        $this->userModel = new User();
    }

    /** Affiche la page profil avec les stats de l'utilisateur connecté. */
    public function index(): void
    {
        Auth::requireRole('user');

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
            'pageTitle'    => 'Profil',
            'user'         => $user,
            'tab'          => $tab,
            'txCount'      => $txCount,
            'netBalance'   => $netBalance,
            'prefs'        => $prefs,
            'profileBase'  => '/profile',
            'flashSuccess' => $this->session->getFlash('success'),
            'flashDanger'  => $this->session->getFlash('danger'),
            'flashInfo'    => $this->session->getFlash('info'),
        ]);
    }

    /** Met à jour le nom, l'email et le téléphone. */
    public function updateInfo(): void
    {
        Auth::requireRole('user');

        $back = $this->safeBack('/profile');

        if (!CSRF::validateToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->session->setFlash('danger', 'Session expirée, veuillez réessayer.');
            $this->redirect($back);
        }

        $userId = (int) (Auth::getUser()['id'] ?? 0);
        $name   = trim((string) ($_POST['name']  ?? ''));
        $email  = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phone  = trim((string) ($_POST['phone'] ?? ''));

        if (strlen($name) < 2) {
            $this->session->setFlash('danger', 'Le nom doit contenir au moins 2 caractères.');
            $this->redirect($back);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->setFlash('danger', 'Adresse email invalide.');
            $this->redirect($back);
        }

        $existing = $this->userModel->findByEmail($email);
        if ($existing !== null && (int) $existing['id'] !== $userId) {
            $this->session->setFlash('danger', 'Cette adresse email est déjà utilisée par un autre compte.');
            $this->redirect($back);
        }

        $this->userModel->updateProfile($userId, $name, $email, $phone !== '' ? $phone : null);

        // Synchronise la session pour que le nom/email en topbar se mette à jour.
        $_SESSION['name']  = $name;
        $_SESSION['email'] = $email;

        $this->session->setFlash('success', 'Profil mis à jour avec succès.');
        $this->redirect($back);
    }

    /** Change le mot de passe après vérification de l'actuel. */
    public function updatePassword(): void
    {
        Auth::requireRole('user');

        $back = $this->safeBack('/profile?tab=security');

        if (!CSRF::validateToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->session->setFlash('danger', 'Session expirée, veuillez réessayer.');
            $this->redirect($back);
        }

        $userId  = (int) (Auth::getUser()['id'] ?? 0);
        $current = (string) ($_POST['current_password'] ?? '');
        $new     = (string) ($_POST['new_password']     ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if ($current === '' || $new === '' || $confirm === '') {
            $this->session->setFlash('danger', 'Tous les champs sont obligatoires.');
            $this->redirect($back);
        }

        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $row  = $stmt->fetch();

        if (!$row || !password_verify($current, (string) $row['password'])) {
            $this->session->setFlash('danger', 'Le mot de passe actuel est incorrect.');
            $this->redirect($back);
        }

        if (strlen($new) < 8) {
            $this->session->setFlash('danger', 'Le nouveau mot de passe doit contenir au moins 8 caractères.');
            $this->redirect($back);
        }

        if ($new !== $confirm) {
            $this->session->setFlash('danger', 'Les mots de passe ne correspondent pas.');
            $this->redirect($back);
        }

        $this->userModel->update($userId, ['password' => password_hash($new, PASSWORD_BCRYPT)]);

        $this->session->setFlash('success', 'Mot de passe modifié avec succès.');
        $this->redirect($back);
    }

    /** Sends a deletion request to all admins and notifies the user. */
    public function requestDeletion(): void
    {
        Auth::requireRole('user');

        $back = $this->safeBack('/profile?tab=security');

        if (!CSRF::validateToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->session->setFlash('danger', 'Session expirée, veuillez réessayer.');
            $this->redirect($back);
        }

        $userId   = (int) (Auth::getUser()['id'] ?? 0);
        $fullUser = $this->userModel->findById($userId) ?? Auth::getUser() ?? [];

        // Envoie la demande à tous les administrateurs actifs.
        Mailer::sendDeletionRequestToAdmins($fullUser);

        $this->session->setFlash('info', 'Votre demande de suppression a été transmise à l\'administrateur.');
        $this->redirect($back);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Return the POST _back value only if it is in ALLOWED_BACK (open-redirect guard).
     *
     * @param string $default Fallback path when _back is absent or disallowed
     * @return string Safe redirect path
     */
    private function safeBack(string $default): string
    {
        $back = (string) ($_POST['_back'] ?? '');
        return in_array($back, self::ALLOWED_BACK, true) ? $back : $default;
    }

    /**
     * Render a view inside the authenticated app layout.
     *
     * @param string               $view Relative path under app/views/ (no .php)
     * @param array<string, mixed> $data Variables to extract into the view scope
     */
    private function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require __DIR__ . '/../views/' . $view . '.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layouts/app.php';
    }

    /**
     * Send an HTTP 302 redirect and halt execution via RedirectException.
     *
     * @param string $path Target URL path
     */
    private function redirect(string $path): void
    {
        header('Location: ' . $path, true, 302);
        throw new RedirectException();
    }
}
