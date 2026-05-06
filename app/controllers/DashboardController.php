<?php

declare(strict_types=1);

/**
 * Dashboard Controller
 * 
 * Renders the main user dashboard with financial summaries,
 * budget progress, recent transactions, and category breakdowns.
 * 
 * This controller handles the main dashboard view with:
 * - KPI cards (income, expenses, balance, saving rate)
 * - Budget progress bars
 * - Recent transactions list
 * - Category breakdown chart
 * - Monthly evolution chart
 */
class DashboardController
{
    private ?Budget $budgets = null;
    private ?Transaction $transactions = null;

    /**
     * Display the dashboard with aggregated financial data.
     * 
     * Gathers all dashboard data:
     * - Total income/expense for current month
     * - Budget spending progress
     * - Recent transactions (last 8)
     * - Category breakdown for current month
     * - Monthly evolution (last 6 months)
     */
    public function index(): void
    {
        Auth::requireRole('user');

        $user = Auth::getUser();
        $userId = (int) ($user['id'] ?? 0);

        // Current month period for cards and category breakdown
        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-t');

        // Calculate KPIs
        $totalIncome = $this->transactions()->sumByType($userId, 'income', $startOfMonth, $endOfMonth);
        $totalExpense = $this->transactions()->sumByType($userId, 'expense', $startOfMonth, $endOfMonth);

        // Build data array for the view
        $data = [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
            'budgets' => $this->buildBudgets($userId),
            'recent_transactions' => $this->transactions()->findByUser($userId, 8),
            'category_breakdown' => $this->transactions()->byCategory($userId, $startOfMonth, $endOfMonth),
            'monthly_evolution' => $this->buildMonthlyEvolution($userId),
        ];

        // Render the dashboard view
        $this->render('dashboard/index', [
            'title' => 'Tableau de bord',
            'pageTitle' => 'Tableau de bord',
            'user' => $user,
            'data' => $data,
        ]);
    }

    /**
     * Build budget summary data with spending percentages and status indicators.
     * 
     * For each budget, calculates:
     * - Spending percentage vs limit
     * - Status: ok (<80%), warning (80-99%), danger (>=100%)
     * 
     * @param int $userId The user ID
     * @return array<array<string, mixed>> Budget summary records
     */
    private function buildBudgets(int $userId): array
    {
        $budgets = [];

        foreach ($this->budgets()->findByUser($userId) as $budget) {
            $limit = $budget['amount_limit'] !== null ? (float) $budget['amount_limit'] : 0.0;
            $spent = (float) ($budget['spent'] ?? 0);
            $percent = $limit > 0 ? (int) round(($spent / $limit) * 100) : 0;
            $status = 'ok';

            if ($limit > 0 && $percent >= 100) {
                $status = 'danger';
            } elseif ($limit > 0 && $percent >= 80) {
                $status = 'warning';
            }

            $budgets[] = [
                'id' => (int) $budget['id'],
                'name' => (string) $budget['name'],
                'type' => (string) $budget['type'],
                'amount_limit' => $limit,
                'spent' => $spent,
                'percent' => $percent,
                'status' => $status,
            ];
        }

        return $budgets;
    }

    /**
     * Build monthly evolution data for the last 6 months.
     * 
     * Fills gaps with zero values for months without transactions.
     * Used to generate the monthly evolution chart.
     * 
     * @param int $userId The user ID
     * @return array<array<string, mixed>> Monthly evolution records with keys:
     *         - month: short month name (e.g., "Jan")
     *         - income: total income for that month
     *         - expense: total expense for that month
     */
    private function buildMonthlyEvolution(int $userId): array
    {
        $indexedRows = [];
        $monthLabels = [
            1 => 'Jan',
            2 => 'Fév',
            3 => 'Mar',
            4 => 'Avr',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juil',
            8 => 'Août',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Déc',
        ];

        // Index monthly data by month key (YYYY-MM)
        foreach ($this->transactions()->monthlyEvolution($userId) as $row) {
            $indexedRows[(string) $row['month_key']] = [
                'income' => (float) $row['income'],
                'expense' => (float) $row['expense'],
            ];
        }

        // Build last 6 months with zero-filled gaps
        $evolution = [];
        $currentMonth = date('Y-m-01');

        for ($i = 5; $i >= 0; $i--) {
            $timestamp = strtotime($currentMonth . ' -' . $i . ' months');
            $key = date('Y-m', $timestamp);
            $monthNumber = (int) date('n', $timestamp);

            $evolution[] = [
                'month' => $monthLabels[$monthNumber] ?? date('M', $timestamp),
                'income' => $indexedRows[$key]['income'] ?? 0.0,
                'expense' => $indexedRows[$key]['expense'] ?? 0.0,
            ];
        }

        return $evolution;
    }

    /**
     * Render a view within the authenticated layout.
     * 
     * Extracts data array into variables for the view,
     * captures the view output, and renders it within the app layout.
     * 
     * @param string $view View file path (relative to app/views/)
     * @param array<string, mixed> $data Variables to extract into the view
     */
    private function render(string $view, array $data = []): void
    {
        // Extract variables from data array
        foreach ($data as $key => $value) {
            $$key = $value;
        }

        ob_start();
        require __DIR__ . '/../views/' . $view . '.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layouts/app.php';
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
