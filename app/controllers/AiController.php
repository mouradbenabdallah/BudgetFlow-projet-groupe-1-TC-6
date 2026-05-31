<?php

declare(strict_types=1);

/**
 * AI Controller
 *
 * Handles the conversational AI endpoint backed by a local Ollama instance.
 * Injects the current user's financial context into every request so the
 * model can answer budget-aware questions.
 *
 * Route (public/index.php):
 *   POST /api/chat → chat()
 *
 * Security:
 *   - Requires an active user session (Auth::requireLogin).
 *   - Admins are blocked at the HTTP level (403).
 *   - No user-supplied data is stored; history is kept client-side only.
 */
class AiController
{
    /**
     * POST /api/chat
     *
     * Reads a JSON body with:
     *   message  string   — the user's question
     *   history  array    — previous turns [{ role, content }, ...]
     *
     * Returns JSON: { response: string } or { error: string }.
     */
    public function chat(): void
    {
        Auth::requireLogin();

        // Admins have no personal financial data and are explicitly excluded.
        if (($_SESSION['role'] ?? '') === 'admin') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Non autorisé']);
            return;
        }

        $input   = json_decode(file_get_contents('php://input'), true) ?? [];
        $message = trim((string) ($input['message'] ?? ''));
        $history = is_array($input['history'] ?? null) ? $input['history'] : [];

