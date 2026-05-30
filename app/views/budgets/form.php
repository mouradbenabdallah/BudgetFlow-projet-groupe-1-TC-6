<?php
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$budget = $budget ?? [];
$errors = $errors ?? [];
$mode = ($mode ?? 'create') === 'edit' ? 'edit' : 'create';
$action = (string) ($action ?? ($mode === 'edit' ? '/budgets/edit' : '/budgets/create'));

$nameValue       = (string) ($budget['name'] ?? '');
$selectedType    = (string) ($budget['type'] ?? 'personal');
$selectedPeriod  = (string) ($budget['period'] ?? 'monthly');
$amountLimitValue = ($budget['amount_limit'] ?? null) !== null ? number_format((float) $budget['amount_limit'], 2, '.', '') : '';
$startDateValue  = (string) ($budget['start_date'] ?? date('Y-m-d'));
$minDate = '2000-01-01';
$maxDate = date('Y-m-d', strtotime('+1 year'));
?>

<!-- Dark overlay -->
<div style="min-height:calc(100vh - 64px);display:flex;align-items:center;justify-content:center;padding:32px 24px;background:rgba(0,30,43,0.7)">

<div style="width:100%;max-width:480px;background:#1c2d38;border:1px solid #3d4f58;border-radius:16px;overflow:hidden;box-shadow:rgba(0,30,43,0.5) 0px 24px 60px">

    <!-- Modal header -->
    <div style="display:flex;align-items:center;justify-content:space-between;padding:24px 28px;border-bottom:1px solid #3d4f58">
        <div>
            <span style="font-family:'Source Code Pro',monospace;font-size:10px;text-transform:uppercase;letter-spacing:2px;color:#00ed64;display:block;margin-bottom:4px">
                <?= $mode === 'edit' ? 'Modifier' : 'Nouveau' ?> Budget
            </span>
            <h2 style="font-size:20px;font-weight:600;color:#fff;margin:0">
                <?= $mode === 'edit' ? 'Modifier le budget' : 'Créer un budget' ?>
            </h2>
        </div>
        <a href="/budgets" style="width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,0.05);display:flex;align-items:center;justify-content:center;color:#5c6c75;text-decoration:none">
            <i class="bi bi-x-lg" style="font-size:15px"></i>
        </a>
    </div>

    <!-- Form body -->
    <form method="post" action="<?= $e($action) ?>" novalidate>
        <?= CSRF::getTokenField() ?>
        <?php if ($mode === 'edit'): ?>
        <input type="hidden" name="id" value="<?= $e((int) ($budget['id'] ?? 0)) ?>">
        <?php endif; ?>
        <input type="hidden" id="budget-type" name="type" value="<?= $e($selectedType) ?>">

        <div style="padding:24px 28px;display:flex;flex-direction:column;gap:20px">

            <?php if (!empty($flashDanger) || !empty($errors['form'])): ?>
            <div style="display:flex;align-items:center;gap:8px;padding:12px;background:rgba(225,29,72,0.1);border:1px solid rgba(225,29,72,0.3);border-radius:8px;font-size:13px;color:#e11d48">
                <i class="bi bi-exclamation-circle" style="flex-shrink:0"></i>
                <?= $e($flashDanger ?: $errors['form']) ?>
            </div>
            <?php endif; ?>

            <!-- Nom -->
            <div>
                <label style="display:block;font-family:'Source Code Pro',monospace;font-size:10px;text-transform:uppercase;letter-spacing:1.5px;color:#5c6c75;margin-bottom:8px">
                    Nom du budget
                </label>
                <input
                    type="text" name="name" id="budget-name" maxlength="100"
                    value="<?= $e($nameValue) ?>"
                    placeholder="Ex : Alimentation, Transport…"
                    required
                    style="width:100%;background:#001e2b;border:1px solid <?= !empty($errors['name']) ? '#e11d48' : '#3d4f58' ?>;border-radius:8px;padding:11px 14px;color:#e8edeb;font-size:14px;outline:none;font-family:'Plus Jakarta Sans',sans-serif;transition:border-color .2s"
                    onfocus="this.style.borderColor='#00684a'" onblur="this.style.borderColor='<?= !empty($errors['name']) ? '#e11d48' : '#3d4f58' ?>'"
                >
                <?php if (!empty($errors['name'])): ?>
                <p style="font-size:12px;color:#e11d48;margin-top:4px"><?= $e($errors['name']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Type -->
            <div>
                <label style="display:block;font-family:'Source Code Pro',monospace;font-size:10px;text-transform:uppercase;letter-spacing:1.5px;color:#5c6c75;margin-bottom:8px">
                    Type de budget
                </label>
                <div style="display:flex;gap:8px">
                    <?php foreach ([['personal','bi-person-fill','Personnel'],['shared','bi-people-fill','Partagé']] as [$val,$icon,$lbl]): ?>
                    <button
                        type="button"
                        data-budget-type="<?= $val ?>"
                        style="flex:1;padding:10px 12px;border-radius:100px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .2s;
                            background:<?= $selectedType === $val ? '#001e2b' : 'transparent' ?>;
                            color:<?= $selectedType === $val ? '#00ed64' : '#5c6c75' ?>;
                            border:1px solid <?= $selectedType === $val ? '#00684a' : '#3d4f58' ?>"
                    >
                        <i class="bi <?= $icon ?>"></i> <?= $lbl ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Période -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div>
                    <label style="display:block;font-family:'Source Code Pro',monospace;font-size:10px;text-transform:uppercase;letter-spacing:1.5px;color:#5c6c75;margin-bottom:8px">
                        Période
                    </label>
                    <select
                        id="budget-period" name="period"
                        style="width:100%;background:#001e2b;border:1px solid #3d4f58;border-radius:8px;padding:11px 14px;color:#e8edeb;font-size:14px;outline:none;cursor:pointer"
                        onfocus="this.style.borderColor='#00684a'" onblur="this.style.borderColor='#3d4f58'"
                    >
                        <option value="monthly" <?= $selectedPeriod === 'monthly' ? 'selected' : '' ?> style="background:#1c2d38">Mensuel</option>
                        <option value="weekly"  <?= $selectedPeriod === 'weekly'  ? 'selected' : '' ?> style="background:#1c2d38">Hebdomadaire</option>
                        <option value="custom"  <?= $selectedPeriod === 'custom'  ? 'selected' : '' ?> style="background:#1c2d38">Personnalisé</option>
                    </select>
                </div>
                <div id="start-date-group" style="display:<?= $selectedPeriod === 'custom' ? 'block' : 'none' ?>">
                    <label style="display:block;font-family:'Source Code Pro',monospace;font-size:10px;text-transform:uppercase;letter-spacing:1.5px;color:#5c6c75;margin-bottom:8px">
                        Date de début
                    </label>
                    <input
                        type="date" id="budget-start" name="start_date"
                        min="<?= $e($minDate) ?>" max="<?= $e($maxDate) ?>"
                        value="<?= $e($startDateValue) ?>"
                        style="width:100%;background:#001e2b;border:1px solid #3d4f58;border-radius:8px;padding:11px 14px;color:#e8edeb;font-size:14px;outline:none;color-scheme:dark"
                        onfocus="this.style.borderColor='#00684a'" onblur="this.style.borderColor='#3d4f58'"
                    >
                </div>
            </div>

            <!-- Limite -->
            <div>
                <label style="display:block;font-family:'Source Code Pro',monospace;font-size:10px;text-transform:uppercase;letter-spacing:1.5px;color:#5c6c75;margin-bottom:8px">
                    Limite du budget <span style="text-transform:none;letter-spacing:0;font-family:'Plus Jakarta Sans',sans-serif;color:#3d4f58">(optionnel)</span>
                </label>
                <div style="position:relative;display:flex;align-items:center">
                    <span style="position:absolute;left:14px;font-size:13px;font-weight:700;color:#3d4f58;pointer-events:none;z-index:1">DT</span>
                    <input
                        type="number" name="amount_limit" id="budget-limit"
                        step="1" min="0"
                        value="<?= $e($amountLimitValue) ?>"
                        placeholder="Pas de limite"
                        style="width:100%;background:#001e2b;border:1px solid <?= !empty($errors['amount_limit']) ? '#e11d48' : '#3d4f58' ?>;border-radius:8px;padding:11px 14px 11px 40px;color:#e8edeb;font-size:14px;outline:none;font-family:'Plus Jakarta Sans',sans-serif;transition:border-color .2s"
                        onfocus="this.style.borderColor='#00684a'" onblur="this.style.borderColor='<?= !empty($errors['amount_limit']) ? '#e11d48' : '#3d4f58' ?>'"
                    >
                </div>
                <?php if (!empty($errors['amount_limit'])): ?>
                <p style="font-size:12px;color:#e11d48;margin-top:4px"><?= $e($errors['amount_limit']) ?></p>
                <?php endif; ?>
            </div>

        </div><!-- /body -->

        <!-- Footer buttons -->
        <div style="display:flex;gap:12px;padding:20px 28px;border-top:1px solid #3d4f58">
            <a href="/budgets"
               style="flex:1;padding:11px;border-radius:100px;border:1px solid #3d4f58;color:#b8c4c2;font-size:14px;text-align:center;text-decoration:none;font-weight:500;transition:border-color .15s;display:block"
               onmouseover="this.style.borderColor='#5c6c75'" onmouseout="this.style.borderColor='#3d4f58'"
            >
                Annuler
            </a>
            <button
                type="submit"
                style="flex:1;padding:11px;border-radius:100px;background:#00684a;color:#fff;border:none;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:opacity .2s,transform .15s"
                onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'"
            >
                <i class="bi bi-check-lg"></i>
                <?= $mode === 'edit' ? 'Enregistrer' : 'Créer le budget' ?>
            </button>
        </div>

    </form>
</div>
</div>

<script>
document.querySelectorAll('[data-budget-type]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var type = this.getAttribute('data-budget-type');
        document.getElementById('budget-type').value = type;
        document.querySelectorAll('[data-budget-type]').forEach(function(b) {
            b.style.background = 'transparent';
            b.style.color = '#5c6c75';
            b.style.borderColor = '#3d4f58';
        });
        this.style.background = '#001e2b';
        this.style.color = '#00ed64';
        this.style.borderColor = '#00684a';
    });
});
document.getElementById('budget-period').addEventListener('change', function() {
    var g = document.getElementById('start-date-group');
    g.style.display = this.value === 'custom' ? 'block' : 'none';
});
</script>
