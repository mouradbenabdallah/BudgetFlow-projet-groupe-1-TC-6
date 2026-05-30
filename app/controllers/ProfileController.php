<?php

declare(strict_types=1);

/**
 * Contrôleur Profil — stub pour le prompt 6.
 * Gère les actions sur le compte de l'utilisateur connecté.
 */
class ProfileController
{
    private Session $session;

    public function __construct()
    {
        $this->session = new Session();
    }

    /** Affiche la page profil de l'utilisateur. */
    public function index(): void
    {
        Auth::requireRole('user');
        $user = Auth::getUser() ?? [];
        $this->render('profile/index', [
            'title'        => 'Mon profil',
            'pageTitle'    => 'Mon profil',
            'user'         => $user,
            'flashSuccess' => $this->session->getFlash('success'),
            'flashDanger'  => $this->session->getFlash('danger'),
            'flashInfo'    => $this->session->getFlash('info'),
        ]);
    }

    /** Met à jour les informations générales (nom, email). */
    public function updateInfo(): void
    {
        Auth::requireRole('user');

        if (!CSRF::validateToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->session->setFlash('danger', 'La session du formulaire a expiré.');
            $this->redirect('/profile');
        }

        // À implémenter dans le prompt 6.
        $this->session->setFlash('info', 'La mise à jour du profil sera disponible prochainement.');
        $this->redirect('/profile');
    }

    /** Met à jour le mot de passe. */
    public function updatePassword(): void
    {
        Auth::requireRole('user');

        if (!CSRF::validateToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->session->setFlash('danger', 'La session du formulaire a expiré.');
            $this->redirect('/profile');
        }

        // À implémenter dans le prompt 6.
        $this->session->setFlash('info', 'Le changement de mot de passe sera disponible prochainement.');
        $this->redirect('/profile');
    }

    /** Envoie une demande de suppression de compte à l'administrateur. */
    public function requestDeletion(): void
    {
        Auth::requireRole('user');

        if (!CSRF::validateToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->session->setFlash('danger', 'La session du formulaire a expiré.');
            $this->redirect('/profile');
        }

        // À implémenter dans le prompt 6.
        $this->session->setFlash('info', 'La demande de suppression sera disponible prochainement.');
        $this->redirect('/profile');
    }

    private function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require __DIR__ . '/../views/' . $view . '.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layouts/app.php';
    }

    private function redirect(string $path): void
    {
        header('Location: ' . $path, true, 302);
        throw new RedirectException();
    }
}