        if ($message === '') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Message vide']);
            return;
        }

        $userId  = (int) ($_SESSION['user_id'] ?? 0);
        $context = $this->getUserFinancialContext($userId);
        $reply   = $this->callOllama($message, $context, $history);

        header('Content-Type: application/json');
        echo json_encode(['response' => $reply]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Build a plain-text financial summary for the current user.
     * Used as the system context injected into every Ollama request.
     *
     * @param int $userId
     * @return string Multi-line context block
     */
    private function getUserFinancialContext(int $userId): string
    {
        $pdo        = Database::getInstance();
        $startMonth = date('Y-m-01');
        $endMonth   = date('Y-m-t');

        // Monthly income / expense totals
        $stmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN t.type = 'income'  THEN t.amount ELSE 0 END), 0) AS income,
                COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) AS expense
            FROM transactions t
            JOIN budgets b ON b.id = t.budget_id
            WHERE (b.owner_id = :uid OR EXISTS (
                SELECT 1 FROM budget_members bm
                WHERE bm.budget_id = b.id AND bm.user_id = :uid2
            ))
            AND t.date BETWEEN :start AND :end
        ");
        $stmt->execute([':uid' => $userId, ':uid2' => $userId, ':start' => $startMonth, ':end' => $endMonth]);
        $monthly = $stmt->fetch() ?: ['income' => 0, 'expense' => 0];

        // Top 3 expense categories this month
        $stmt = $pdo->prepare("
            SELECT c.name, COALESCE(SUM(t.amount), 0) AS total
            FROM transactions t
            JOIN categories c ON c.id = t.category_id
            JOIN budgets b    ON b.id = t.budget_id
            WHERE (b.owner_id = :uid OR EXISTS (
                SELECT 1 FROM budget_members bm
                WHERE bm.budget_id = b.id AND bm.user_id = :uid2
            ))
            AND t.type = 'expense'
            AND t.date BETWEEN :start AND :end
            GROUP BY c.name
            ORDER BY total DESC
            LIMIT 3
        ");
        $stmt->execute([':uid' => $userId, ':uid2' => $userId, ':start' => $startMonth, ':end' => $endMonth]);
        $categories = $stmt->fetchAll();

        // Active budgets with spending progress
        $stmt = $pdo->prepare("
            SELECT b.name, b.amount_limit,
                   COALESCE(SUM(t.amount) FILTER (WHERE t.type = 'expense'), 0) AS spent
            FROM budgets b
            LEFT JOIN transactions  t  ON t.budget_id  = b.id
            LEFT JOIN budget_members bm ON bm.budget_id = b.id
            WHERE (b.owner_id = :uid OR bm.user_id = :uid2)
            GROUP BY b.id, b.name, b.amount_limit
        ");
        $stmt->execute([':uid' => $userId, ':uid2' => $userId]);
        $budgets = $stmt->fetchAll();

        // Build context string
        $income  = number_format((float) $monthly['income'],  2, ',', ' ');
        $expense = number_format((float) $monthly['expense'], 2, ',', ' ');
        $balance = number_format((float) $monthly['income'] - (float) $monthly['expense'], 2, ',', ' ');

        $ctx  = "Contexte financier de l'utilisateur ce mois (" . date('F Y') . ") :\n";
        $ctx .= "- Revenus  : {$income} TND\n";
        $ctx .= "- Dépenses : {$expense} TND\n";
        $ctx .= "- Solde    : {$balance} TND\n\n";

        if (!empty($categories)) {
            $ctx .= "Top catégories de dépenses :\n";
            foreach ($categories as $cat) {
                $total = number_format((float) $cat['total'], 2, ',', ' ');
                $ctx .= "- {$cat['name']} : {$total} TND\n";
            }
            $ctx .= "\n";
        }

        if (!empty($budgets)) {
            $ctx .= "État des budgets :\n";
            foreach ($budgets as $b) {
                $spent = number_format((float) $b['spent'], 2, ',', ' ');
                if ((float) ($b['amount_limit'] ?? 0) > 0) {
                    $percent = round(((float) $b['spent'] / (float) $b['amount_limit']) * 100);
                    $limit   = number_format((float) $b['amount_limit'], 2, ',', ' ');
                    $ctx .= "- {$b['name']} : {$spent} TND / {$limit} TND ({$percent}%)\n";
                } else {
                    $ctx .= "- {$b['name']} : {$spent} TND dépensés (sans plafond)\n";
                }
            }
        }

        return $ctx;
    }

    /**
     * Call the Ollama /api/chat endpoint and return the assistant's reply.
     *
     * @param string               $message New user message
     * @param string               $context Financial context injected as system prompt
     * @param array<array<string, string>> $history Previous conversation turns
     * @return string Assistant reply text
     */
    private function callOllama(string $message, string $context, array $history): string
    {
        $config      = require __DIR__ . '/../../config/config.php';
        $ollamaHost  = rtrim((string) ($config['ollama']['host']  ?? 'http://ollama:11434'), '/');
        $ollamaModel = (string) ($config['ollama']['model'] ?? 'llama3.2:1b');

        // System prompt — kept concise so the small 1B model doesn't get confused.
        $messages = [[
            'role'    => 'system',
            'content' => "Tu es un assistant financier intégré dans BudgetFlow. "
                       . "Réponds toujours en français, de façon claire et concise. "
                       . "Voici le contexte financier actuel de l'utilisateur :\n\n{$context}",
        ]];

        // Append sanitised conversation history (last 6 turns max)
        foreach (array_slice($history, -6) as $turn) {
            $role    = (string) ($turn['role']    ?? '');
            $content = (string) ($turn['content'] ?? '');
            if (in_array($role, ['user', 'assistant'], true) && $content !== '') {
                $messages[] = ['role' => $role, 'content' => $content];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        $payload = json_encode([
            'model'    => $ollamaModel,
            'messages' => $messages,
            'stream'   => false,
            'options'  => ['temperature' => 0.7, 'num_predict' => 500],
        ]);

        $ch = curl_init("{$ollamaHost}/api/chat");
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);

        $result = curl_exec($ch);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($error !== '') {
            error_log('[AiController] Ollama curl error: ' . $error);
            return "Désolé, je ne peux pas répondre pour le moment. Vérifiez que le service IA est démarré.";
        }

        $data = json_decode((string) $result, true);
        return (string) ($data['message']['content']
            ?? "Désolé, je n'ai pas pu traiter votre demande.");
    }
}
