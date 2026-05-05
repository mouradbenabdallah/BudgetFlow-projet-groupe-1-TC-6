<?php

declare(strict_types=1);

/**
 * Auth Controller
 *
 * Handles user authentication flows: login, registration, and logout.
 * New accounts require admin activation before they can log in.
 */
class AuthController
{
    private ?User $users = null;
    private Session $session;

    public function __construct()
    {
        $this->session = new Session();
    }

    /**
     * Display the login form.
     */
    public function showLogin(): void
    {
        $this->redirectIfAuthenticated();

        $this->render('auth/login', [
            'title' => 'Connexion',
            'errors' => [],
            'old' => [],
            'flashInfo' => $this->session->getFlash('info'),
        ]);
    }

    /**
     * Process login submission. Validates credentials and creates session.
     */
    public function login(): void
    {
        $this->redirectIfAuthenticated();

        // Normalisation et validation serveur : on ne fait jamais confiance au HTML seul.
        $email = $this->normalizeEmail($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $errors = [];

        if (!CSRF::validateToken((string) ($_POST['csrf_token'] ?? ''))) {
            $errors['form'] = 'La session du formulaire a expiré. Veuillez réessayer.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Veuillez saisir une adresse email valide.';
        }

        if ($password === '') {
            $errors['password'] = 'Veuillez saisir votre mot de passe.';
        }

        if ($errors !== []) {
            $this->render('auth/login', [
                'title' => 'Connexion',
                'errors' => $errors,
                'old' => ['email' => $email],
                'flashInfo' => null,
            ]);
            return;
        }

        $user = $this->users()->findByEmail($email);

        // Message volontairement générique pour ne pas révéler si l'email existe.
        if ($user === null || !password_verify($password, $user['password'])) {
            $this->render('auth/login', [
                'title' => 'Connexion',
                'errors' => ['form' => 'Email ou mot de passe incorrect.'],
                'old' => ['email' => $email],
                'flashInfo' => null,
            ]);
            return;
        }

        // Un compte créé par inscription reste bloqué tant qu'un admin ne l'active pas.
        if (!$this->isActive($user['is_active'])) {
            $this->render('auth/login', [
                'title' => 'Connexion',
                'errors' => ['form' => 'Votre compte est en attente de validation.'],
                'old' => ['email' => $email],
                'flashInfo' => null,
            ]);
            return;
        }

        // Après login réussi, on régénère l'ID pour limiter la fixation de session.
        session_regenerate_id(true);
        $this->session->set('user_id', (int) $user['id']);
        $this->session->set('name', (string) $user['name']);
        $this->session->set('email', (string) $user['email']);
        $this->session->set('role', (string) $user['role']);

        $this->redirect($user['role'] === 'admin' ? '/admin' : '/dashboard');
    }

    /**
     * Display the registration form.
     */
    public function showRegister(): void
    {
        $this->redirectIfAuthenticated();

        $this->render('auth/register', [
            'title' => 'Inscription',
            'errors' => [],
            'old' => [],
            'flashInfo' => $this->session->getFlash('info'),
        ]);
    }

    /**
     * Process registration submission. Creates an inactive user awaiting admin approval.
     */
    public function register(): void
    {
        $this->redirectIfAuthenticated();

        // Les anciennes valeurs seront renvoyées à la vue sauf les mots de passe.
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = $this->normalizeEmail($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');
        $errors = [];

        if (!CSRF::validateToken((string) ($_POST['csrf_token'] ?? ''))) {
            $errors['form'] = 'La session du formulaire a expiré. Veuillez réessayer.';
        }

        if (strlen($name) < 2) {
            $errors['name'] = 'Le nom complet doit contenir au moins 2 caractères.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Veuillez saisir une adresse email valide.';
        } elseif ($this->users()->findByEmail($email) !== null) {
            $errors['email'] = 'Cette adresse email est déjà utilisée.';
        }

        if (strlen($password) < 8) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }

        if ($passwordConfirmation === '' || $passwordConfirmation !== $password) {
            $errors['password_confirmation'] = 'La confirmation du mot de passe ne correspond pas.';
        }

        if ($errors !== []) {
            $this->render('auth/register', [
                'title' => 'Inscription',
                'errors' => $errors,
                'old' => ['name' => $name, 'email' => $email],
                'flashInfo' => null,
            ]);
            return;
        }

        // Le compte est créé inactif : l'admin le validera dans une prochaine fonction.
        $this->users()->create([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role' => 'user',
            'is_active' => false,
        ]);

        // Hook futur : envoyer un email de confirmation après validation admin.
        $this->session->setFlash(
            'info',
            'Votre compte est en attente de validation par un administrateur. Vous recevrez un email de confirmation.'
        );

        $this->redirect('/register');
    }

    /**
     * Destroy the user session and redirect to login.
     */
    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRF::validateToken((string) ($_POST['csrf_token'] ?? ''));
        }

        $this->session->destroy();
        $this->redirect('/login');
    }

    /**
     * Render a view within the guest layout.
     *
     * @param string $view View file path (relative to app/views/)
     * @param array<string, mixed> $data Variables to extract into the view
     */
    private function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require __DIR__ . '/../views/' . $view . '.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layouts/guest.php';
    }

    /**
     * Redirect authenticated users to their appropriate dashboard.
     */
    private function redirectIfAuthenticated(): void
    {
        if (!Auth::isLoggedIn()) {
            return;
        }

        $user = Auth::getUser();
        $this->redirect(($user['role'] ?? 'user') === 'admin' ? '/admin' : '/dashboard');
    }

    /**
     * Perform an HTTP 302 redirect. Throws RedirectException to halt execution.
     *
     * @param string $path Target URL path
     */
    private function redirect(string $path): void
    {
        header('Location: ' . $path, true, 302);
        throw new RedirectException();
    }

    /**
     * Normalize and trim an email value.
     *
     * @param mixed $email Raw email input
     * @return string Lowercase trimmed email
     */
    private function normalizeEmail(mixed $email): string
    {
        return strtolower(trim((string) $email));
    }

    /**
     * Check if a value represents an active/truthy state.
     *
     * @param mixed $value The value to check
     * @return bool True if active
     */
    private function isActive(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 't', 'true'], true);
    }

    /**
     * Lazy-load the User model instance.
     *
     * @return User
     */
    private function users(): User
    {
        if ($this->users === null) {
            $this->users = new User();
        }

        return $this->users;
    }
}
