<?php

declare(strict_types=1);

/**
 * Transaction Controller
 * 
 * Handles CRUD operations for transactions including listing,
 * creation, editing, and deletion with full ownership validation.
 * 
 * Features:
 * - List transactions with filters (type, category, budget, month)
 * - Create/edit transactions with validation
 * - CSRF protection
 * - Ownership validation (user can only edit/delete their own transactions)
 * - Form state persistence on validation errors
 */
class TransactionController
{
    private const PER_PAGE = 20;

    private ?Transaction $transactions = null;
    private ?Budget $budgets = null;
    private ?Category $categories = null;
    private Session $session;

    /**
     * Constructor - Initialize session handler.
     */
    public function __construct()
    {
        $this->session = new Session();
    }

    /**
     * Display the transactions listing with filters and pagination.
     * 
     * Supports filtering by:
     * - Type (income/expense)
     * - Category
     * - Budget
     * - Month
     * 
     * Pagination is handled automatically.
     */
    public function index(): void
    {
        Auth::requireRole('user');

        $user = Auth::getUser() ?? [];
        $userId = (int) ($user['id'] ?? 0);

        $filters = $this->readFiltersFromQuery();
        $filters['limit'] = self::PER_PAGE;

        $total = $this->transactions()->countByUser($userId, $filters);
        $page = min((int) $filters['page'], max(1, (int) ceil(max(1, $total) / self::PER_PAGE)));
        $filters['page'] = $page;

        $transactions = $this->transactions()->findByUser($userId, $filters);
        $month = $filters['month'];

        // Calculate summary for current month filter
        $summary = [
            'income' => $this->transactions()->sumByTypeAndUser($userId, 'income', $month),
            'expense' => $this->transactions()->sumByTypeAndUser($userId, 'expense', $month),
        ];
        $summary['balance'] = $summary['income'] - $summary['expense'];

        $this->render('transactions/index', [
            'title' => 'Transactions',
            'pageTitle' => 'Transactions',
            'user' => $user,
            'transactions' => $transactions,
            'filters' => $filters,
            'summary' => $summary,
            'total' => $total,
            'perPage' => self::PER_PAGE,
            'budgets' => $this->budgets()->findByUser($userId),
            'categories' => $this->categories()->findAllForUser($userId),
            'flashSuccess' => $this->session->getFlash('success'),
            'flashWarning' => $this->session->getFlash('warning'),
            'flashDanger' => $this->session->getFlash('danger'),
        ]);
    }

    /**
     * Display the transaction creation form.
     * 
     * Restores form state (old input, validation errors) from session if available.
     */
    public function showCreate(): void
    {
        Auth::requireRole('user');

        $user = Auth::getUser() ?? [];
        $userId = (int) ($user['id'] ?? 0);
        $formState = $this->consumeFormState('transaction_form');

        $this->render('transactions/form', [
            'title' => 'Nouvelle transaction',
            'pageTitle' => 'Nouvelle transaction',
            'user' => $user,
            'mode' => 'create',
            'action' => '/transactions/create',
            'transaction' => $formState['old'] ?? [],
            'errors' => $formState['errors'] ?? [],
            'selectedType' => $formState['old']['type'] ?? 'expense',
            'budgets' => $this->budgets()->findByUser($userId),
            'categories' => $this->categories()->findAllForUser($userId),
            'flashDanger' => $this->session->getFlash('danger'),
        ]);
    }

    /**
     * Process transaction creation via POST.
     * 
     * Validates:
     * - CSRF token
     * - Required fields (type, amount, date, budget_id)
     * - Valid budget ownership
     * - Valid category (if provided)
     * 
     * On validation error, stores input and errors in session and redirects back.
     */
    public function create(): void
    {
        Auth::requireRole('user');

        $user = Auth::getUser() ?? [];
        $userId = (int) ($user['id'] ?? 0);
        $input = $_POST;

        // Validate CSRF token
        if (!CSRF::validateToken((string) ($input['csrf_token'] ?? ''))) {
            $this->storeFormState('transaction_form', $input, ['form' => 'La session du formulaire a expiré. Veuillez réessayer.']);
            $this->session->setFlash('danger', 'Le formulaire a expiré.');
            $this->redirect('/transactions/create');
        }

        // Validate input
        list($payload, $errors) = $this->validateTransactionInput($input, $userId);
        if ($errors !== []) {
            $this->storeFormState('transaction_form', $input, $errors);
            $this->session->setFlash('danger', 'Corrigez les erreurs avant d\'enregistrer la transaction.');
            $this->redirect('/transactions/create');
        }

        // Create transaction
        $this->transactions()->create($payload);
        $this->session->setFlash('success', 'La transaction a été créée avec succès.');
        $this->redirect('/transactions');
    }

