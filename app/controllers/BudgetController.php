<?php

declare(strict_types=1);

/**
 * Budget Controller
 * 
 * Handles CRUD operations for budgets including creation, listing,
 * editing, deletion, and collaboration features (invite/remove members).
 * 
 * Features:
 * - List budgets (personal and shared)
 * - Create/edit/delete budgets with ownership validation
 * - Invite members to shared budgets (email based)
 * - Remove members from shared budgets
 * - CSRF protection
 * - Full ownership validation on destructive actions
 */
class BudgetController
{
    private ?Budget $budgets = null;
    private ?Transaction $transactions = null;
    private Session $session;

    /**
     * Constructor - Initialize session handler.
     */
    public function __construct()
    {
        $this->session = new Session();
    }

    /**
     * Display the budgets listing page.
     * 
     * Shows personal budgets (owned by user) and shared budgets (where user is member).
     * Includes budget utilization statistics.
     */
    public function index(): void
    {
        Auth::requireRole('user');

        $user = Auth::getUser() ?? [];
        $userId = (int) ($user['id'] ?? 0);

        $budgets = $this->budgets()->findByUser($userId);

        $personalBudgets = [];
        $sharedBudgets = [];

        foreach ($budgets as $budget) {
            if ((int) ($budget['owner_id'] ?? 0) === $userId && ($budget['type'] ?? '') === 'personal') {
                $personalBudgets[] = $budget;
            } else {
                if (($budget['type'] ?? '') === 'shared') {
                    $budget['members'] = $this->budgets()->getMembers((int) ($budget['id'] ?? 0));
                }
                $sharedBudgets[] = $budget;
            }
        }

        $this->render('budgets/index', [
            'title' => 'Mes Budgets',
            'pageTitle' => 'Mes Budgets',
            'user' => $user,
            'personalBudgets' => $personalBudgets,
            'sharedBudgets' => $sharedBudgets,
            'flashSuccess' => $this->session->getFlash('success'),
            'flashWarning' => $this->session->getFlash('warning'),
            'flashDanger' => $this->session->getFlash('danger'),
        ]);
    }

    /**
     * Display the budget creation form.
     */
    public function showCreate(): void
    {
        Auth::requireRole('user');

        $user = Auth::getUser() ?? [];
        $formState = $this->consumeFormState('budget_form');

        $this->render('budgets/form', [
            'title' => 'Nouveau budget',
            'pageTitle' => 'Nouveau budget',
            'user' => $user,
            'mode' => 'create',
            'action' => '/budgets/create',
            'budget' => $formState['old'] ?? [],
            'errors' => $formState['errors'] ?? [],
            'flashDanger' => $this->session->getFlash('danger'),
        ]);
    }

    /**
     * Process budget creation via POST.
     * 
     * Validates:
     * - CSRF token
     * - Required fields (name, type, period)
     * - Valid dates
     */
    public function create(): void
    {
        Auth::requireRole('user');

        $user = Auth::getUser() ?? [];
        $userId = (int) ($user['id'] ?? 0);
        $input = $_POST;

        if (!CSRF::validateToken((string) ($input['csrf_token'] ?? ''))) {
            $this->storeFormState('budget_form', $input, ['form' => 'La session du formulaire a expiré. Veuillez réessayer.']);
            $this->session->setFlash('danger', 'Le formulaire a expiré.');
            $this->redirect('/budgets/create');
        }

        list($payload, $errors) = $this->validateBudgetInput($input);
        if ($errors !== []) {
            $this->storeFormState('budget_form', $input, $errors);
            $this->session->setFlash('danger', 'Corrigez les erreurs avant d\'enregistrer le budget.');
            $this->redirect('/budgets/create');
        }

        $payload['owner_id'] = $userId;

        $budgetId = $this->budgets()->create($payload);
        $this->session->setFlash('success', 'Le budget a été créé avec succès.');
        $this->redirect('/budgets');
    }

