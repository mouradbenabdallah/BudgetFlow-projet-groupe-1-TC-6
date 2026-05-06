<?php
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$formatMoney = static fn (mixed $amount): string => number_format((float) $amount, 3, ',', ' ') . ' TND';
$filters = $filters ?? [];
$transactions = $transactions ?? [];
$budgets = $budgets ?? [];
$categories = $categories ?? [];
$summary = $summary ?? ['income' => 0, 'expense' => 0, 'balance' => 0];
$formState = $formState ?? [];
$errorBag = $formState['errors'] ?? [];
$page = max(1, (int) ($filters['page'] ?? 1));
$perPage = max(1, (int) ($perPage ?? 20));
$totalCount = max(0, (int) ($total ?? 0));
$totalPages = max(1, (int) ceil($totalCount / $perPage));

$month = $filters['month'] ?? '';
$currentMonth = date('Y-m');
?>

<div class="bf-page-transactions-index">
    <div class="bf-container">
        <?php if (!empty($flashSuccess)): ?>
        <div class="bf-alert bf-alert-success mb-4"><?= $e($flashSuccess) ?></div>
        <?php endif; ?>
        <?php if (!empty($flashWarning)): ?>
        <div class="bf-alert bf-alert-warning mb-4"><?= $e($flashWarning) ?></div>
        <?php endif; ?>
        <?php if (!empty($flashDanger)): ?>
        <div class="bf-alert bf-alert-danger mb-4"><?= $e($flashDanger) ?></div>
        <?php endif; ?>

        <div class="bf-page-header">
            <div>
                <h1 class="bf-page-title">Transactions</h1>
                <p class="bf-page-subtitle">Track and manage your financial activity</p>
            </div>
        </div>

        <div class="bf-summary-cards">
            <div class="bf-summary-card">
                <div class="bf-summary-header">
                    <span class="bf-summary-label">TOTAL TRANSACTIONS</span>
                    <svg class="bf-summary-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <div class="bf-summary-value"><?= $e($totalCount) ?></div>
            </div>
            <div class="bf-summary-card">
                <div class="bf-summary-header">
                    <span class="bf-summary-label">TOTAL INCOME</span>
                    <svg class="bf-summary-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
                </div>
                <div class="bf-summary-value bf-income">+<?= $e($formatMoney($summary['income'] ?? 0)) ?></div>
            </div>
            <div class="bf-summary-card">
                <div class="bf-summary-header">
                    <span class="bf-summary-label">TOTAL EXPENSES</span>
                    <svg class="bf-summary-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                </div>
                <div class="bf-summary-value bf-expense">-<?= $e($formatMoney($summary['expense'] ?? 0)) ?></div>
            </div>
        </div>

        <div class="bf-filter-bar">
            <div class="bf-filter-group">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="transactionSearch" class="bf-input" placeholder="Search transactions...">
            </div>
            <button class="bf-btn bf-filter-btn-toggle" id="filtersToggle">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Filters
            </button>
            <button class="bf-btn bf-btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Transaction
            </button>
        </div>

        <div id="advancedFilters" style="display: none; margin-bottom: 20px;">
            <div class="bf-filter-advanced">
                <div class="bf-filter-advanced-group">
                    <label class="bf-label">Type</label>
                    <select id="filterType" class="bf-select">
                        <option value="">All Types</option>
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                    </select>
                </div>
                <div class="bf-filter-advanced-group">
                    <label class="bf-label">Category</label>
                    <select id="filterCategory" class="bf-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $e((int) ($cat['id'] ?? 0)) ?>"><?= $e($cat['name'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="bf-filter-advanced-group">
                    <label class="bf-label">Month</label>
                    <input type="month" id="filterMonth" class="bf-input" value="<?= $e($month) ?>">
                </div>
            </div>
        </div>

        <?php if ($transactions === []): ?>
        <div class="bf-empty-state">
            <p class="bf-empty-state-text">No transactions found. Start by adding one!</p>
            <button class="bf-btn bf-btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Transaction
            </button>
        </div>
        <?php else: ?>
        <div class="bf-transactions-table-wrapper">
            <table class="bf-transactions-table">
                <thead>
                    <tr>
                        <th>DESCRIPTION</th>
                        <th>CATEGORY</th>
                        <th>TYPE</th>
                        <th>AMOUNT</th>
                        <th>DATE</th>
                        <th>STATUS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                    <?php
                        $isIncome = ($transaction['type'] ?? '') === 'income';
                        $categoryColor = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($transaction['category_color'] ?? '')) === 1
                            ? (string) $transaction['category_color']
                            : '#8B90A7';
                        $categoryName = $transaction['category_name'] ?? 'Uncategorized';
                        $description = $transaction['description'] ?? '';
                        $amount = (float) ($transaction['amount'] ?? 0);
                        $date = $transaction['date'] ?? '';
                        $status = 'completed';
                        $statusLabel = 'Completed';
                    ?>
                    <tr data-type="<?= $e($isIncome ? 'income' : 'expense') ?>" data-category="<?= $e($transaction['category_id'] ?? '') ?>">
                        <td>
                            <div class="bf-transaction-description">
                                <span class="bf-transaction-icon <?= $isIncome ? 'income' : 'expense' ?>">
                                    <?php if ($isIncome): ?>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
                                    <?php else: ?>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                                    <?php endif; ?>
                                </span>
                                <span><?= $e($description ?: $categoryName) ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="bf-category-pill" style="background: rgba(0,0,0,0.04); color: #516a70;">
                                <?= $e($categoryName) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($isIncome): ?>
                            <span class="bf-type-pill bf-type-income">Income</span>
                            <?php else: ?>
                            <span class="bf-type-pill bf-type-expense">Expense</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="bf-transaction-amount <?= $isIncome ? 'income' : 'expense' ?>">
                                <?= $e(($isIncome ? '+' : '-') . $formatMoney($amount)) ?>
                            </span>
                        </td>
                        <td>
                            <span class="bf-transaction-date"><?= $e($date) ?></span>
                        </td>
                        <td>
                            <span class="bf-status-pill bf-status-<?= $status ?>"><?= $e($statusLabel) ?></span>
                        </td>
                        <td>
                            <div class="bf-transaction-actions">
                                <a href="/transactions/edit?id=<?= $e((int) ($transaction['id'] ?? 0)) ?>"
                                    class="bf-btn-icon" title="Edit">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <form method="POST" action="/transactions/delete" style="display: inline;">
                                    <?= CSRF::getTokenField() ?>
                                    <input type="hidden" name="id" value="<?= $e((int) ($transaction['id'] ?? 0)) ?>">
                                    <button type="submit" class="bf-btn-icon bf-btn-icon-danger"
                                        data-confirm="Delete this transaction?"
                                        title="Delete">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="bf-pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="bf-pagination-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" class="bf-pagination-btn <?= $i === $page ? 'is-active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" class="bf-pagination-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>


<!-- Add Transaction Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1" aria-labelledby="addTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bf-modal-dark">
            <div class="modal-header">
                <div>
                    <small class="bf-modal-label-dark">NOUVELLE TRANSACTION</small>
                    <h5 class="modal-title" id="addTransactionModalLabel">Ajouter une transaction</h5>
                </div>
                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="/transactions/create">
                <?= CSRF::getTokenField() ?>
                <input type="hidden" id="modal-transaction-type" name="type" value="expense">
                <div class="modal-body">
                    <div class="bf-form-group-dark">
                        <label class="bf-label-dark">TYPE</label>
                        <div class="bf-type-toggle-group-dark">
                            <button type="button" class="bf-type-toggle-dark" data-type="expense"><i class="bi bi-arrow-up-circle"></i> Dépense</button>
                            <button type="button" class="bf-type-toggle-dark" data-type="income"><i class="bi bi-arrow-down-circle"></i> Revenu</button>
                        </div>
                    </div>
                    <div class="bf-form-group-dark">
                        <label class="bf-label-dark" for="modal-amount">MONTANT (DT)</label>
                        <div class="input-group">
                            <span class="input-group-text">DT</span>
                            <input class="form-control bf-input-dark" id="modal-amount" type="number" step="0.001" min="0.001" name="amount" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="bf-form-row-dark">
                        <div class="bf-form-group-dark">
                            <label class="bf-label-dark" for="modal-date">DATE</label>
                            <input class="form-control bf-input-dark" id="modal-date" type="date" name="date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="bf-form-group-dark">
                            <label class="bf-label-dark" for="modal-budget">BUDGET</label>
                            <select class="form-select bf-input-dark" id="modal-budget" name="budget_id" required>
                                <option value="">Sélectionner un budget</option>
                                <?php foreach ($budgets as $budget): ?>
                                    <option value="<?= $e((int) ($budget['id'] ?? 0)) ?>"><?= $e($budget['name'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="bf-form-group-dark">
                        <label class="bf-label-dark" for="modal-category">CATÉGORIE</label>
                        <select class="form-select bf-input-dark" id="modal-category" name="category_id">
                            <option value="">Sans catégorie</option>
                            <?php foreach ($categories as $cat): ?>
                                <?php $color = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($cat['color'] ?? '')) === 1 ? (string) $cat['color'] : '#8B90A7'; ?>
                                <option value="<?= $e((int) ($cat['id'] ?? 0)) ?>" data-color="<?= $e($color) ?>"><?= $e($cat['name'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="bf-form-group-dark">
                        <label class="bf-label-dark" for="modal-description">DESCRIPTION</label>
                        <input class="form-control bf-input-dark" id="modal-description" type="text" name="description" maxlength="255" placeholder="Ex. Courses hebdomadaires">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="bf-btn bf-btn-cancel-dark" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="bf-btn bf-btn-submit-dark">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Ajouter la transaction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('addTransactionModal');
    const typeButtons = modal.querySelectorAll('.bf-type-toggle-dark');
    const typeInput = document.getElementById('modal-transaction-type');
    
    typeButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            typeButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            typeInput.value = btn.getAttribute('data-type');
        });
    });
    // Set default
    typeButtons[0].classList.add('active');
});
</script>
