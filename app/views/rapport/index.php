<?php
$currentYear = (int) date('Y');
$moisFr = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
ob_start();
?>

<h1 class="c2c-page-title">Générer un Rapport PDF</h1>
<p class="c2c-page-subtitle">Exportez vos données financières en PDF</p>

<?php if (!empty($flashDanger)): ?>
    <div class="c2c-alert c2c-alert-danger mb-4"><?= htmlspecialchars((string)$flashDanger, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="c2c-card">
            <h5 style="font-size:17px;font-weight:700;color:var(--accent-navy);margin-bottom:20px;">
                Paramètres du rapport
            </h5>

            <form method="POST" action="/rapport/generer">
                <?= CSRF::getTokenField() ?>

                <!-- Type -->
                <div class="mb-4">
                    <label class="form-label fw-semibold"
                           style="color:var(--text-secondary);font-size:12px;text-transform:uppercase;letter-spacing:.06em;">
                        Type de rapport
                    </label>
                    <div class="d-flex gap-3">
                        <label class="rapport-type-card flex-fill text-center p-3 rounded-3 active-type"
                               id="card-mensuel"
                               style="cursor:pointer;border:2px solid var(--border);background:var(--bg-elevated);transition:all .2s;">
                            <input type="radio" name="type" value="mensuel" class="d-none" checked>
                            <div style="margin-bottom:8px;">
                                <i class="bi bi-calendar3" style="font-size:26px;color:#22D3A5;"></i>
                            </div>
                            <div class="fw-semibold" style="font-size:14px;">Mensuel</div>
                            <div style="color:var(--text-secondary);font-size:12px;">1 mois précis</div>
                        </label>
                        <label class="rapport-type-card flex-fill text-center p-3 rounded-3"
                               id="card-annuel"
                               style="cursor:pointer;border:2px solid var(--border);background:var(--bg-elevated);transition:all .2s;">
                            <input type="radio" name="type" value="annuel" class="d-none">
                            <div style="margin-bottom:8px;">
                                <i class="bi bi-calendar-range" style="font-size:26px;color:#22D3A5;"></i>
                            </div>
                            <div class="fw-semibold" style="font-size:14px;">Annuel</div>
                            <div style="color:var(--text-secondary);font-size:12px;">Année complète</div>
                        </label>
                    </div>
                </div>

                <!-- Période mensuelle -->
                <div class="mb-3" id="periode-mensuelle">
                    <label class="form-label fw-semibold"
                           style="color:var(--text-secondary);font-size:12px;text-transform:uppercase;letter-spacing:.06em;">
                        Mois
                    </label>
                    <select name="mois" class="c2c-input form-select">
                        <?php for ($m = 1; $m <= 12; $m++):
                            $val   = $currentYear . '-' . str_pad((string) $m, 2, '0', STR_PAD_LEFT);
                            $label = $moisFr[$m - 1] . ' ' . $currentYear;
                        ?>
                            <option value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>"
                                <?= ($m === (int) date('n')) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Période annuelle -->
                <div class="mb-3 d-none" id="periode-annuelle">
                    <label class="form-label fw-semibold"
                           style="color:var(--text-secondary);font-size:12px;text-transform:uppercase;letter-spacing:.06em;">
                        Année
                    </label>
                    <select name="annee" class="c2c-input form-select">
                        <?php for ($y = $currentYear; $y >= $currentYear - 4; $y--): ?>
                            <option value="<?= $y ?>" <?= ($y === $currentYear) ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Sections -->
                <div class="mb-5">
                    <label class="form-label fw-semibold"
                           style="color:var(--text-secondary);font-size:12px;text-transform:uppercase;letter-spacing:.06em;">
                        Sections à inclure
                    </label>
                    <div class="d-flex flex-column gap-2">
                        <?php
                        $sections = [
                            'stats'        => ['bi-bar-chart-fill', 'Statistiques générales'],
                            'transactions' => ['bi-credit-card',    'Transactions détaillées'],
                            'budgets'      => ['bi-wallet2',         'État des budgets'],
                            'categories'   => ['bi-tag',             'Répartition par catégorie'],
                        ];
                        foreach ($sections as $key => [$icon, $label]):
                        ?>
                        <label class="d-flex align-items-center gap-3 p-3 rounded-3"
                               style="cursor:pointer;background:var(--bg-elevated);border:1px solid var(--border);">
                            <input type="checkbox" name="sections[]"
                                   value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                                   checked class="form-check-input m-0"
                                   style="width:18px;height:18px;accent-color:#22D3A5;">
                            <i class="bi <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"
                               style="font-size:18px;color:#22D3A5;flex-shrink:0;"></i>
                            <span style="color:var(--text-primary);font-size:14px;font-weight:500;">
                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Bouton wavy -->
                <button type="submit" class="rapport-generate-btn w-100">
                    <img src="/animations/pdf.gif" alt="" class="rapport-btn-gif">
                    <span class="rapport-btn-text">Générer le Rapport PDF</span>
                </button>

            </form>
        </div>
    </div>
</div>

<style>
.rapport-type-card.active-type {
    border-color: var(--accent) !important;
    background: var(--accent-light) !important;
}

/* Bouton wavy PDF */
.rapport-generate-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    padding: 16px 32px;
    background: var(--accent-navy);
    color: #fff;
    border: 3px solid var(--accent);
    border-radius: 18px 6px 18px 6px / 6px 18px 6px 18px;
    font-weight: 700;
    font-size: 16px;
    cursor: pointer;
    box-shadow: 0 0 14px rgba(99,91,255,.35), 0 0 28px rgba(99,91,255,.15);
    animation: rapport-wavy 3s ease-in-out infinite;
    transition: transform .2s;
    letter-spacing: .01em;
}
.rapport-generate-btn:hover {
    transform: scale(1.03);
    box-shadow: 0 0 22px rgba(99,91,255,.6), 0 0 44px rgba(99,91,255,.25);
}
.rapport-btn-gif {
    width: 44px; height: 44px;
    object-fit: contain; flex-shrink: 0; border-radius: 8px;
}
.rapport-btn-text { flex-shrink: 0; }