    /**
     * Display budget details page.
     * 
     * Shows budget info, statistics, members (for shared), and transactions.
     */
    public function show(): void
    {
        Auth::requireRole('user');

        $user = Auth::getUser() ?? [];
        $userId = (int) ($user['id'] ?? 0);
        $budgetId = $this->normalizeId($_GET['id'] ?? null);

        if ($budgetId === null) {
            $this->session->setFlash('danger', 'Budget introuvable.');
            $this->redirect('/budgets');
        }

        if (!$this->budgets()->belongsToUser($budgetId, $userId)) {
            http_response_code(403);
            echo '403 - Accès interdit';
            return;
        }

        $budget = $this->budgets()->findById($budgetId);
        if ($budget === null) {
            http_response_code(404);
            echo '404 - Budget introuvable';
            return;
        }

        $isOwner = $this->budgets()->isOwner($budgetId, $userId);

        $spent = $this->budgets()->getTotalSpent($budgetId);
        $income = $this->budgets()->getTotalIncome($budgetId);
        $balance = $income - $spent;

        $budget['spent'] = $spent;
        $budget['income'] = $income;
        $budget['balance'] = $balance;

        $percent = $budget['amount_limit'] > 0
            ? ($spent / (float) $budget['amount_limit']) * 100
            : 0;

        $status = match(true) {
            $percent >= 100 => 'danger',
            $percent >= 80 => 'warning',
            default => 'ok',
        };

        $overAmount = $percent >= 100 ? $spent - (float) $budget['amount_limit'] : 0;

        $typeFilter = $_GET['type'] ?? null;
        if ($typeFilter !== null && !in_array($typeFilter, ['income', 'expense'], true)) {
            $typeFilter = null;
        }

        $transactions = $this->budgets()->getTransactions($budgetId, $typeFilter);
        $categoryBreakdown = $this->budgets()->getCategoryBreakdown($budgetId);

        $members = [];
        if (($budget['type'] ?? '') === 'shared') {
            $members = $this->budgets()->getMembers($budgetId);
        }

        $this->render('budgets/show', [
            'title' => htmlspecialchars((string) ($budget['name'] ?? 'Budget')),
            'pageTitle' => htmlspecialchars((string) ($budget['name'] ?? 'Budget')),
            'user' => $user,
            'budget' => $budget,
            'isOwner' => $isOwner,
            'members' => $members,
            'transactions' => $transactions,
            'categoryBreakdown' => $categoryBreakdown,
            'percent' => $percent,
            'status' => $status,
            'overAmount' => $overAmount,
            'typeFilter' => $typeFilter,
            'flashSuccess' => $this->session->getFlash('success'),
            'flashWarning' => $this->session->getFlash('warning'),
            'flashDanger' => $this->session->getFlash('danger'),
        ]);
    }

    /**
     * Display the budget edit form.
     */
    public function showEdit(): void
    {
        Auth::requireRole('user');

        $user = Auth::getUser() ?? [];
        $userId = (int) ($user['id'] ?? 0);
        $budgetId = $this->normalizeId($_GET['id'] ?? null);

        if ($budgetId === null) {
            $this->session->setFlash('danger', 'Budget introuvable.');
            $this->redirect('/budgets');
        }

        $budget = $this->budgets()->findById($budgetId);
        if ($budget === null) {
            http_response_code(404);
            echo '404 - Budget introuvable';
            return;
        }

        if (!$this->budgets()->isOwner($budgetId, $userId)) {
            http_response_code(403);
            echo '403 - Accès interdit';
            return;
        }

        $formState = $this->consumeFormState('budget_form');

        $this->render('budgets/form', [
            'title' => 'Modifier le budget',
            'pageTitle' => 'Modifier le budget',
            'user' => $user,
            'mode' => 'edit',
            'action' => '/budgets/edit?id=' . $budgetId,
            'budget' => $formState['old'] ?? $budget,
            'errors' => $formState['errors'] ?? [],
            'flashDanger' => $this->session->getFlash('danger'),
        ]);
    }

