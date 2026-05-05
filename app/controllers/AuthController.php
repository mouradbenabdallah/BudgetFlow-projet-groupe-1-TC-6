<?php

class AuthController
{
    private ?User $users = null;
    private Session $session;

    public function __construct()
    {
        $this->session = new Session();
    }

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

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            CSRF::validateToken((string) ($_POST['csrf_token'] ?? ''));
        }

        $this->session->destroy();
        $this->redirect('/login');
    }

    private function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require __DIR__ . '/../views/' . $view . '.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layouts/guest.php';
    }

    private function redirectIfAuthenticated(): void
    {
        if (!Auth::isLoggedIn()) {
            return;
        }

        $user = Auth::getUser();
        $this->redirect(($user['role'] ?? 'user') === 'admin' ? '/admin' : '/dashboard');
    }

    private function redirect(string $path): void
    {
        header('Location: ' . $path, true, 302);
        throw new RedirectException();
    }

    private function normalizeEmail(mixed $email): string
    {
        return strtolower(trim((string) $email));
    }

    private function isActive(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 't', 'true'], true);
    }

    private function users(): User
    {
        if ($this->users === null) {
            $this->users = new User();
        }

        return $this->users;
    }
}
