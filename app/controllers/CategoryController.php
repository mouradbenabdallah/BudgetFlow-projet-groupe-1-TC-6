<?php

class CategoryController
{
    private ?Category $categories = null;
    private Session $session;

    public function __construct()
    {
        $this->session = new Session();
    }

    public function index(): void
    {
        Auth::requireRole('user');

        $user = Auth::getUser() ?? [];
        $userId = (int) ($user['id'] ?? 0);
        $categoryState = $this->consumeFormState('category_form');
        $allCategories = $this->categories()->findAllForUser($userId);

        $defaultCategories = array_values(array_filter($allCategories, static fn (array $category): bool => !empty($category['is_default'])));
        $personalCategories = array_values(array_filter($allCategories, static fn (array $category): bool => empty($category['is_default']) && (int) ($category['user_id'] ?? 0) === $userId));

        $totalCategories = count($allCategories);
        $expenseCategories = 0;
        $incomeCategories = 0;

        foreach ($allCategories as $cat) {
            $type = $this->inferCategoryType($cat);
            if ($type === 'expense') {
                $expenseCategories++;
            } else {
                $incomeCategories++;
            }
        }

        $this->render('categories/index', [
            'title' => 'Catégories',
            'pageTitle' => 'Catégories',
            'user' => $user,
            'defaultCategories' => $defaultCategories,
            'personalCategories' => $personalCategories,
            'allCategories' => $allCategories,
            'totalCategories' => $totalCategories,
            'totalExpense' => $expenseCategories,
            'totalIncome' => $incomeCategories,
            'flashSuccess' => $this->session->getFlash('success'),
            'flashWarning' => $this->session->getFlash('warning'),
            'flashDanger' => $this->session->getFlash('danger'),
            'formState' => $categoryState,
        ]);
    }

    public function create(): void
    {
        Auth::requireRole('user');

        $user = Auth::getUser() ?? [];
        $userId = (int) ($user['id'] ?? 0);
        $input = $_POST;

        if (!CSRF::validateToken((string) ($input['csrf_token'] ?? ''))) {
            $this->storeFormState('category_form', 'create', $input, ['form' => 'La session du formulaire a expiré. Veuillez réessayer.']);
            $this->session->setFlash('danger', 'Le formulaire a expiré.');
            $this->redirect('/categories');
        }

        [$payload, $errors] = $this->validateCategoryInput($input, $userId);
        if ($errors !== []) {
            $this->storeFormState('category_form', 'create', $input, $errors);
            $this->session->setFlash('danger', 'Corrigez les erreurs avant de créer la catégorie.');
            $this->redirect('/categories');
        }

        if ($this->isDuplicateName($userId, $payload['name'])) {
            $this->storeFormState('category_form', 'create', $input, ['name' => 'Une catégorie portant ce nom existe déjà.']);
            $this->session->setFlash('danger', 'Cette catégorie existe déjà.');
            $this->redirect('/categories');
        }

        $this->categories()->create($payload);
        $this->session->setFlash('success', 'La catégorie a été créée avec succès.');
        $this->redirect('/categories');
    }

    public function edit(): void
    {
        Auth::requireRole('user');

        $user = Auth::getUser() ?? [];
        $userId = (int) ($user['id'] ?? 0);
        $input = $_POST;
        $categoryId = $this->normalizeId($input['id'] ?? null);

        if (!CSRF::validateToken((string) ($input['csrf_token'] ?? ''))) {
            $this->storeFormState('category_form', 'edit', $input, ['form' => 'La session du formulaire a expiré. Veuillez réessayer.']);
            $this->session->setFlash('danger', 'Le formulaire a expiré.');
            $this->redirect('/categories');
        }

        if ($categoryId === null) {
            $this->session->setFlash('danger', 'Catégorie introuvable.');
            $this->redirect('/categories');
        }

        $category = $this->categories()->findById($categoryId);
        if ($category === null) {
            http_response_code(404);
            echo '404 - Catégorie introuvable';
            return;
        }

        if (!empty($category['is_default']) || (int) ($category['user_id'] ?? 0) !== $userId) {
            http_response_code(403);
            echo '403 - Accès interdit';
            return;
        }

        [$payload, $errors] = $this->validateCategoryInput($input, $userId, $categoryId);
        if ($errors !== []) {
            $this->storeFormState('category_form', 'edit', $input, $errors);
            $this->session->setFlash('danger', 'Corrigez les erreurs avant d\'enregistrer la catégorie.');
            $this->redirect('/categories');
        }

        if ($this->isDuplicateName($userId, $payload['name'], $categoryId)) {
            $this->storeFormState('category_form', 'edit', $input, ['name' => 'Une catégorie portant ce nom existe déjà.']);
            $this->session->setFlash('danger', 'Cette catégorie existe déjà.');
            $this->redirect('/categories');
        }

        $this->categories()->update($categoryId, $payload);
        $this->session->setFlash('success', 'La catégorie a été modifiée avec succès.');
        $this->redirect('/categories');
    }

