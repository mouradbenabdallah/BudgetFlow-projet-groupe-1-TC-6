# BudgetFlow AI — Intégration Ollama

> Assistant financier conversationnel alimenté par un modèle LLM local (llama3.2:1b).  
> Aucune donnée ne quitte le serveur — tout tourne dans Docker.

---

## Objectif

Permettre à chaque utilisateur de poser des questions en langage naturel sur ses finances :

- « Quel est mon solde ce mois ? »
- « Où ai-je le plus dépensé ? »
- « Donne-moi des conseils pour économiser. »

L'IA connaît les données réelles de l'utilisateur (budgets, dépenses, catégories) grâce au contexte financier injecté dans chaque requête.

---

## Architecture

```
Navigateur (utilisateur)
        │
        │  POST /api/chat  { message, history }
        ▼
  PHP AiController
        │
        │  1. Vérifie la session (Auth::requireLogin)
        │  2. Bloque les admins (403)
        │  3. Lit le contexte financier depuis PostgreSQL
        │  4. Appel cURL → Ollama /api/chat
        ▼
  Ollama (Docker)
  modèle : llama3.2:1b
        │
        │  Réponse JSON { message: { content: "..." } }
        ▼
  PHP → JSON { response: "..." }
        │
        ▼
  JavaScript (modal chat)
```

---

## Fichiers créés / modifiés

| Fichier | Rôle |
|---|---|
| `app/controllers/AiController.php` | Endpoint PHP `/api/chat` |
| `app/views/partials/ai-assistant.php` | Bouton flottant + modal chat + CSS + JS |
| `app/views/layouts/app.php` | Inclusion du partial (utilisateurs uniquement) |
| `public/index.php` | Route `POST /api/chat` |
| `config/config.php` | Clé `ollama` (host + model) |
| `docker-compose.yml` | Service `ollama` + volume `ollama_data` |
| `docker/ollama-init.sh` | Script de pull du modèle |

---

## Container Ollama dans Docker

### Trouver Ollama

```bash
# Voir si le conteneur tourne
docker ps | grep ollama

# Voir les logs Ollama
docker logs budgetflow_ollama

# Voir les modèles téléchargés
docker exec budgetflow_ollama ollama list
```

### Accès à l'API Ollama

| Depuis | URL |
|---|---|
| Navigateur / terminal hôte | `http://localhost:11434` |
| Container PHP (interne Docker) | `http://ollama:11434` |

```bash
# Tester l'API depuis le terminal
curl http://localhost:11434/api/tags

# Tester depuis le container PHP
docker exec budgetflow_php curl -s http://ollama:11434/api/tags
```

### Modèles disponibles

```bash
# Lister les modèles installés
docker exec budgetflow_ollama ollama list

# Télécharger un modèle
docker exec budgetflow_ollama ollama pull llama3.2:1b

# Supprimer un modèle
docker exec budgetflow_ollama ollama rm llama3.2:1b

# Tester un modèle en mode interactif
docker exec -it budgetflow_ollama ollama run llama3.2:1b
```

### Envoyer une requête directement à Ollama

```bash
curl -s -X POST http://localhost:11434/api/chat \
  -H "Content-Type: application/json" \
  -d '{
    "model": "llama3.2:1b",
    "messages": [
      { "role": "user", "content": "Bonjour, comment vas-tu ?" }
    ],
    "stream": false
  }' | python3 -c "import sys,json; print(json.load(sys.stdin)['message']['content'])"
```

---

## Démarrage complet

```bash
# 1. Démarrer tous les services (app + Ollama)
docker compose up -d

# 2. Vérifier que tous les conteneurs tournent
docker compose ps

# 3. Télécharger le modèle (première fois seulement, ~1.3 GB)
docker exec budgetflow_ollama ollama pull llama3.2:1b

# 4. Vérifier que le modèle est présent
docker exec budgetflow_ollama ollama list

# 5. Ouvrir l'application
# http://localhost:8000  → se connecter → bouton IA en bas à droite
```

---

## Configuration

Dans `config/config.php` :

```php
'ollama' => [
    'host'  => getenv('OLLAMA_HOST') ?: 'http://ollama:11434',
    'model' => getenv('OLLAMA_MODEL') ?: 'llama3.2:1b',
],
```

Variables d'environnement optionnelles dans `docker-compose.yml` :