@keyframes rapport-wavy {
    0%   { border-radius: 18px 6px 18px 6px / 6px 18px 6px 18px; box-shadow: 0 0 14px rgba(99,91,255,.35), 0 0 28px rgba(99,91,255,.15); }
    25%  { border-radius: 6px 18px 6px 18px / 18px 6px 18px 6px; box-shadow: 0 0 18px rgba(99,91,255,.5),  0 0 36px rgba(99,91,255,.2);  }
    50%  { border-radius: 18px 6px 18px 6px / 18px 6px 18px 6px; box-shadow: 0 0 24px rgba(99,91,255,.65), 0 0 48px rgba(99,91,255,.25); }
    75%  { border-radius: 6px 18px 6px 18px / 6px 18px 6px 18px; box-shadow: 0 0 18px rgba(99,91,255,.5),  0 0 36px rgba(99,91,255,.2);  }
    100% { border-radius: 18px 6px 18px 6px / 6px 18px 6px 18px; box-shadow: 0 0 14px rgba(99,91,255,.35), 0 0 28px rgba(99,91,255,.15); }
}
</style>

<script>
document.querySelectorAll('input[name="type"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        var isMensuel = this.value === 'mensuel';
        document.getElementById('periode-mensuelle').classList.toggle('d-none', !isMensuel);
        document.getElementById('periode-annuelle').classList.toggle('d-none',  isMensuel);
        document.querySelectorAll('.rapport-type-card').forEach(function(c) {
            c.classList.remove('active-type');
        });
        this.closest('.rapport-type-card').classList.add('active-type');
    });
});
</script>

<?php
$content   = ob_get_clean();
$pageTitle = 'Rapport PDF';
require __DIR__ . '/../layouts/app.php';