    public function delete(): void
    {
        Auth::requireRole('user');

        $user = Auth::getUser() ?? [];
        $userId = (int) ($user['id'] ?? 0);
        $input = $_POST;
        $categoryId = $this->normalizeId($input['id'] ?? null);

        if (!CSRF::validateToken((string) ($input['csrf_token'] ?? ''))) {
            $this->session->setFlash('danger', 'La session du formulaire a expiré.');
            $this->redirect('/categories');
        }

        if ($categoryId === null) {
            $this->session->setFlash('danger', 'Catégorie introuvable.');
            $this->redirect('/categories');
        }

        $category = $this->categories()->findById($categoryId);
        if ($category === null) {
            http_response_code(404);
            echo '404 - Catégorie introuvable';
            return;
        }

        if (!empty($category['is_default']) || (int) ($category['user_id'] ?? 0) !== $userId) {
            http_response_code(403);
            echo '403 - Accès interdit';
            return;
        }

        $usedTransactions = $this->categories()->isUsedInTransactions($categoryId);
        $this->categories()->delete($categoryId);

        if ($usedTransactions > 0) {
            $this->session->setFlash('warning', sprintf(
                'Cette catégorie est utilisée par %d transaction(s). Les transactions ont été conservées sans catégorie.',
                $usedTransactions
            ));
        } else {
            $this->session->setFlash('success', 'La catégorie a été supprimée.');
        }

        $this->redirect('/categories');
    }

    private function inferCategoryType(array $category): string
    {
        $count = (int) ($category['transaction_count'] ?? 0);
        $total = (float) ($category['total'] ?? 0);

        if ($count === 0) {
            return 'expense';
        }

        $avg = $total / $count;

        return $avg >= 0 ? 'income' : 'expense';
    }

    private function validateCategoryInput(array $input, int $userId, ?int $categoryId = null): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        $color = trim((string) ($input['color'] ?? ''));

        if ($name === '') {
            $errors['name'] = 'Le nom est obligatoire.';
        } elseif (strlen($name) > 80) {
            $errors['name'] = 'Le nom ne peut pas dépasser 80 caractères.';
        }

        if ($color === '' || preg_match('/^#[0-9A-Fa-f]{6}$/', $color) !== 1) {
            $errors['color'] = 'Veuillez choisir une couleur hexadécimale valide.';
        }

        if ($errors !== []) {
            return [[], $errors];
        }

        return [[
            'user_id' => $userId,
            'name' => $name,
            'color' => $color,
            'is_default' => false,
        ], []];
    }

    private function isDuplicateName(int $userId, string $name, ?int $ignoreId = null): bool
    {
        foreach ($this->categories()->findAllForUser($userId) as $category) {
            if ((int) ($category['user_id'] ?? 0) !== $userId) {
                continue;
            }

            if ($ignoreId !== null && (int) ($category['id'] ?? 0) === $ignoreId) {
                continue;
            }

            if (mb_strtolower(trim((string) ($category['name'] ?? ''))) === mb_strtolower(trim($name))) {
                return true;
            }
        }

        return false;
    }

    private function normalizeId(mixed $value): ?int
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        $integer = (int) $value;

        return $integer > 0 ? $integer : null;
    }

    private function storeFormState(string $key, string $mode, array $old, array $errors): void
    {
        $_SESSION[$key] = [
            'mode' => $mode,
            'old' => $this->sanitizeArray($old),
            'errors' => $errors,
        ];
    }

    private function consumeFormState(string $key): array
    {
        $state = $_SESSION[$key] ?? [];
        unset($_SESSION[$key]);

        if (!is_array($state)) {
            return [];
        }

        return $state;
    }

    private function sanitizeArray(array $values): array
    {
        $sanitized = [];
        foreach ($values as $key => $value) {
            $sanitized[$key] = is_string($value) ? trim($value) : $value;
        }

        return $sanitized;
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

    private function categories(): Category
    {
        if ($this->categories === null) {
            $this->categories = new Category();
        }

        return $this->categories;
    }
}