    /**
     * Display the transaction edit form.
     * 
     * Validates:
     * - Transaction exists
     * - User owns the transaction
     */
    public function showEdit(): void
    {
        Auth::requireRole('user');

        $user = Auth::getUser() ?? [];
        $userId = (int) ($user['id'] ?? 0);
        $transactionId = $this->normalizeId($_GET['id'] ?? null);

        if ($transactionId === null) {
            $this->session->setFlash('danger', 'Transaction introuvable.');
            $this->redirect('/transactions');
        }

        $transaction = $this->transactions()->findById($transactionId);
        if ($transaction === null) {
            http_response_code(404);
            echo '404 - Transaction introuvable';
            return;
        }

        if ((int) ($transaction['user_id'] ?? 0) !== $userId) {
            http_response_code(403);
            echo '403 - Accès interdit';
            return;
        }

        $formState = $this->consumeFormState('transaction_form');

        $this->render('transactions/form', [
            'title' => 'Modifier la transaction',
            'pageTitle' => 'Modifier la transaction',
            'user' => $user,
            'mode' => 'edit',
            'action' => '/transactions/edit?id=' . $transactionId,
            'transaction' => $formState['old'] ?? $transaction,
            'errors' => $formState['errors'] ?? [],
            'selectedType' => $formState['old']['type'] ?? $transaction['type'],
            'budgets' => $this->budgets()->findByUser($userId),
            'categories' => $this->categories()->findAllForUser($userId),
            'flashDanger' => $this->session->getFlash('danger'),
        ]);
    }

    /**
     * Process transaction update via POST.
     */
    public function edit(): void
    {
        Auth::requireRole('user');

        $user = Auth::getUser() ?? [];
        $userId = (int) ($user['id'] ?? 0);
        $input = $_POST;
        $transactionId = $this->normalizeId($input['id'] ?? ($_GET['id'] ?? null));

        if ($transactionId === null) {
            $this->session->setFlash('danger', 'Transaction introuvable.');
            $this->redirect('/transactions');
        }

        $transaction = $this->transactions()->findById($transactionId);
        if ($transaction === null) {
            http_response_code(404);
            echo '404 - Transaction introuvable';
            return;
        }

        if ((int) ($transaction['user_id'] ?? 0) !== $userId) {
            http_response_code(403);
            echo '403 - Accès interdit';
            return;
        }

        if (!CSRF::validateToken((string) ($input['csrf_token'] ?? ''))) {
            $this->storeFormState('transaction_form', $input, ['form' => 'La session du formulaire a expiré. Veuillez réessayer.']);
            $this->session->setFlash('danger', 'Le formulaire a expiré.');
            $this->redirect('/transactions/edit?id=' . $transactionId);
        }

        list($payload, $errors) = $this->validateTransactionInput($input, $userId, $transactionId);
        if ($errors !== []) {
            $this->storeFormState('transaction_form', $input, $errors);
            $this->session->setFlash('danger', 'Corrigez les erreurs avant d’enregistrer la transaction.');
            $this->redirect('/transactions/edit?id=' . $transactionId);
        }

        $this->transactions()->update($transactionId, $payload);
        $this->session->setFlash('success', 'La transaction a été modifiée avec succès.');
        $this->redirect('/transactions');
    }

    /**
     * Process transaction deletion via POST.
     */
    public function delete(): void
    {
        Auth::requireRole('user');

        $user = Auth::getUser() ?? [];
        $userId = (int) ($user['id'] ?? 0);
        $input = $_POST;
        $transactionId = $this->normalizeId($input['id'] ?? null);

        if (!CSRF::validateToken((string) ($input['csrf_token'] ?? ''))) {
            $this->session->setFlash('danger', 'La session du formulaire a expiré.');
            $this->redirect('/transactions');
        }

        if ($transactionId === null) {
            $this->session->setFlash('danger', 'Transaction introuvable.');
            $this->redirect('/transactions');
        }

        $transaction = $this->transactions()->findById($transactionId);
        if ($transaction === null) {
            http_response_code(404);
            echo '404 - Transaction introuvable';
            return;
        }

        if ((int) ($transaction['user_id'] ?? 0) !== $userId) {
            http_response_code(403);
            echo '403 - Accès interdit';
            return;
        }

        $this->transactions()->delete($transactionId);
        $this->session->setFlash('success', 'La transaction a été supprimée.');
        $this->redirect('/transactions');
    }