```yaml
OLLAMA_HOST: http://ollama:11434
OLLAMA_MODEL: llama3.2:1b
```

Changer de modèle (exemple avec un modèle plus grand) :

```bash
# Télécharger llama3.2:3b (meilleure qualité, plus lent)
docker exec budgetflow_ollama ollama pull llama3.2:3b

# Puis changer dans docker-compose.yml :
# OLLAMA_MODEL: llama3.2:3b
# et relancer : docker compose up -d php
```

---

## Contexte financier injecté

À chaque requête, `AiController::getUserFinancialContext()` construit automatiquement :

```
Contexte financier de l'utilisateur ce mois (mai 2026) :
- Revenus  : 0,00 TND
- Dépenses : 1 009,00 TND
- Solde    : -1 009,00 TND

Top catégories de dépenses :
- Autre : 1 000,00 TND
- Transport : 9,00 TND

État des budgets :
- Budget Alerte Test : 1 009,00 TND / 10,00 TND (10090%)
```

Ce texte est injecté comme **system prompt** — l'IA répond donc avec les vraies données de l'utilisateur sans que celui-ci ait besoin de les mentionner.

---

## Sécurité

| Règle | Implémentation |
|---|---|
| Utilisateur connecté obligatoire | `Auth::requireLogin()` en premier |
| Admin bloqué | `if role === admin → 403` |
| Bouton absent sur pages admin | Partial inclus uniquement dans `layouts/app.php` |
| Bouton absent sur pages guest | Non inclus dans `layouts/guest.php` |
| Historique client uniquement | Aucune écriture en base de données |
| Échappement HTML | `htmlspecialchars()` sur `$_SESSION['name']` |
| Données locales | Ollama tourne dans Docker — aucun appel externe |

---

## Frontend — Composants

### Bouton flottant

```
Position : fixed, bottom: 28px, right: 28px, z-index: 1050
Style    : gradient violet → vert, animation pulse
Trigger  : data-bs-toggle="modal" data-bs-target="#aiModal"
```

### Modal

```
Header  : avatar gradient + "BudgetFlow AI" + statut "En ligne" animé
Body    : zone de messages scrollable (#aiMessages)
Footer  : input texte + bouton envoyer + 3 chips suggestions
```

### Chips de suggestions

| Chip | Message envoyé |
|---|---|
| Mon solde | "Quel est mon solde ce mois ?" |
| Mes dépenses | "Où ai-je le plus dépensé ce mois ?" |
| Conseils | "Donne-moi des conseils pour économiser." |

### Indicateur de frappe

3 points animés (`.ai-dot`) affichés pendant que l'IA génère la réponse.  
Supprimés automatiquement dès que la réponse arrive.

### Historique

- Conservé en **mémoire JavaScript** uniquement (variable `history`)
- Maximum **6 derniers échanges** envoyés à Ollama par requête
- Réinitialisé à la fermeture du modal / rechargement de page

---

## Dépannage

### Le bouton IA n'apparaît pas

```bash
# Vérifier que le partial est inclus dans app.php
grep "ai-assistant" app/views/layouts/app.php

# Vérifier le rôle de l'utilisateur (le bouton est masqué pour admin)
# Se connecter avec un compte role = 'user'
```

### L'IA ne répond pas

```bash
# 1. Vérifier que le conteneur Ollama tourne
docker ps | grep ollama

# 2. Vérifier que le modèle est chargé
docker exec budgetflow_ollama ollama list

# 3. Tester la connectivité depuis PHP
docker exec budgetflow_php curl -sf http://ollama:11434/api/tags

# 4. Voir les erreurs PHP
docker logs budgetflow_php | grep AiController

# 5. Si "ollama" n'est pas résolu, reconnecter avec alias
docker network disconnect budgetflow_budgetflow budgetflow_ollama
docker network connect --alias ollama budgetflow_budgetflow budgetflow_ollama
```

### Réponse lente (>30s)

Normal sur CPU sans GPU. Le modèle `llama3.2:1b` est le plus rapide.  
Le timeout PHP est réglé à **120 secondes** dans `AiController::callOllama()`.

### Après docker compose down / up

```bash
# Redémarrer Ollama et vérifier le modèle
docker compose up -d ollama
docker exec budgetflow_ollama ollama list

# Si le modèle a disparu (volume supprimé avec -v)
docker exec budgetflow_ollama ollama pull llama3.2:1b
```
