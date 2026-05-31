<?php

declare(strict_types=1);

class RapportController
{
    public function index(): void
    {
        Auth::requireRole('user');
        $session     = new Session();
        $flashDanger = $session->getFlash('danger');
        require __DIR__ . '/../views/rapport/index.php';
    }

    public function generer(): void
    {
        Auth::requireRole('user');

        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            (new Session())->setFlash('danger', 'Token invalide.');
            header('Location: /rapport');
            exit;
        }

        $userId   = (int) ($_SESSION['user_id'] ?? 0);
        $type     = in_array($_POST['type'] ?? '', ['mensuel', 'annuel'], true) ? $_POST['type'] : 'mensuel';
        $sections = $_POST['sections'] ?? [];

        $moisFr = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];

        if ($type === 'mensuel') {
            $mois = $_POST['mois'] ?? date('Y-m');
            if (!preg_match('/^\d{4}-\d{2}$/', $mois)) {
                $mois = date('Y-m');
            }
            $startDate = $mois . '-01';
            $endDate   = date('Y-m-t', strtotime($startDate));
            $moisNum   = (int) substr($mois, 5, 2);
            $anneeNum  = (int) substr($mois, 0, 4);
            $periode   = $moisFr[$moisNum - 1] . ' ' . $anneeNum;
        } else {
            $annee = (int) ($_POST['annee'] ?? date('Y'));
            if ($annee < 2000 || $annee > 2100) {
                $annee = (int) date('Y');
            }
            $startDate = $annee . '-01-01';
            $endDate   = $annee . '-12-31';
            $periode   = "Année {$annee}";
        }

        $data = $this->getData($userId, $startDate, $endDate, $sections);

        // Rendu HTML full-page optimisé impression
        require_once __DIR__ . '/../views/rapport/print.php';
        exit;
    }

    public function getData(int $userId, string $start, string $end, array $sections): array
    {
        $pdo  = Database::getInstance();
        $user = (new User())->findById($userId);
        $data = ['user' => $user ?? ['name' => 'Utilisateur']];

        if (in_array('stats', $sections, true)) {
            $stmt = $pdo->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN t.type='income'  THEN t.amount ELSE 0 END), 0) AS total_income,
                    COALESCE(SUM(CASE WHEN t.type='expense' THEN t.amount ELSE 0 END), 0) AS total_expense,
                    COUNT(*) AS nb_transactions
                FROM transactions t
                JOIN budgets b ON b.id = t.budget_id
                LEFT JOIN budget_members bm ON bm.budget_id = b.id
                WHERE (b.owner_id = :uid OR bm.user_id = :uid2)
                  AND t.date BETWEEN :start AND :end
            ");
            $stmt->execute([':uid' => $userId, ':uid2' => $userId, ':start' => $start, ':end' => $end]);
            $row = $stmt->fetch();
            $data['stats'] = $row;
            $data['stats']['balance'] = (float) $row['total_income'] - (float) $row['total_expense'];
        }

        if (in_array('transactions', $sections, true)) {
            $stmt = $pdo->prepare("
                SELECT t.date, t.type, t.amount, t.description,
                       c.name AS category_name, c.color AS category_color,
                       b.name AS budget_name
                FROM transactions t
                JOIN budgets b ON b.id = t.budget_id
                LEFT JOIN categories c ON c.id = t.category_id
                LEFT JOIN budget_members bm ON bm.budget_id = b.id
                WHERE (b.owner_id = :uid OR bm.user_id = :uid2)
                  AND t.date BETWEEN :start AND :end
                ORDER BY t.date DESC
            ");
            $stmt->execute([':uid' => $userId, ':uid2' => $userId, ':start' => $start, ':end' => $end]);
            $data['transactions'] = $stmt->fetchAll();
        }

        if (in_array('budgets', $sections, true)) {
            $stmt = $pdo->prepare("
                SELECT b.name, b.type, b.amount_limit, b.period,
                       COALESCE(SUM(CASE WHEN t.type='expense' THEN t.amount ELSE 0 END), 0) AS spent,
                       COALESCE(SUM(CASE WHEN t.type='income'  THEN t.amount ELSE 0 END), 0) AS income
                FROM budgets b
                LEFT JOIN transactions t ON t.budget_id = b.id
                    AND t.date BETWEEN :start AND :end
                LEFT JOIN budget_members bm ON bm.budget_id = b.id
                WHERE (b.owner_id = :uid OR bm.user_id = :uid2)
                GROUP BY b.id, b.name, b.type, b.amount_limit, b.period
                ORDER BY b.created_at DESC
            ");
            $stmt->execute([':uid' => $userId, ':uid2' => $userId, ':start' => $start, ':end' => $end]);
            $data['budgets'] = $stmt->fetchAll();
        }

        if (in_array('categories', $sections, true)) {
            $stmt = $pdo->prepare("
                SELECT c.name, c.color,
                       COALESCE(SUM(t.amount), 0) AS total,
                       COUNT(t.id) AS nb
                FROM transactions t
                JOIN categories c ON c.id = t.category_id
                JOIN budgets b ON b.id = t.budget_id
                WHERE (b.owner_id = :uid OR EXISTS (
                    SELECT 1 FROM budget_members bm
                    WHERE bm.budget_id = b.id AND bm.user_id = :uid2
                ))
                  AND t.type = 'expense'
                  AND t.date BETWEEN :start AND :end
                GROUP BY c.id, c.name, c.color
                ORDER BY total DESC
            ");
            $stmt->execute([':uid' => $userId, ':uid2' => $userId, ':start' => $start, ':end' => $end]);
            $data['categories'] = $stmt->fetchAll();
        }

        return $data;
    }
}
