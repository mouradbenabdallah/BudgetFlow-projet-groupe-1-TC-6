<?php
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$transaction = $transaction ?? [];
$errors = $errors ?? [];
$budgets = $budgets ?? [];
$categories = $categories ?? [];
$mode = ($mode ?? 'create') === 'edit' ? 'edit' : 'create';
$action = (string) ($action ?? ($mode === 'edit' ? '/transactions/edit' : '/transactions/create'));
$selectedType = (string) ($selectedType ?? ($transaction['type'] ?? 'expense'));
$selectedBudgetId = (int) ($transaction['budget_id'] ?? 0);
$selectedCategoryId = (int) ($transaction['category_id'] ?? 0);
$dateValue = (string) ($transaction['date'] ?? date('Y-m-d'));
$amountValue = isset($transaction['amount']) ? number_format((float) $transaction['amount'], 3, '.', '') : '';
$descriptionValue = (string) ($transaction['description'] ?? '');
$minDate = '2000-01-01';
$maxDate = date('Y-m-d', strtotime('+1 year'));
?>

<div class="bf-page-transactions-form">
    <div class="bf-form-shell">
        <div class="bf-form-header">
            <span class="bf-kicker"><?= $mode === 'edit' ? 'Modifier une transaction' : 'Nouvelle transaction' ?></span>
            <h1 class="bf-card-title mb-2"><?= $mode === 'edit' ? 'Modifier la transaction' : 'Ajouter une transaction' ?></h1>
            <p class="bf-card-subtitle mb-0">Renseignez les informations financières avec les contrôles serveur et CSRF actifs.</p>
        </div>

        <?php if (!empty($flashDanger)): ?>
            <div class="bf-alert bf-alert-danger mx-4 mt-4" role="alert"><?= $e($flashDanger) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors['form'])): ?>
            <div class="bf-alert bf-alert-danger mx-4 mt-4" role="alert"><?= $e($errors['form']) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= $e($action) ?>" novalidate>
            <?= CSRF::getTokenField() ?>
            <?php if ($mode === 'edit'): ?>
                <input type="hidden" name="id" value="<?= $e((int) ($transaction['id'] ?? 0)) ?>">
            <?php endif; ?>
            <input type="hidden" id="transaction-type" name="type" value="<?= $e($selectedType) ?>">

            <div class="bf-form-grid">
                <div class="full">
                    <label class="bf-label">Type</label>
                    <div class="bf-type-toggle-group">
                        <button class="bf-type-toggle <?= $selectedType === 'income' ? 'is-active' : '' ?>" type="button" data-transaction-type="income">💰 Revenu</button>
                        <button class="bf-type-toggle <?= $selectedType === 'expense' ? 'is-active' : '' ?>" type="button" data-transaction-type="expense">💸 Dépense</button>
                    </div>
                    <?php if (!empty($errors['type'])): ?>
                        <div class="bf-alert bf-alert-danger mt-2" role="alert"><?= $e($errors['type']) ?></div>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="bf-label" for="transaction-amount">Montant (DT)</label>
                    <div class="input-group">
                        <span class="input-group-text">DT</span>
                        <input class="form-control bf-input <?= !empty($errors['amount']) ? 'is-invalid' : '' ?>" id="transaction-amount" type="number" step="0.001" min="0.001" name="amount" value="<?= $e($amountValue) ?>" required>
                    </div>
                    <?php if (!empty($errors['amount'])): ?>
                        <div class="bf-alert bf-alert-danger mt-2" role="alert"><?= $e($errors['amount']) ?></div>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="bf-label" for="transaction-date">Date</label>
                    <input class="form-control bf-input <?= !empty($errors['date']) ? 'is-invalid' : '' ?>" id="transaction-date" type="date" name="date" min="<?= $e($minDate) ?>" max="<?= $e($maxDate) ?>" value="<?= $e($dateValue) ?>" required>
                    <?php if (!empty($errors['date'])): ?>
                        <div class="bf-alert bf-alert-danger mt-2" role="alert"><?= $e($errors['date']) ?></div>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="bf-label" for="transaction-budget">Budget</label>
                    <select class="form-select bf-input <?= !empty($errors['budget_id']) ? 'is-invalid' : '' ?>" id="transaction-budget" name="budget_id" required>
                        <option value="">Sélectionner un budget</option>
                        <?php foreach ($budgets as $budget): ?>
                            <option value="<?= $e((int) ($budget['id'] ?? 0)) ?>" <?= $selectedBudgetId === (int) ($budget['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= $e($budget['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['budget_id'])): ?>
                        <div class="bf-alert bf-alert-danger mt-2" role="alert"><?= $e($errors['budget_id']) ?></div>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="bf-label" for="transaction-category">Catégorie</label>
                    <select class="form-select bf-input <?= !empty($errors['category_id']) ? 'is-invalid' : '' ?>" id="transaction-category" name="category_id">
                        <option value="">Sans catégorie</option>
                        <?php foreach ($categories as $category): ?>
                            <?php $color = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($category['color'] ?? '')) === 1 ? (string) $category['color'] : '#8B90A7'; ?>
                            <option value="<?= $e((int) ($category['id'] ?? 0)) ?>" data-color="<?= $e($color) ?>" <?= $selectedCategoryId === (int) ($category['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= $e($category['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="mt-2">
                        <span class="bf-category-preview" id="category-preview" style="display:none;">
                            <span class="bf-category-preview-dot" id="category-preview-dot"></span>
                            <span id="category-preview-label">Sans catégorie</span>
                        </span>
                    </div>
                    <?php if (!empty($errors['category_id'])): ?>
                        <div class="bf-alert bf-alert-danger mt-2" role="alert"><?= $e($errors['category_id']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="full">
                    <label class="bf-label" for="transaction-description">Description</label>
                    <input class="form-control bf-input <?= !empty($errors['description']) ? 'is-invalid' : '' ?>" id="transaction-description" type="text" name="description" maxlength="255" value="<?= $e($descriptionValue) ?>" placeholder="Ex. Courses hebdomadaires">
                    <?php if (!empty($errors['description'])): ?>
                        <div class="bf-alert bf-alert-danger mt-2" role="alert"><?= $e($errors['description']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="full d-grid gap-2">
                    <button class="btn bf-btn-primary" type="submit"><?= $mode === 'edit' ? 'Enregistrer les modifications' : 'Enregistrer la transaction' ?></button>
                    <a class="text-decoration-none" href="/transactions">← Retour aux transactions</a>
                </div>
            </div>
        </form>
    </div>
</div>