    /**
     * Process budget update via POST.
     */
    public function edit(): void
    {
        Auth::requireRole('user');

        $user = Auth::getUser() ?? [];
        $userId = (int) ($user['id'] ?? 0);
        $input = $_POST;
        $budgetId = $this->normalizeId($input['id'] ?? ($_GET['id'] ?? null));

        if ($budgetId === null) {
            $this->session->setFlash('danger', 'Budget introuvable.');
            $this->redirect('/budgets');
        }

        $budget = $this->budgets()->findById($budgetId);
        if ($budget === null) {
            http_response_code(404);
            echo '404 - Budget introuvable';
            return;
        }

        if (!$this->budgets()->isOwner($budgetId, $userId)) {
            http_response_code(403);
            echo '403 - Accès interdit';
            return;
        }

        if (!CSRF::validateToken((string) ($input['csrf_token'] ?? ''))) {
            $this->storeFormState('budget_form', $input, ['form' => 'La session du formulaire a expiré. Veuillez réessayer.']);
            $this->session->setFlash('danger', 'Le formulaire a expiré.');
            $this->redirect('/budgets/edit?id=' . $budgetId);
        }

        list($payload, $errors) = $this->validateBudgetInput($input);
        if ($errors !== []) {
            $this->storeFormState('budget_form', $input, $errors);
            $this->session->setFlash('danger', 'Corrigez les erreurs avant d\'enregistrer le budget.');
            $this->redirect('/budgets/edit?id=' . $budgetId);
        }

        $this->budgets()->update($budgetId, $payload);
        $this->session->setFlash('success', 'Le budget a été modifié avec succès.');
        $this->redirect('/budgets/show?id=' . $budgetId);
    }

    /**
     * Process budget deletion via POST.
     */
    public function delete(): void
    {
        Auth::requireRole('user');

        $user = Auth::getUser() ?? [];
        $userId = (int) ($user['id'] ?? 0);
        $input = $_POST;
        $budgetId = $this->normalizeId($input['id'] ?? null);

        if (!CSRF::validateToken((string) ($input['csrf_token'] ?? ''))) {
            $this->session->setFlash('danger', 'La session du formulaire a expiré.');
            $this->redirect('/budgets');
        }

        if ($budgetId === null) {
            $this->session->setFlash('danger', 'Budget introuvable.');
            $this->redirect('/budgets');
        }

        $budget = $this->budgets()->findById($budgetId);
        if ($budget === null) {
            http_response_code(404);
            echo '404 - Budget introuvable';
            return;
        }

        if (!$this->budgets()->isOwner($budgetId, $userId)) {
            http_response_code(403);
            echo '403 - Accès interdit';
            return;
        }

        $this->budgets()->delete($budgetId);
        $this->session->setFlash('success', 'Le budget a été supprimé.');
        $this->redirect('/budgets');
    }

    /**
     * Invite a member to a shared budget via POST.
     */
    public function invite(): void
    {
        Auth::requireRole('user');

        $user = Auth::getUser() ?? [];
        $userId = (int) ($user['id'] ?? 0);
        $input = $_POST;
        $budgetId = $this->normalizeId($input['budget_id'] ?? null);

        if (!CSRF::validateToken((string) ($input['csrf_token'] ?? ''))) {
            $this->session->setFlash('danger', 'La session du formulaire a expiré.');
            $this->redirect('/budgets');
        }

        if ($budgetId === null) {
            $this->session->setFlash('danger', 'Budget introuvable.');
            $this->redirect('/budgets');
        }

        $budget = $this->budgets()->findById($budgetId);
        if ($budget === null) {
            http_response_code(404);
            echo '404 - Budget introuvable';
            return;
        }

        if (!$this->budgets()->isOwner($budgetId, $userId)) {
            http_response_code(403);
            echo '403 - Accès interdit';
            return;
        }

        if (($budget['type'] ?? '') !== 'shared') {
            $this->session->setFlash('danger', 'Vous ne pouvez inviter des membres que sur un budget partagé.');
            $this->redirect('/budgets/show?id=' . $budgetId);
        }

        $email = trim(strtolower((string) ($input['email'] ?? '')));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->setFlash('warning', 'Veuillez entrer une adresse email valide.');
            $this->redirect('/budgets/show?id=' . $budgetId);
        }

