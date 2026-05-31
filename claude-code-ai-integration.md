# MISSION — Intégration IA (Ollama) dans BudgetFlow
## Claude Code — PHP 8.3 natif + Bootstrap 5 + Docker + Ollama

---

## ÉTAPE 0 — Lire la documentation EN PREMIER

```
documentation/SKILL.md
documentation/design.md
database/schema.sql
img/              ← captures Figma pour le style du modal
```

---

## OBJECTIF

Ajouter un assistant IA conversationnel dans BudgetFlow :
- Bouton flottant animé sur toutes les pages UTILISATEUR uniquement
- Modal Bootstrap avec interface de chat
- Backend PHP qui appelle Ollama
- Ollama tourne dans Docker avec llama3.2:1b
- L'IA connaît le contexte financier de l'utilisateur connecté

---

## ÉTAPE 1 — Mettre à jour docker-compose.yml

Ajouter le service Ollama au fichier existant.
Ne pas modifier les services existants (app, nginx, db).
Ajouter UNIQUEMENT ce nouveau service :

```yaml
  # ── Ollama AI ────────────────────────────────────────────
  ollama:
    image: ollama/ollama:latest
    container_name: budgetflow_ollama
    restart: unless-stopped
    volumes:
      - ollama_data:/root/.ollama
    ports:
      - "11434:11434"
    networks:
      - budgetflow
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:11434/api/tags"]
      interval: 30s
      timeout: 10s
      retries: 5
      start_period: 60s
```

Ajouter aussi dans la section volumes :
```yaml
  ollama_data:
```

Et dans config/config.php ajouter :
```php
'ollama' => [
    'host'  => 'http://ollama:11434',  // nom du service Docker
    'model' => 'llama3.2:1b',
]
```

---

## ÉTAPE 2 — Script d'initialisation Ollama

Créer le fichier `docker/ollama-init.sh` :

```bash
#!/bin/bash
# Attendre qu'Ollama soit prêt
echo "⏳ Attente du service Ollama..."
until curl -sf http://ollama:11434/api/tags > /dev/null 2>&1; do
    sleep 3
done
echo "✅ Ollama est prêt."

# Télécharger le modèle si pas déjà présent
MODEL="llama3.2:1b"
if ! curl -sf http://ollama:11434/api/tags | grep -q "$MODEL"; then
    echo "📥 Téléchargement du modèle $MODEL..."
    curl -X POST http://ollama:11434/api/pull \
         -H "Content-Type: application/json" \
         -d "{\"name\": \"$MODEL\"}"
    echo "✅ Modèle $MODEL téléchargé."
else
    echo "✅ Modèle $MODEL déjà présent."
fi
```

---

## ÉTAPE 3 — Endpoint PHP /api/chat

Créer `app/controllers/AiController.php` :

