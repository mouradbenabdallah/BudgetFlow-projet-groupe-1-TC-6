<?php
// Afficher uniquement pour les utilisateurs connectés (pas pour les admins).
if (!isset($_SESSION['role']) || $_SESSION['role'] === 'admin') {
    return;
}
$aiUserName = htmlspecialchars((string) ($_SESSION['name'] ?? 'utilisateur'), ENT_QUOTES, 'UTF-8');
?>

<!-- ── Bouton flottant AI ─────────────────────────────────────────────────── -->
<button
    id="ai-fab"
    data-bs-toggle="modal"
    data-bs-target="#aiModal"
    title="Assistant IA CASHtoCASH"
    aria-label="Ouvrir l'assistant IA">
    <img src="/animations/ai-chat.gif" alt="" aria-hidden="true" class="ai-fab-gif">
    <span class="ai-fab-label">IA</span>
</button>

<!-- ── Modal chat ────────────────────────────────────────────────────────── -->
<div class="modal fade" id="aiModal" tabindex="-1" aria-labelledby="aiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ai-modal-content">

            <!-- Header -->
            <div class="modal-header ai-modal-header">
                <div class="ai-modal-title">
                    <div class="ai-avatar" aria-hidden="true">
                        <img src="/animations/ai-chat.gif" alt="" class="ai-avatar-gif">
                    </div>
                    <div>
                        <div class="ai-modal-name" id="aiModalLabel">CASHtoCASH AI</div>
                        <div class="ai-modal-status">
                            <span class="ai-status-dot" aria-hidden="true"></span>
                            <span>En ligne</span>
                            <span class="ai-modal-badge" aria-hidden="true"><i class="bi bi-stars"></i> Assistant</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <!-- Zone de messages -->
            <div class="modal-body ai-messages" id="aiMessages" role="log" aria-live="polite">
                <div class="ai-message ai-message-bot">
                    <div class="ai-bubble">
                        Bonjour <strong><?= $aiUserName ?></strong> !
                        Je suis votre assistant financier. Je connais vos budgets et dépenses.
                        Comment puis-je vous aider ?
                    </div>
                    <div class="ai-time" aria-hidden="true"><?= date('H:i') ?></div>
                </div>
            </div>

            <!-- Input + suggestions -->
            <div class="modal-footer ai-modal-footer">
                <div class="ai-input-row">
                    <label class="visually-hidden" for="aiInput">Votre message</label>
                    <input
                        type="text"
                        id="aiInput"
                        class="ai-input"
                        placeholder="Posez votre question..."
                        maxlength="500"
                        autocomplete="off">
                    <button id="aiSend" class="ai-send-btn" type="button" title="Envoyer" aria-label="Envoyer">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M2 21L23 12 2 3v7l15 2-15 2v7z"/>
                        </svg>
                    </button>
                </div>
                <div class="ai-suggestions" role="group" aria-label="Suggestions rapides">
                    <button class="ai-chip" type="button" data-msg="Quel est mon solde ce mois ?">
                        <i class="bi bi-wallet2" aria-hidden="true"></i> Mon solde
                    </button>
                    <button class="ai-chip" type="button" data-msg="Où ai-je le plus dépensé ce mois ?">
                        <i class="bi bi-bar-chart" aria-hidden="true"></i> Mes dépenses
                    </button>
                    <button class="ai-chip" type="button" data-msg="Donne-moi des conseils pour économiser.">
                        <i class="bi bi-lightbulb" aria-hidden="true"></i> Conseils
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ── Styles (inline — ne polluent pas public/style.css) ─────────────────── -->
<style>
/* ── Floating button ── */
#ai-fab {
    position: fixed;
    bottom: 28px;
    right: 28px;
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 64px;
    height: 64px;
    background: #001e2b;
    color: #fff;
    border: 3px solid #3B82F6;
    border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
    cursor: pointer;
    padding: 0;
    box-shadow: 0 0 16px rgba(59,130,246,.45), 0 0 32px rgba(59,130,246,.2);
    animation: ai-wavy 3s ease-in-out infinite;
    transition: transform .2s, box-shadow .2s;
}
#ai-fab:hover {
    transform: scale(1.1);
    box-shadow: 0 0 24px rgba(59,130,246,.7), 0 0 48px rgba(59,130,246,.35);
}
.ai-fab-gif {
    width: 40px; height: 40px;
    object-fit: contain; flex-shrink: 0; border-radius: 50%;
}
.ai-fab-label { display: none; }
.ai-avatar-gif {
    width: 100%; height: 100%;
    object-fit: contain; border-radius: 50%;
}
@keyframes ai-wavy {
    0%   { border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%; box-shadow: 0 0 16px rgba(59,130,246,.45), 0 0 32px rgba(59,130,246,.2); }
    25%  { border-radius: 30% 60% 70% 40% / 50% 60% 30% 60%; box-shadow: 0 0 20px rgba(59,130,246,.6),  0 0 40px rgba(59,130,246,.25); }
    50%  { border-radius: 50% 60% 30% 40% / 40% 50% 70% 60%; box-shadow: 0 0 24px rgba(59,130,246,.7),  0 0 48px rgba(59,130,246,.3); }
    75%  { border-radius: 40% 50% 60% 30% / 60% 40% 30% 70%; box-shadow: 0 0 20px rgba(59,130,246,.6),  0 0 40px rgba(59,130,246,.25); }
    100% { border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%; box-shadow: 0 0 16px rgba(59,130,246,.45), 0 0 32px rgba(59,130,246,.2); }
}