        $invitee = $this->budgets()->findUserByEmail($email);

        if ($invitee === null) {
            $this->session->setFlash('warning', 'Aucun compte trouvé avec cet email. L\'utilisateur doit d\'abord créer un compte BudgetFlow.');
            $this->redirect('/budgets/show?id=' . $budgetId);
        }

        if (empty($invitee['is_active'])) {
            $this->session->setFlash('warning', 'Cet utilisateur n\'a pas encore activé son compte.');
            $this->redirect('/budgets/show?id=' . $budgetId);
        }

        if ((int) ($invitee['id'] ?? 0) === $userId) {
            $this->session->setFlash('warning', 'Vous ne pouvez pas vous inviter vous-même.');
            $this->redirect('/budgets/show?id=' . $budgetId);
        }

        $existingMembers = $this->budgets()->getMembers($budgetId);
        foreach ($existingMembers as $member) {
            if ((int) ($member['id'] ?? 0) === (int) ($invitee['id'] ?? 0)) {
                $this->session->setFlash('warning', 'Cet utilisateur est déjà membre de ce budget.');
                $this->redirect('/budgets/show?id=' . $budgetId);
            }
        }

        $added = $this->budgets()->addMember($budgetId, (int) $invitee['id']);

        if (!$added) {
            $this->session->setFlash('danger', 'Impossible d\'ajouter ce membre. Il est peut-être déjà membre.');
            $this->redirect('/budgets/show?id=' . $budgetId);
        }

