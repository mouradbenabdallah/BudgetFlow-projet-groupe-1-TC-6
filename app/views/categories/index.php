<?php
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$defaultCategories = $defaultCategories ?? [];
$personalCategories = $personalCategories ?? [];
$allCategories = $allCategories ?? [];
$formState = $formState ?? [];
$createState = ($formState['mode'] ?? '') === 'create' ? $formState : [];
$editState = ($formState['mode'] ?? '') === 'edit' ? $formState : [];
$totalCategories = $totalCategories ?? count($allCategories);
$totalExpense = $totalExpense ?? 0;
$totalIncome = $totalIncome ?? 0;

$formatMoney = static fn (mixed $amount): string => number_format((float) $amount, 3, ',', ' ') . ' TND';

$categoryEmojis = [
    'alimentation' => '🍔',
    'transport' => '🚗',
    'shopping' => '🛍️',
    'santé' => '💪',
    'services publics' => '⚡',
    'salaire' => 'bi bi-cash-stack',
    'freelance' => '💻',
    'divertissement' => '🎬',
    'éducation' => '📚',
    'maison' => '🏠',
    'assurance' => '🛡️',
    'logement' => '🏠',
    'loisirs' => '🎯',
    'études' => '📚',
    'autre' => '📁',
];

$defaultPalette = ['#00ED64', '#E11D48', '#3B82F6', '#F59E0B', '#8B5CF6', '#EC4899', '#14B8A6', '#6366F1'];

$autoOpenModal = !empty($createState) ? 'createCategoryModal' : (!empty($editState) ? 'editCategoryModal' : '');

$getEmoji = static function (string $name) use ($categoryEmojis): string {
    $lower = mb_strtolower($name, 'UTF-8');
    return $categoryEmojis[$lower] ?? '📁';
};

$inferType = static function (array $cat): string {
    $count = (int) ($cat['transaction_count'] ?? 0);
    $total = (float) ($cat['total'] ?? 0);
    if ($count === 0) {
        return 'expense';
    }
    $avg = $total / $count;
    return $avg >= 0 ? 'income' : 'expense';
};

$getCatColor = static function (array $cat) use ($defaultPalette): string {
    if (!empty($cat['color'])) {
        return $cat['color'];
    }
    $idx = (int) ($cat['id'] ?? 0) % count($defaultPalette);
    return $defaultPalette[$idx];
};

$getTintedBg = static function (string $hex): string {
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "rgba($r, $g, $b, 0.1)";
};
?>

<div class="bf-page-categories-index">
    <div class="bf-container">

        <?php if ($flashSuccess): ?>
        <div class="bf-alert bf-alert-success mb-4"><?= $e($flashSuccess) ?></div>
        <?php endif; ?>
        <?php if ($flashWarning): ?>
        <div class="bf-alert bf-alert-warning mb-4"><?= $e($flashWarning) ?></div>
        <?php endif; ?>
        <?php if ($flashDanger): ?>
        <div class="bf-alert bf-alert-danger mb-4"><?= $e($flashDanger) ?></div>
        <?php endif; ?>

        <div class="bf-page-header">
            <div>
                <h1 class="bf-page-title">Categories</h1>
                <p class="bf-page-subtitle">Organize your transactions with custom categories</p>
            </div>
        </div>

        <div class="bf-summary-cards">
            <div class="bf-summary-card">
                <div class="bf-summary-header">
                    <span class="bf-summary-label">TOTAL CATEGORIES</span>
                    <svg class="bf-summary-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                </div>
                <div class="bf-summary-value"><?= $e($totalCategories) ?></div>
            </div>
            <div class="bf-summary-card">
                <div class="bf-summary-header">
                    <span class="bf-summary-label">EXPENSE CATEGORIES</span>
                    <svg class="bf-summary-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                </div>
                <div class="bf-summary-value"><?= $e($totalExpense) ?></div>
            </div>
            <div class="bf-summary-card">
                <div class="bf-summary-header">
                    <span class="bf-summary-label">INCOME CATEGORIES</span>
                    <svg class="bf-summary-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                </div>
                <div class="bf-summary-value"><?= $e($totalIncome) ?></div>
            </div>
        </div>

        <div class="bf-filter-bar">
            <div class="bf-filter-group">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="categorySearch" class="bf-input" placeholder="Search categories...">
            </div>
            <div class="bf-filter-buttons">
                <button class="bf-filter-btn is-active" data-filter="all">All</button>
                <button class="bf-filter-btn" data-filter="expense">Expense</button>
                <button class="bf-filter-btn" data-filter="income">Income</button>
            </div>
            <button class="bf-btn bf-btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Category
            </button>
        </div>

        <div class="bf-categories-grid">
            <?php if (empty($allCategories)): ?>
            <div class="bf-empty-state">
                <p class="bf-empty-state-text">No categories yet. Start by adding one!</p>
                <button class="bf-btn bf-btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Category
                </button>
            </div>
            <?php else: ?>
            <?php foreach ($allCategories as $category): ?>
                <?php
                $catType = $inferType($category);
                $catName = $category['name'] ?? '';
                $emoji = $getEmoji($catName);
                $isPersonal = in_array($category, $personalCategories, true);
                $catColor = $getCatColor($category);
                $tintedBg = $getTintedBg($catColor);
                $txCount = (int) ($category['transaction_count'] ?? 0);
                $catTotal = $category['total'] ?? 0;
                ?>
            <div class="bf-category-card-grid" data-category-type="<?= $e($catType) ?>" data-category-name="<?= $e($catName) ?>">
                <div class="bf-category-icon-container" style="background-color: <?= $e($tintedBg) ?>;">
                    <div class="bf-category-icon"><?= $emoji ?></div>
                </div>

                <div class="bf-category-info-grid">
                    <h3 class="bf-category-name"><?= $e($catName) ?></h3>

                    <?php if ($catType === 'income'): ?>
                    <span class="bf-category-type-badge" style="background: rgba(0, 237, 100, 0.1); color: #00ED64;">Income</span>
                    <?php else: ?>
                    <span class="bf-category-type-badge" style="background: rgba(225, 29, 72, 0.1); color: #E11D48;">Expense</span>
                    <?php endif; ?>

                    <div class="bf-category-stats-grid">
                        <div class="bf-stat-box">
                            <span class="bf-stat-label">TRANSACTIONS</span>
                            <span class="bf-stat-value"><?= $e($txCount) ?></span>
                        </div>
                        <div class="bf-stat-box bf-stat-box-right">
                            <span class="bf-stat-label">TOTAL</span>
                            <span class="bf-stat-value" style="color: <?= $e($catColor) ?>;">
                                <?= $formatMoney($catTotal) ?>
                            </span>
                        </div>
                    </div>

                    <div class="bf-category-color-bar" style="background-color: <?= $e($catColor) ?>;"></div>
                </div>

                <?php if ($isPersonal): ?>
                <div class="bf-category-actions">
                    <button class="bf-btn bf-btn-sm"
                        data-category-edit="<?= $e($category['id'] ?? '') ?>"
                        data-category-name="<?= $e($catName) ?>"
                        data-category-color="<?= $e($catColor) ?>"
                        title="Edit">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <form method="POST" action="/categories/delete" style="display: inline;">
                        <?= CSRF::getTokenField() ?>
                        <input type="hidden" name="id" value="<?= $e($category['id'] ?? '') ?>">
                        <button type="submit" class="bf-btn bf-btn-sm bf-btn-sm-danger"
                            data-confirm="Delete this category?"
                            title="Delete">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Category Modal -->