/* ── Modal container — wavy animated shape ── */
@keyframes ai-modal-wavy {
    0%   { border-radius: 28px 14px 26px 16px / 16px 26px 14px 28px; }
    25%  { border-radius: 16px 26px 14px 28px / 28px 14px 26px 16px; }
    50%  { border-radius: 26px 16px 28px 14px / 14px 28px 16px 26px; }
    75%  { border-radius: 14px 28px 16px 26px / 26px 16px 28px 14px; }
    100% { border-radius: 28px 14px 26px 16px / 16px 26px 14px 28px; }
}

.ai-modal-content {
    background: #001e2b;
    border: 1px solid #3d4f58;
    border-radius: 28px 14px 26px 16px / 16px 26px 14px 28px;
    overflow: hidden;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    animation: ai-modal-wavy 7s ease-in-out infinite;
    box-shadow: 0 32px 80px rgba(0,0,0,.55), 0 0 0 1px rgba(59,130,246,.12), 0 0 40px rgba(59,130,246,.08);
}

/* ── Header ── */
.ai-modal-header {
    background: linear-gradient(135deg, #001e2b 0%, #0d2233 100%);
    border-bottom: 1px solid #3d4f58;
    padding: 18px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
}
.ai-modal-header::after {
    content: '';
    position: absolute;
    bottom: 0; left: 22px;
    width: 48px; height: 2px;
    background: linear-gradient(90deg, #3B82F6, transparent);
    border-radius: 1px;
}
.ai-modal-title { display: flex; align-items: center; gap: 12px; }

.ai-avatar {
    width: 42px; height: 42px;
    background: linear-gradient(135deg, #1D4ED8, #3B82F6);
    border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; color: #fff; flex-shrink: 0;
    overflow: hidden; padding: 3px;
    animation: ai-wavy 4s ease-in-out infinite;
    box-shadow: 0 0 12px rgba(59,130,246,.35);
}

.ai-modal-name {
    color: #e8edeb;
    font-weight: 700;
    font-size: 15px;
    font-family: 'Plus Jakarta Sans', 'DM Sans', sans-serif;
    letter-spacing: -.1px;
}
.ai-modal-badge {
    display: inline-flex; align-items: center; gap: 4px;
    background: rgba(59,130,246,.12);
    border: 1px solid rgba(59,130,246,.25);
    border-radius: 999px;
    font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1px;
    color: #60a5fa;
    padding: 2px 8px; margin-top: 3px;
}
.ai-modal-status { display: flex; align-items: center; gap: 5px; font-size: 12px; color: #5c6c75; margin-top: 2px; }
.ai-status-dot {
    width: 6px; height: 6px; background: #3B82F6; border-radius: 50%;
    animation: ai-blink 1.5s ease-in-out infinite;
    box-shadow: 0 0 6px rgba(59,130,246,.6);
}
@keyframes ai-blink {
    0%, 100% { opacity: 1; }
    50%       { opacity: .3; }
}
.ai-modal-header .btn-close-white { opacity: .5; transition: opacity .15s; }
.ai-modal-header .btn-close-white:hover { opacity: 1; }

/* ── Messages area ── */
.ai-messages {
    padding: 20px;
    max-height: 320px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 14px;
    background: #0d1a21;
}
.ai-messages::-webkit-scrollbar { width: 4px; }
.ai-messages::-webkit-scrollbar-track { background: transparent; }
.ai-messages::-webkit-scrollbar-thumb { background: #3d4f58; border-radius: 2px; }

.ai-message      { display: flex; flex-direction: column; max-width: 80%; }
.ai-message-bot  { align-self: flex-start; }
.ai-message-user { align-self: flex-end; }

.ai-bubble {
    padding: 10px 14px;
    border-radius: 14px;
    font-size: 13.5px;
    line-height: 1.65;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.ai-message-bot .ai-bubble {
    background: #1c2d38;
    border: 1px solid #3d4f58;
    color: #b8c4c2;
    border-bottom-left-radius: 4px;
}
.ai-message-user .ai-bubble {
    background: linear-gradient(135deg, #1D4ED8, #3B82F6);
    color: #fff;
    border-bottom-right-radius: 4px;
    box-shadow: 0 4px 16px rgba(59,130,246,.25);
}
.ai-time { font-size: 10px; color: #3d4f58; margin-top: 4px; padding: 0 4px; font-family: 'Source Code Pro', monospace; }
.ai-message-user .ai-time { text-align: right; }

/* Typing indicator */
.ai-typing .ai-bubble { display: flex; align-items: center; gap: 5px; padding: 12px 16px; }
.ai-dot {
    width: 6px; height: 6px; background: #5c6c75; border-radius: 50%;
    animation: ai-typing-dot 1.2s ease-in-out infinite;
}
.ai-dot:nth-child(2) { animation-delay: .2s; }
.ai-dot:nth-child(3) { animation-delay: .4s; }
@keyframes ai-typing-dot {
    0%, 80%, 100% { transform: scale(.8); opacity: .4; }
    40%           { transform: scale(1.2); opacity: 1; }
}

/* ── Footer ── */
.ai-modal-footer {
    background: #001e2b;
    border-top: 1px solid #3d4f58;
    padding: 14px 16px;
    flex-direction: column;
    gap: 10px;
}
.ai-input-row { display: flex; gap: 8px; width: 100%; }
.ai-input {
    flex: 1;
    background: #1c2d38;
    border: 1px solid #3d4f58;
    border-radius: 100px;
    color: #e8edeb;
    padding: 10px 18px;
    font-size: 13.5px;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.ai-input:focus        { border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59,130,246,.12); }
.ai-input::placeholder { color: #3d4f58; }

.ai-send-btn {
    width: 42px; height: 42px;
    background: #3B82F6;
    border: none;
    border-radius: 50%;
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; flex-shrink: 0;
    transition: background .2s, transform .15s, box-shadow .2s;
    box-shadow: 0 4px 12px rgba(59,130,246,.35);
}
.ai-send-btn:hover    { background: #2563EB; transform: scale(1.08); box-shadow: 0 6px 20px rgba(59,130,246,.5); }
.ai-send-btn:disabled { background: #3d4f58; cursor: not-allowed; transform: none; box-shadow: none; }

/* Suggestion chips — match site's filter button style */
.ai-suggestions { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 2px; }
.ai-chip {
    background: #1c2d38;
    border: 1px solid #3d4f58;
    border-radius: 100px;
    color: #5c6c75;
    font-size: 11px;
    font-weight: 600;
    padding: 5px 12px;
    cursor: pointer;
    transition: all .15s;
    font-family: 'Plus Jakarta Sans', sans-serif;
    display: inline-flex; align-items: center; gap: 5px;
    letter-spacing: .2px;
}
.ai-chip:hover {
    background: rgba(59,130,246,.1);
    color: #60a5fa;
    border-color: rgba(59,130,246,.4);
}
</style>

<!-- ── JavaScript chat ───────────────────────────────────────────────────── -->
<script>
(function () {
    'use strict';

    var input    = document.getElementById('aiInput');
    var sendBtn  = document.getElementById('aiSend');
    var messages = document.getElementById('aiMessages');
    var modal    = document.getElementById('aiModal');

    // Historique conservé en mémoire JS uniquement (jamais persisté en base).
    var history = [];

    // Envoyer sur Entrée (sans Shift)
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    sendBtn.addEventListener('click', sendMessage);

    // Suggestions rapides
    document.querySelectorAll('.ai-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            input.value = chip.dataset.msg;
            sendMessage();
        });
    });

    // Focus sur l'input à l'ouverture du modal
    modal.addEventListener('shown.bs.modal', function () {
        input.focus();
    });

    async function sendMessage() {
        var text = input.value.trim();
        if (!text) return;

        input.value   = '';
        sendBtn.disabled = true;

        appendMessage('user', text);
        var typingId = appendTyping();

        history.push({ role: 'user', content: text });

        try {
            var res = await fetch('/api/chat', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({
                    message: text,
                    history: history.slice(-6)   // conserver les 6 derniers échanges
                })
            });

            var data  = await res.json();
            var reply = data.response || data.error || 'Désolé, une erreur est survenue.';

            removeTyping(typingId);
            appendMessage('bot', reply);
            history.push({ role: 'assistant', content: reply });

        } catch (err) {
            removeTyping(typingId);
            appendMessage('bot', 'Impossible de contacter l\'assistant. Vérifiez que Docker est en cours d\'exécution.');
        }

        sendBtn.disabled = false;
        input.focus();
    }

    function appendMessage(role, text) {
        var time = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        var div  = document.createElement('div');
        div.className = 'ai-message ai-message-' + role;
        div.innerHTML  = '<div class="ai-bubble">' + escapeHtml(text).replace(/\n/g, '<br>') + '</div>'
                       + '<div class="ai-time" aria-hidden="true">' + time + '</div>';
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    function appendTyping() {
        var id  = 'typing-' + Date.now();
        var div = document.createElement('div');
        div.className = 'ai-message ai-message-bot ai-typing';
        div.id        = id;
        div.innerHTML = '<div class="ai-bubble">'
                      + '<span class="ai-dot" aria-hidden="true"></span>'
                      + '<span class="ai-dot" aria-hidden="true"></span>'
                      + '<span class="ai-dot" aria-hidden="true"></span>'
                      + '</div>';
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
        return id;
    }

    function removeTyping(id) {
        var el = document.getElementById(id);
        if (el) el.remove();
    }

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }
}());
</script>