```php
<?php
class AiController {

    /**
     * POST /api/chat
     * Reçoit un message utilisateur et retourne la réponse Ollama
     */
    public function chat(): void {
        // Vérifier que l'utilisateur est connecté
        Auth::requireLogin();

        // Vérifier que c'est un utilisateur (pas admin)
        if ($_SESSION['role'] === 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Non autorisé']);
            exit;
        }

        // Lire le message depuis le body JSON
        $input = json_decode(file_get_contents('php://input'), true);
        $message = trim($input['message'] ?? '');
        $history = $input['history'] ?? [];

        if (empty($message)) {
            http_response_code(400);
            echo json_encode(['error' => 'Message vide']);
            exit;
        }

        // Construire le contexte financier de l'utilisateur
        $context = $this->getUserFinancialContext($_SESSION['user_id']);

        // Appeler Ollama
        $response = $this->callOllama($message, $context, $history);

        header('Content-Type: application/json');
        echo json_encode(['response' => $response]);
    }

    /**
     * Récupérer le contexte financier de l'utilisateur
     */
    private function getUserFinancialContext(int $userId): string {
        $pdo = Database::getInstance();

        // Solde du mois en cours
        $startMonth = date('Y-m-01');
        $endMonth   = date('Y-m-t');

        $stmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN t.type='income'  THEN t.amount ELSE 0 END), 0) as income,
                COALESCE(SUM(CASE WHEN t.type='expense' THEN t.amount ELSE 0 END), 0) as expense
            FROM transactions t
            JOIN budgets b ON b.id = t.budget_id
            WHERE (b.owner_id = :uid OR EXISTS (
                SELECT 1 FROM budget_members bm
                WHERE bm.budget_id = b.id AND bm.user_id = :uid2
            ))
            AND t.date BETWEEN :start AND :end
        ");
        $stmt->execute([
            ':uid'   => $userId,
            ':uid2'  => $userId,
            ':start' => $startMonth,
            ':end'   => $endMonth
        ]);
        $monthly = $stmt->fetch();

        // Top 3 catégories de dépenses
        $stmt = $pdo->prepare("
            SELECT c.name, COALESCE(SUM(t.amount), 0) as total
            FROM transactions t
            JOIN categories c ON c.id = t.category_id
            JOIN budgets b ON b.id = t.budget_id
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
        $stmt->execute([
            ':uid'   => $userId,
            ':uid2'  => $userId,
            ':start' => $startMonth,
            ':end'   => $endMonth
        ]);
        $categories = $stmt->fetchAll();

        // Budgets actifs et leur état
        $stmt = $pdo->prepare("
            SELECT b.name, b.amount_limit,
                   COALESCE(SUM(t.amount) FILTER (WHERE t.type='expense'), 0) as spent
            FROM budgets b
            LEFT JOIN transactions t ON t.budget_id = b.id
            LEFT JOIN budget_members bm ON bm.budget_id = b.id
            WHERE (b.owner_id = :uid OR bm.user_id = :uid2)
            GROUP BY b.id, b.name, b.amount_limit
        ");
        $stmt->execute([':uid' => $userId, ':uid2' => $userId]);
        $budgets = $stmt->fetchAll();

        // Construire le texte de contexte
        $income  = number_format($monthly['income'],  2, ',', ' ');
        $expense = number_format($monthly['expense'], 2, ',', ' ');
        $balance = number_format($monthly['income'] - $monthly['expense'], 2, ',', ' ');

        $context = "Contexte financier de l'utilisateur ce mois (" . date('F Y') . ") :\n";
        $context .= "- Revenus : {$income} €\n";
        $context .= "- Dépenses : {$expense} €\n";
        $context .= "- Solde : {$balance} €\n\n";

        if (!empty($categories)) {
            $context .= "Top catégories de dépenses :\n";
            foreach ($categories as $cat) {
                $total = number_format($cat['total'], 2, ',', ' ');
                $context .= "- {$cat['name']} : {$total} €\n";
            }
            $context .= "\n";
        }

        if (!empty($budgets)) {
            $context .= "État des budgets :\n";
            foreach ($budgets as $b) {
                if ($b['amount_limit'] > 0) {
                    $percent = round(($b['spent'] / $b['amount_limit']) * 100);
                    $spent   = number_format($b['spent'], 2, ',', ' ');
                    $limit   = number_format($b['amount_limit'], 2, ',', ' ');
                    $context .= "- {$b['name']} : {$spent} € / {$limit} € ({$percent}%)\n";
                } else {
                    $spent = number_format($b['spent'], 2, ',', ' ');
                    $context .= "- {$b['name']} : {$spent} € dépensés (sans plafond)\n";
                }
            }
        }

        return $context;
    }

    /**
     * Appeler l'API Ollama
     */
    private function callOllama(string $message, string $context, array $history): string {
        $config = require __DIR__ . '/../../config/config.php';
        $ollamaHost  = $config['ollama']['host'];
        $ollamaModel = $config['ollama']['model'];

        // Construire les messages avec historique
        $messages = [
            [
                'role'    => 'system',
                'content' => "Tu es un assistant financier intelligent intégré dans BudgetFlow, une application de gestion de budget. Tu aides les utilisateurs à comprendre leurs finances, analyser leurs dépenses et prendre de meilleures décisions financières. Réponds toujours en français, de façon claire et concise. Voici le contexte financier actuel de l'utilisateur :\n\n{$context}"
            ]
        ];

        // Ajouter l'historique de conversation
        foreach ($history as $h) {
            if (!empty($h['role']) && !empty($h['content'])) {
                $messages[] = [
                    'role'    => $h['role'],
                    'content' => $h['content']
                ];
            }
        }

        // Ajouter le nouveau message
        $messages[] = ['role' => 'user', 'content' => $message];

        // Appel API Ollama
        $payload = json_encode([
            'model'    => $ollamaModel,
            'messages' => $messages,
            'stream'   => false,
            'options'  => [
                'temperature' => 0.7,
                'num_predict' => 500,
            ]
        ]);

        $ch = curl_init("{$ollamaHost}/api/chat");
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);

        $result = curl_exec($ch);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Ollama curl error: {$error}");
            return "Désolé, je ne peux pas répondre pour le moment. Vérifiez que le service IA est démarré.";
        }

        $data = json_decode($result, true);
        return $data['message']['content']
            ?? "Désolé, je n'ai pas pu traiter votre demande.";
    }
}
```