<div class="modal fade" id="createCategoryModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bf-modal-dark">
            <div class="modal-header">
                <div>
                    <small class="bf-modal-label-dark">NEW CATEGORY</small>
                    <h5 class="modal-title" id="createModalLabel">Add Category</h5>
                </div>
                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="/categories/create">
                <div class="modal-body">
                    <?= CSRF::getTokenField() ?>

                    <div class="bf-form-group-dark">
                        <label class="bf-label-dark">NAME</label>
                        <input type="text" id="category-name" name="name" class="bf-input-dark"
                            placeholder="e.g. Groceries"
                            value="<?= $e($createState['old']['name'] ?? $createState['name'] ?? '') ?>"
                            required>
                        <?php if (isset($createState['errors']['name'])): ?>
                        <div class="bf-field-error-dark"><?= $e($createState['errors']['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="bf-form-group-dark">
                        <label class="bf-label-dark">COLOR</label>
                        <div id="create-color-picker" class="bf-color-presets-dark">
                            <?php foreach ($defaultPalette as $color): ?>
                            <button type="button" class="bf-color-btn-dark" data-category-color="<?= $e($color) ?>"
                                style="background-color: <?= $e($color) ?>;"
                                title="<?= $e($color) ?>"></button>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="category-color" name="color"
                            value="<?= $e($createState['old']['color'] ?? $createState['color'] ?? $defaultPalette[0]) ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="bf-btn bf-btn-cancel-dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bf-btn bf-btn-submit-dark">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bf-modal-dark">
            <div class="modal-header">
                <div>
                    <small class="bf-modal-label-dark">EDIT CATEGORY</small>
                    <h5 class="modal-title" id="editModalLabel">Edit Category</h5>
                </div>
                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="/categories/edit">
                <div class="modal-body">
                    <?= CSRF::getTokenField() ?>

                    <input type="hidden" id="edit-category-id" name="id">

                    <div class="bf-form-group-dark">
                        <label class="bf-label-dark">NAME</label>
                        <input type="text" id="edit-category-name" name="name" class="bf-input-dark"
                            placeholder="e.g. Groceries" required>
                    </div>

                    <div class="bf-form-group-dark">
                        <label class="bf-label-dark">COLOR</label>
                        <div id="edit-color-picker" class="bf-color-presets-dark">
                            <?php foreach ($defaultPalette as $color): ?>
                            <button type="button" class="bf-color-btn-dark" data-category-color="<?= $e($color) ?>"
                                style="background-color: <?= $e($color) ?>;"
                                title="<?= $e($color) ?>"></button>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="edit-category-color" name="color" value="#00ED64">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="bf-btn bf-btn-cancel-dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bf-btn bf-btn-submit-dark">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($autoOpenModal): ?>
<div id="bf-auto-open-modal" data-target="<?= $e($autoOpenModal) ?>" style="display:none;"></div>
<?php endif; ?>