    /**
     * Validate and sanitize transaction input data.
     *
     * @param array<string, mixed> $input Raw POST data
     * @param int $userId Current user ID
     * @param int|null $transactionId Existing transaction ID (for edit ownership check)
     * @return array{0: array<string, mixed>, 1: array<string, string>} [payload, errors]
     */
    private function validateTransactionInput(array $input, int $userId, ?int $transactionId = null): array
    {
        $errors = [];
        $type = strtolower(trim((string) ($input['type'] ?? 'expense')));
        $amount = trim((string) ($input['amount'] ?? ''));
        $date = trim((string) ($input['date'] ?? date('Y-m-d')));
        $budgetId = $this->normalizeId($input['budget_id'] ?? null);
        $categoryId = $this->normalizeId($input['category_id'] ?? null);
        $description = trim((string) ($input['description'] ?? ''));

        if (!in_array($type, ['income', 'expense'], true)) {
            $errors['type'] = 'Veuillez sélectionner un type valide.';
        }

        if ($amount === '' || !is_numeric($amount) || (float) $amount <= 0) {
            $errors['amount'] = 'Le montant doit être supérieur à 0.';
        }

        if (!$this->isValidDate($date)) {
            $errors['date'] = 'Veuillez sélectionner une date valide.';
        }

        if ($budgetId === null || !$this->budgetBelongsToUser($budgetId, $userId, $transactionId)) {
            $errors['budget_id'] = 'Veuillez sélectionner un budget qui vous appartient ou auquel vous êtes membre.';
        }

        if ($categoryId !== null && !$this->categoryBelongsToUserOrDefault($categoryId, $userId)) {
            $errors['category_id'] = 'Veuillez sélectionner une catégorie valide.';
        }

        if ($description !== '' && strlen($description) > 255) {
            $errors['description'] = 'La description ne peut pas dépasser 255 caractères.';
        }

        if ($errors !== []) {
            return [[], $errors];
        }

        $payload = [
            'budget_id' => $budgetId,
            'user_id' => $userId,
            'category_id' => $categoryId,
            'type' => $type,
            'amount' => number_format((float) $amount, 2, '.', ''),
            'description' => $description !== '' ? $description : null,
            'date' => $date,
        ];

        return [$payload, []];
    }

    /**
     * Read filter parameters from the query string.
     *
     * @return array<string, mixed> Normalized filter values
     */
    private function readFiltersFromQuery(): array
    {
        return [
            'type' => (string) ($_GET['type'] ?? 'all'),
            'budget_id' => $this->normalizeId($_GET['budget_id'] ?? null),
            'category_id' => $this->normalizeId($_GET['category_id'] ?? null),
            'month' => $this->normalizeMonth((string) ($_GET['month'] ?? date('Y-m'))),
            'page' => max(1, $this->normalizeId($_GET['page'] ?? null) ?? 1),
        ];
    }

    /**
     * Verify a budget belongs to the user (owner or member).
     *
     * @param int $budgetId Budget ID to check
     * @param int $userId User ID
     * @param int|null $transactionId Existing transaction ID (allows keeping original budget on edit)
     * @return bool True if the budget is accessible to the user
     */
    private function budgetBelongsToUser(int $budgetId, int $userId, ?int $transactionId = null): bool
    {
        foreach ($this->budgets()->findByUser($userId) as $budget) {
            if ((int) ($budget['id'] ?? 0) !== $budgetId) {
                continue;
            }

            return true;
        }

        if ($transactionId !== null) {
            $transaction = $this->transactions()->findById($transactionId);
            if ($transaction !== null && (int) ($transaction['budget_id'] ?? 0) === $budgetId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify a category belongs to the user or is a default category.
     *
     * @param int $categoryId Category ID to check
     * @param int $userId User ID
     * @return bool True if the category is accessible to the user
     */
    private function categoryBelongsToUserOrDefault(int $categoryId, int $userId): bool
    {
        $category = $this->categories()->findById($categoryId);
        if ($category === null) {
            return false;
        }

        if (!empty($category['is_default'])) {
            return true;
        }

        return (int) ($category['user_id'] ?? 0) === $userId;
    }

    /**
     * Validate a date string is a valid calendar date not more than 1 year in the future.
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
     * Normalize a month string to YYYY-MM format.
     *
     * @param string $month Raw month input
     * @return string Normalized YYYY-MM or current month
     */
    private function normalizeMonth(string $month): string
    {
        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            return date('Y-m');
        }

        return $month;
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
     * Store form state in session for sticky forms (validation errors).
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
     * Lazy-load the Category model instance.
     *
     * @return Category
     */
    private function categories(): Category
    {
        if ($this->categories === null) {
            $this->categories = new Category();
        }

        return $this->categories;
    }
}