---

## ÉTAPE 4 — Ajouter la route dans public/index.php

Ajouter cette route avec les autres :

```php
// AI Chat (utilisateurs uniquement)
$router->post('/api/chat', [AiController::class, 'chat']);
```

Et ajouter le require du controller :
```php
require_once __DIR__ . '/../app/controllers/AiController.php';
```

---

## ÉTAPE 5 — Bouton flottant + Modal Bootstrap

Créer `app/views/partials/ai-assistant.php` :

```php
<?php
// Ne pas afficher pour les admins
if (!isset($_SESSION['role']) || $_SESSION['role'] === 'admin') return;
?>

<!-- ══════════════════════════════════════
     BOUTON FLOTTANT AI
═══════════════════════════════════════ -->
<button
    id="ai-fab"
    data-bs-toggle="modal"
    data-bs-target="#aiModal"
    title="Assistant IA BudgetFlow"
    aria-label="Ouvrir l'assistant IA">
    <span class="ai-fab-icon">✨</span>
    <span class="ai-fab-label">IA</span>
</button>

<!-- ══════════════════════════════════════
     MODAL CHAT
═══════════════════════════════════════ -->
<div class="modal fade" id="aiModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content ai-modal-content">

      <!-- Header -->
      <div class="modal-header ai-modal-header">
        <div class="ai-modal-title">
          <div class="ai-avatar">✨</div>
          <div>
            <div class="ai-modal-name">BudgetFlow AI</div>
            <div class="ai-modal-status">
              <span class="ai-status-dot"></span> En ligne
            </div>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <!-- Messages -->
      <div class="modal-body ai-messages" id="aiMessages">
        <div class="ai-message ai-message-bot">
          <div class="ai-bubble">
            👋 Bonjour <strong><?= htmlspecialchars($_SESSION['name'] ?? 'utilisateur') ?></strong> !
            Je suis votre assistant financier. Je connais vos budgets et dépenses.
            Comment puis-je vous aider ?
          </div>
          <div class="ai-time"><?= date('H:i') ?></div>
        </div>
      </div>

      <!-- Input -->
      <div class="modal-footer ai-modal-footer">
        <div class="ai-input-row">
          <input
            type="text"
            id="aiInput"
            class="ai-input"
            placeholder="Posez votre question..."
            maxlength="500"
            autocomplete="off">
          <button id="aiSend" class="ai-send-btn" title="Envoyer">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
              <path d="M2 21L23 12 2 3v7l15 2-15 2v7z"/>
            </svg>
          </button>
        </div>
        <div class="ai-suggestions">
          <button class="ai-chip" data-msg="Quel est mon solde ce mois ?">💰 Mon solde</button>
          <button class="ai-chip" data-msg="Où ai-je le plus dépensé ?">📊 Mes dépenses</button>
          <button class="ai-chip" data-msg="Comment économiser ?">💡 Conseils</button>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ══════════════════════════════════════
     STYLES AI (inline — spécifiques)
═══════════════════════════════════════ -->
<style>
/* Bouton flottant */
#ai-fab {
  position: fixed;
  bottom: 28px;
  right: 28px;
  z-index: 1050;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  background: linear-gradient(135deg, #6C63FF, #22D3A5);
  color: white;
  border: none;
  border-radius: 999px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  box-shadow: 0 4px 20px rgba(108,99,255,0.4);
  animation: ai-pulse 2.5s ease-in-out infinite;
  transition: transform 0.2s, box-shadow 0.2s;
}
#ai-fab:hover {
  transform: scale(1.06) translateY(-2px);
  box-shadow: 0 8px 28px rgba(108,99,255,0.5);
}
.ai-fab-icon { font-size: 18px; }

@keyframes ai-pulse {
  0%, 100% { box-shadow: 0 4px 20px rgba(108,99,255,0.4); }
  50%       { box-shadow: 0 4px 32px rgba(108,99,255,0.7); }
}

/* Modal */
.ai-modal-content {
  background: #1A1D27;
  border: 1px solid #2A2F45;
  border-radius: 16px;
  overflow: hidden;
  max-height: 80vh;
}
.ai-modal-header {
  background: linear-gradient(135deg, #6C63FF22, #1A1D27);
  border-bottom: 1px solid #2A2F45;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.ai-modal-title { display: flex; align-items: center; gap: 12px; }
.ai-avatar {
  width: 40px; height: 40px;
  background: linear-gradient(135deg, #6C63FF, #22D3A5);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px;
}
.ai-modal-name { color: #F0F2F8; font-weight: 600; font-size: 15px; }
.ai-modal-status { display: flex; align-items: center; gap: 5px; font-size: 12px; color: #8B90A7; }
.ai-status-dot {
  width: 7px; height: 7px;
  background: #22D3A5;
  border-radius: 50%;
  animation: ai-blink 1.5s ease-in-out infinite;
}
@keyframes ai-blink {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.3; }
}

/* Messages */
.ai-messages {
  padding: 20px;
  max-height: 340px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 16px;
  background: #0F1117;
}
.ai-messages::-webkit-scrollbar { width: 4px; }
.ai-messages::-webkit-scrollbar-thumb { background: #2A2F45; border-radius: 2px; }

.ai-message { display: flex; flex-direction: column; max-width: 80%; }
.ai-message-bot { align-self: flex-start; }
.ai-message-user { align-self: flex-end; }

.ai-bubble {
  padding: 10px 14px;
  border-radius: 12px;
  font-size: 14px;
  line-height: 1.55;
}
.ai-message-bot .ai-bubble {
  background: #1A1D27;
  border: 1px solid #2A2F45;
  color: #F0F2F8;
  border-bottom-left-radius: 4px;
}
.ai-message-user .ai-bubble {
  background: linear-gradient(135deg, #6C63FF, #7B74FF);
  color: white;
  border-bottom-right-radius: 4px;
}
.ai-time { font-size: 11px; color: #555B75; margin-top: 4px; padding: 0 4px; }
.ai-message-user .ai-time { text-align: right; }

/* Typing indicator */
.ai-typing .ai-bubble {
  display: flex; align-items: center; gap: 4px; padding: 12px 16px;
}
.ai-dot {
  width: 7px; height: 7px;
  background: #8B90A7;
  border-radius: 50%;
  animation: ai-typing-dot 1.2s ease-in-out infinite;
}
.ai-dot:nth-child(2) { animation-delay: 0.2s; }
.ai-dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes ai-typing-dot {
  0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
  40%           { transform: scale(1.2); opacity: 1; }
}

/* Footer */
.ai-modal-footer {
  background: #1A1D27;
  border-top: 1px solid #2A2F45;
  padding: 12px 16px;
  flex-direction: column;
  gap: 10px;
}
.ai-input-row { display: flex; gap: 10px; width: 100%; }
.ai-input {
  flex: 1;
  background: #222636;
  border: 1px solid #2A2F45;
  border-radius: 10px;
  color: #F0F2F8;
  padding: 10px 14px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s;
}
.ai-input:focus { border-color: #6C63FF; }
.ai-input::placeholder { color: #555B75; }
.ai-send-btn {
  width: 42px; height: 42px;
  background: #6C63FF;
  border: none;
  border-radius: 10px;
  color: white;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  transition: background 0.2s;
}
.ai-send-btn:hover { background: #7B74FF; }
.ai-send-btn:disabled { background: #2A2F45; cursor: not-allowed; }

/* Suggestions */
.ai-suggestions { display: flex; gap: 8px; flex-wrap: wrap; }
.ai-chip {
  background: #222636;
  border: 1px solid #2A2F45;
  border-radius: 999px;
  color: #8B90A7;
  font-size: 12px;
  padding: 4px 12px;
  cursor: pointer;
  transition: all 0.15s;
}
.ai-chip:hover { background: rgba(108,99,255,0.15); color: #6C63FF; border-color: #6C63FF; }
</style>

<!-- ══════════════════════════════════════
     JAVASCRIPT CHAT
═══════════════════════════════════════ -->
<script>
(function() {
  const input    = document.getElementById('aiInput');
  const sendBtn  = document.getElementById('aiSend');
  const messages = document.getElementById('aiMessages');
  const chips    = document.querySelectorAll('.ai-chip');

  // Historique de conversation
  let history = [];

  // Envoyer message sur Enter
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });

  sendBtn.addEventListener('click', sendMessage);

  // Suggestions rapides
  chips.forEach(chip => {
    chip.addEventListener('click', () => {
      input.value = chip.dataset.msg;
      sendMessage();
    });
  });

  // Focus sur input à l'ouverture du modal
  document.getElementById('aiModal').addEventListener('shown.bs.modal', () => {
    input.focus();
  });

  async function sendMessage() {
    const text = input.value.trim();
    if (!text) return;

    input.value = '';
    sendBtn.disabled = true;

    // Afficher message utilisateur
    appendMessage('user', text);

    // Afficher indicateur de frappe
    const typingId = appendTyping();

    // Ajouter à l'historique
    history.push({ role: 'user', content: text });

    try {
      const res = await fetch('/api/chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          message: text,
          history: history.slice(-6) // garder les 6 derniers échanges
        })
      });

      const data = await res.json();
      removeTyping(typingId);

      const reply = data.response || data.error || 'Désolé, une erreur est survenue.';
      appendMessage('bot', reply);

      // Ajouter réponse à l'historique
      history.push({ role: 'assistant', content: reply });

    } catch (err) {
      removeTyping(typingId);
      appendMessage('bot', '❌ Impossible de contacter l\'assistant. Vérifiez que Docker tourne.');
    }

    sendBtn.disabled = false;
    input.focus();
  }

  function appendMessage(role, text) {
    const time = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    const div = document.createElement('div');
    div.className = `ai-message ai-message-${role}`;
    div.innerHTML = `
      <div class="ai-bubble">${escapeHtml(text).replace(/\n/g, '<br>')}</div>
      <div class="ai-time">${time}</div>
    `;
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
    return div;
  }

  function appendTyping() {
    const id = 'typing-' + Date.now();
    const div = document.createElement('div');
    div.className = 'ai-message ai-message-bot ai-typing';
    div.id = id;
    div.innerHTML = `
      <div class="ai-bubble">
        <span class="ai-dot"></span>
        <span class="ai-dot"></span>
        <span class="ai-dot"></span>
      </div>
    `;
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
    return id;
  }

  function removeTyping(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
  }

  function escapeHtml(text) {
    const d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
  }
})();
</script>
```