        $this->session->setFlash('success', 'Membre ajouté avec succès.');
        $this->redirect('/budgets/show?id=' . $budgetId);
    }

    /**
     * Remove a member from a shared budget via POST.
     */
    public function removeMember(): void
    {
        Auth::requireRole('user');

        $user = Auth::getUser() ?? [];
        $userId = (int) ($user['id'] ?? 0);
        $input = $_POST;
        $budgetId = $this->normalizeId($input['budget_id'] ?? null);
        $memberId = $this->normalizeId($input['user_id'] ?? null);

        if (!CSRF::validateToken((string) ($input['csrf_token'] ?? ''))) {
            $this->session->setFlash('danger', 'La session du formulaire a expiré.');
            $this->redirect('/budgets');
        }

        if ($budgetId === null || $memberId === null) {
            $this->session->setFlash('danger', 'Paramètres invalides.');
            $this->redirect('/budgets');
        }

        $budget = $this->budgets()->findById($budgetId);
        if ($budget === null) {
            http_response_code(404);
            echo '404 - Budget introuvable';
            return;
        }

        if (!$this->budgets()->isOwner($budgetId, $userId)) {
            http_response_code(403);
            echo '403 - Accès interdit';
            return;
        }

        if ((int) ($budget['owner_id'] ?? 0) === $memberId) {
            $this->session->setFlash('danger', 'Vous ne pouvez pas retirer le propriétaire du budget.');
            $this->redirect('/budgets/show?id=' . $budgetId);
        }

        $this->budgets()->removeMember($budgetId, $memberId);
        $this->session->setFlash('success', 'Membre retiré du budget.');
        $this->redirect('/budgets/show?id=' . $budgetId);
    }

    /**
     * Validate and sanitize budget input data.
     *
     * @param array<string, mixed> $input Raw POST data
     * @return array{0: array<string, mixed>, 1: array<string, string>} [payload, errors]
     */
    private function validateBudgetInput(array $input): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        $type = strtolower(trim((string) ($input['type'] ?? 'personal')));
        $period = strtolower(trim((string) ($input['period'] ?? 'monthly')));
        $amountLimit = trim((string) ($input['amount_limit'] ?? ''));
        $startDate = trim((string) ($input['start_date'] ?? ''));

        if ($name === '' || strlen($name) > 100) {
            $errors['name'] = 'Le nom du budget est requis (max 100 caractères).';
        }

        if (!in_array($type, ['personal', 'shared'], true)) {
            $errors['type'] = 'Veuillez sélectionner un type de budget valide.';
        }

        if (!in_array($period, ['weekly', 'monthly', 'custom'], true)) {
            $errors['period'] = 'Veuillez sélectionner une période valide.';
        }

        $calculatedStartDate = match ($period) {
            'weekly' => date('Y-m-d', strtotime('monday this week')),
            'monthly' => date('Y-m-01'),
            default => null,
        };

        if ($startDate !== '') {
            if (!$this->isValidDate($startDate)) {
                $errors['start_date'] = 'Veuillez sélectionner une date valide.';
            }
        }

        if ($amountLimit !== '') {
            if (!is_numeric($amountLimit) || (float) $amountLimit < 0) {
                $errors['amount_limit'] = 'Le montant doit être un nombre positif.';
            }
        }

        if ($errors !== []) {
            return [[], $errors];
        }

        $payload = [
            'name' => $name,
            'type' => $type,
            'period' => $period,
            'amount_limit' => $amountLimit !== '' ? number_format((float) $amountLimit, 2, '.', '') : null,
            'start_date' => $startDate !== '' ? $startDate : ($calculatedStartDate ?? date('Y-m-d')),
        ];

        return [$payload, []];
    }

    /**
     * Validate a date string.
     *
     * @param string $date Date in YYYY-MM-DD format
     * @return bool True if valid
     */
    private function isValidDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            return false;
        }

        $limit = (new DateTimeImmutable('today'))->modify('+1 year');

        return $parsed <= $limit;
    }

    /**
     * Normalize an ID value to a positive integer or null.
     *
     * @param mixed $value The raw value
     * @return int|null Positive integer or null
     */
    private function normalizeId(mixed $value): ?int
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        $integer = (int) $value;

        return $integer > 0 ? $integer : null;
    }

    /**
     * Store form state in session for sticky forms.
     *
     * @param string $key Session key
     * @param array<string, mixed> $old Original input values
     * @param array<string, string> $errors Validation errors
     */
    private function storeFormState(string $key, array $old, array $errors): void
    {
        $_SESSION[$key] = [
            'old' => $this->sanitizeArray($old),
            'errors' => $errors,
        ];
    }

    /**
     * Retrieve and consume form state from session.
     *
     * @param string $key Session key
     * @return array<string, mixed> Form state or empty array
     */
    private function consumeFormState(string $key): array
    {
        $state = $_SESSION[$key] ?? [];
        unset($_SESSION[$key]);

        if (!is_array($state)) {
            return [];
        }

        return $state;
    }

    /**
     * Trim all string values in an array.
     *
     * @param array<string, mixed> $values Input array
     * @return array<string, mixed> Sanitized array
     */
    private function sanitizeArray(array $values): array
    {
        $sanitized = [];
        foreach ($values as $key => $value) {
            $sanitized[$key] = is_string($value) ? trim($value) : $value;
        }

        return $sanitized;
    }

    /**
     * Render a view within the authenticated layout.
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

        require __DIR__ . '/../views/layouts/app.php';
    }

    /**
     * Perform an HTTP 302 redirect.
     *
     * @param string $path Target URL path
     */
    private function redirect(string $path): void
    {
        header('Location: ' . $path, true, 302);
        throw new RedirectException();
    }

    /**
     * Lazy-load the Budget model instance.
     *
     * @return Budget
     */
    private function budgets(): Budget
    {
        if ($this->budgets === null) {
            $this->budgets = new Budget();
        }

        return $this->budgets;
    }

    /**
     * Lazy-load the Transaction model instance.
     *
     * @return Transaction
     */
    private function transactions(): Transaction
    {
        if ($this->transactions === null) {
            $this->transactions = new Transaction();
        }

        return $this->transactions;
    }
}