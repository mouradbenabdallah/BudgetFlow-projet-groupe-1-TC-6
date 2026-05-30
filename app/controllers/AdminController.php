<?php

declare(strict_types=1);

/**
 * Contrôleur Administration — stub pour le prompt 5.
 * Gère la supervision des utilisateurs et des budgets partagés.
 */
class AdminController
{
    private Session $session;

    public function __construct()
    {
        $this->session = new Session();
    }

    /** Tableau de bord administrateur. */
    public function index(): void
    {
        Auth::requireRole('admin');
        $this->render('admin/index', [
            'title'     => 'Administration',
            'pageTitle' => 'Tableau de bord admin',
        ]);
    }

    /** Liste des utilisateurs. */
    public function users(): void
    {
        Auth::requireRole('admin');
        $this->render('admin/users', [
            'title'        => 'Gestion des utilisateurs',
            'pageTitle'    => 'Utilisateurs',
            'flashSuccess' => $this->session->getFlash('success'),
            'flashDanger'  => $this->session->getFlash('danger'),
            'flashInfo'    => $this->session->getFlash('info'),
        ]);
    }

    /** Valide un compte utilisateur en attente. */
    public function validateUser(): void
    {
        Auth::requireRole('admin');

        if (!CSRF::validateToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->session->setFlash('danger', 'La session du formulaire a expiré.');
            $this->redirect('/admin/users');
        }

        // À implémenter dans le prompt 5.
        $this->session->setFlash('info', 'La validation de compte sera disponible prochainement.');
        $this->redirect('/admin/users');
    }

    /** Modifie le rôle d'un utilisateur. */
    public function changeRole(): void
    {
        Auth::requireRole('admin');

        if (!CSRF::validateToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->session->setFlash('danger', 'La session du formulaire a expiré.');
            $this->redirect('/admin/users');
        }

        // À implémenter dans le prompt 5.
        $this->session->setFlash('info', 'La gestion des rôles sera disponible prochainement.');
        $this->redirect('/admin/users');
    }

    /** Supprime un compte utilisateur. */
    public function deleteUser(): void
    {
        Auth::requireRole('admin');

        if (!CSRF::validateToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->session->setFlash('danger', 'La session du formulaire a expiré.');
            $this->redirect('/admin/users');
        }

        // À implémenter dans le prompt 5.
        $this->session->setFlash('info', 'La suppression de compte sera disponible prochainement.');
        $this->redirect('/admin/users');
    }

    /** Supervision des budgets partagés. */
    public function budgets(): void
    {
        Auth::requireRole('admin');
        $this->render('admin/budgets', [
            'title'     => 'Supervision des budgets',
            'pageTitle' => 'Budgets partagés',
        ]);
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
