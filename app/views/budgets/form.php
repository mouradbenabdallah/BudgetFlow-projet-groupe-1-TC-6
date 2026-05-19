<?php
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$budget = $budget ?? [];
$errors = $errors ?? [];
$mode = ($mode ?? 'create') === 'edit' ? 'edit' : 'create';
$action = (string) ($action ?? ($mode === 'edit' ? '/budgets/edit' : '/budgets/create'));

$nameValue = (string) ($budget['name'] ?? '');
$selectedType = (string) ($budget['type'] ?? 'personal');
$selectedPeriod = (string) ($budget['period'] ?? 'monthly');
$amountLimitValue = $budget['amount_limit'] !== null ? number_format((float) $budget['amount_limit'], 0, '.', '') : '';
$startDateValue = (string) ($budget['start_date'] ?? date('Y-m-d'));
$minDate = '2000-01-01';
$maxDate = date('Y-m-d', strtotime('+1 year'));
?>

<style>
.bf-form-page { background: #f5f7fa; min-height: 100vh; }
.bf-form-shell { max-width: 600px; margin: 0 auto; }

.bf-form-card {
    background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.bf-form-card-title { font-size: 22px; font-weight: 700; color: #0f172a; margin: 0 0 4px; }
.bf-form-card-subtitle { font-size: 14px; color: #64748b; margin: 0 0 24px; }
.bf-form-kicker { font-size: 11px; font-weight: 600; color: var(--bf-green-dark); text-transform: uppercase; letter-spacing: 1px; margin: 0 0 8px; }

.bf-form-group { margin-bottom: 20px; }
.bf-form-label { display: block; margin-bottom: 8px; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
.bf-form-input {
    width: 100%; min-height: 48px; padding: 12px 16px; border: 1px solid #e2e8f0;
    border-radius: 10px; background: #ffffff; color: #0f172a; font-size: 15px;
    outline: none; transition: border-color 0.2s, box-shadow 0.2s;
}
.bf-form-input:focus { border-color: var(--bf-green-dark); box-shadow: 0 0 0 3px rgba(0,127,95,0.1); }
.bf-form-input.is-invalid { border-color: #e11d48; }
.bf-form-input::placeholder { color: #cbd5e1; }

.bf-form-error { font-size: 12px; color: #e11d48; margin-top: 6px; display: flex; align-items: center; gap: 4px; }

.bf-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

.bf-type-toggle-group { display: flex; gap: 12px; }
.bf-type-toggle-btn {
    flex: 1; padding: 14px; border: 2px solid #e2e8f0; border-radius: 12px;
    background: #ffffff; cursor: pointer; text-align: center; font-size: 14px;
    font-weight: 600; color: #94a3b8; display: flex; align-items: center;
    justify-content: center; gap: 8px; transition: all 0.2s;
}
.bf-type-toggle-btn:hover { border-color: #cbd5e1; }
.bf-type-toggle-btn.active { border-color: var(--bf-green-dark); background: rgba(0,127,95,0.06); color: var(--bf-green-dark); }

.bf-form-input-wrap { position: relative; }
.bf-form-input-prefix {
    position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
    color: #94a3b8; font-size: 15px; font-weight: 600; pointer-events: none;
}
.bf-form-input-wrap .bf-form-input { padding-left: 44px; }

.bf-form-submit {
    width: 100%; padding: 14px; background: var(--bf-sidebar); color: #fff; border: none;
    border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer;
    transition: all 0.2s; font-family: var(--bf-font-ui);
}
.bf-form-submit:hover { background: #003d4d; transform: translateY(-1px); }

.bf-form-back { text-align: center; margin-top: 16px; }
.bf-form-back a { color: #64748b; text-decoration: none; font-size: 14px; font-weight: 500; }
.bf-form-back a:hover { color: #0f172a; }

.bf-form-alert {
    padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 13px;
    display: flex; align-items: center; gap: 10px;
}
.bf-form-alert.danger { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }

@media (max-width: 768px) { .bf-form-row { grid-template-columns: 1fr; } }
</style>

<div class="bf-form-page">
    <div class="bf-form-shell">
        <div class="bf-form-card">
            <p class="bf-form-kicker">Budget</p>
            <h2 class="bf-form-card-title"><?= $mode === 'edit' ? 'Modifier le budget' : 'Nouveau budget' ?></h2>
            <p class="bf-form-card-subtitle"><?= $mode === 'edit' ? 'Modifiez les paramètres de votre budget.' : 'Créez un nouveau budget pour suivre vos dépenses.' ?></p>

            <?php if (!empty($flashDanger)): ?>
            <div class="bf-form-alert danger"><i class="bi bi-x-circle"></i> <?= $e($flashDanger) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors['form'])): ?>
            <div class="bf-form-alert danger"><i class="bi bi-x-circle"></i> <?= $e($errors['form']) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= $e($action) ?>" novalidate>
                <?= CSRF::getTokenField() ?>
                <?php if ($mode === 'edit'): ?>
                <input type="hidden" name="id" value="<?= $e((int) ($budget['id'] ?? 0)) ?>">
                <?php endif; ?>
                <input type="hidden" id="budget-type" name="type" value="<?= $e($selectedType) ?>">

                <!-- Nom -->
                <div class="bf-form-group">
                    <label class="bf-form-label" for="budget-name">Nom du budget</label>
                    <input class="bf-form-input <?= !empty($errors['name']) ? 'is-invalid' : '' ?>" id="budget-name" type="text" name="name" maxlength="100"
                        value="<?= $e($nameValue) ?>" placeholder="Ex : Alimentation, Transport…" required>
                    <?php if (!empty($errors['name'])): ?>
                    <div class="bf-form-error"><i class="bi bi-exclamation-circle"></i> <?= $e($errors['name']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Type -->
                <div class="bf-form-group">
                    <label class="bf-form-label">Type de budget</label>
                    <div class="bf-type-toggle-group">
                        <button class="bf-type-toggle-btn <?= $selectedType === 'personal' ? 'active' : '' ?>" type="button" data-budget-type="personal">
                            <i class="bi bi-person" style="font-size:18px"></i> Personnel
                        </button>
                        <button class="bf-type-toggle-btn <?= $selectedType === 'shared' ? 'active' : '' ?>" type="button" data-budget-type="shared">
                            <i class="bi bi-people" style="font-size:18px"></i> Partagé
                        </button>
                    </div>
                </div>

                <!-- Période + Date -->
                <div class="bf-form-row">
                    <div class="bf-form-group">
                        <label class="bf-form-label" for="budget-period">Période</label>
                        <select class="bf-form-input" id="budget-period" name="period" style="cursor:pointer">
                            <option value="monthly" <?= $selectedPeriod === 'monthly' ? 'selected' : '' ?>>Mensuel</option>
                            <option value="weekly" <?= $selectedPeriod === 'weekly' ? 'selected' : '' ?>>Hebdomadaire</option>
                            <option value="custom" <?= $selectedPeriod === 'custom' ? 'selected' : '' ?>>Personnalisé</option>
                        </select>
                    </div>
                    <div class="bf-form-group" id="start-date-group" style="display: <?= $selectedPeriod === 'custom' ? 'block' : 'none' ?>">
                        <label class="bf-form-label" for="budget-start">Date de début</label>
                        <input class="bf-form-input" id="budget-start" type="date" name="start_date"
                            min="<?= $e($minDate) ?>" max="<?= $e($maxDate) ?>" value="<?= $e($startDateValue) ?>">
                    </div>
                </div>

                <!-- Limite -->
                <div class="bf-form-group">
                    <label class="bf-form-label" for="budget-limit">
                        Limite du budget <span style="color:#94a3b8;font-weight:400">(optionnel)</span>
                    </label>
                    <div class="bf-form-input-wrap">
                        <span class="bf-form-input-prefix">DT</span>
                        <input class="bf-form-input <?= !empty($errors['amount_limit']) ? 'is-invalid' : '' ?>" id="budget-limit" type="number" step="1" min="0" name="amount_limit"
                            value="<?= $e($amountLimitValue) ?>" placeholder="Pas de limite">
                    </div>
                    <?php if (!empty($errors['amount_limit'])): ?>
                    <div class="bf-form-error"><i class="bi bi-exclamation-circle"></i> <?= $e($errors['amount_limit']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="bf-form-submit">
                    <?= $mode === 'edit' ? 'Enregistrer les modifications' : 'Créer le budget' ?>
                </button>
            </form>

            <div class="bf-form-back">
                <a href="/budgets"><i class="bi bi-arrow-left" style="margin-right:4px"></i> Retour aux budgets</a>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('[data-budget-type]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var type = this.getAttribute('data-budget-type');
        document.getElementById('budget-type').value = type;
        document.querySelectorAll('[data-budget-type]').forEach(function(b) { b.classList.remove('active'); });
        this.classList.add('active');
    });
});

document.getElementById('budget-period').addEventListener('change', function() {
    document.getElementById('start-date-group').style.display = this.value === 'custom' ? 'block' : 'none';
});
</script>