---

## ÉTAPE 6 — Inclure dans le layout app.php

Dans `app/views/layouts/app.php`, juste avant `</body>` :

```php
<?php require_once __DIR__ . '/../partials/ai-assistant.php'; ?>
```

**NE PAS inclure** dans `layouts/guest.php` ni `layouts/admin.php`.

---

## ÉTAPE 7 — Vérification finale

```bash
# 1. Relancer Docker avec Ollama
docker compose down
docker compose up -d --build

# 2. Attendre qu'Ollama démarre (1-2 min)
docker compose logs -f ollama

# 3. Télécharger le modèle dans le conteneur Ollama
docker compose exec ollama ollama pull llama3.2:1b

# 4. Tester l'API Ollama directement
curl http://localhost:11434/api/tags

# 5. Tester l'endpoint PHP
# Aller sur http://localhost:8000
# Se connecter en tant qu'utilisateur
# Cliquer sur le bouton ✨ IA en bas à droite

# 6. Vérifier que le bouton N'apparaît PAS sur les pages admin
# Se connecter en tant qu'admin
# Vérifier absence du bouton
```

---

## ÉTAPE 8 — Rapport final

```
════════════════════════════════════════
RAPPORT — INTÉGRATION IA BUDGETFLOW
════════════════════════════════════════

Docker
  Service ollama ajouté     : ✅ ou ❌
  Volume ollama_data         : ✅ ou ❌
  Modèle llama3.2:1b         : ✅ téléchargé ou ⏳ en cours

Backend PHP
  AiController.php créé      : ✅ ou ❌
  Route POST /api/chat        : ✅ ou ❌
  Contexte financier injecté  : ✅ ou ❌
  Appel Ollama fonctionnel    : ✅ ou ❌

Frontend
  Bouton flottant animé       : ✅ ou ❌
  Modal Bootstrap chat        : ✅ ou ❌
  Suggestions rapides         : ✅ ou ❌
  Historique conversation     : ✅ ou ❌

Sécurité
  Admin bloqué sur /api/chat  : ✅ ou ❌
  Auth::requireLogin()        : ✅ ou ❌
  htmlspecialchars() sur output: ✅ ou ❌

Test
  http://localhost:8000        : ✅ accessible
  Bouton ✨ visible (user)     : ✅ ou ❌
  Bouton ✨ absent (admin)     : ✅ ou ❌
  Réponse IA reçue            : ✅ ou ❌
════════════════════════════════════════
```

---

## RÈGLES ABSOLUES

```
✅ Bouton flottant UNIQUEMENT sur pages utilisateur
✅ Admin bloqué au niveau PHP sur /api/chat
✅ Contexte financier injecté dans chaque appel Ollama
✅ Historique de conversation conservé côté JS
✅ Indicateur de frappe pendant la réponse Ollama
✅ Styles AI en inline dans ai-assistant.php
   (ne pas polluer public/style.css)
✅ Ollama host = 'http://ollama:11434' (nom service Docker)
✅ Commentaires en français

❌ Ne pas modifier les controllers existants
❌ Ne pas modifier public/style.css
❌ Ne pas inclure le bouton dans guest.php ou admin.php
❌ Ne pas stocker l'historique en base de données
   (garder en mémoire JS uniquement)
```
