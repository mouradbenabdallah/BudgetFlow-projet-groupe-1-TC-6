# BudgetFlow — Guide de lancement et configuration

---

## 1. Architecture du projet

BudgetFlow est un MVC PHP natif sans framework.

| Dossier / Fichier | Rôle |
|---|---|
| `public/index.php` | Point d'entrée unique — charge toutes les classes et enregistre les routes |
| `core/` | Routeur, session, auth, CSRF, connexion PDO |
| `app/controllers/` | Logique métier de chaque page |
| `app/models/` | Accès base de données (PDO préparé) |
| `app/views/` | Templates PHP affichés à l'utilisateur |
| `config/config.php` | Configuration centrale lue via `getenv()` |
| `database/schema.sql` | Création des 5 tables + données initiales |
| `docker-compose.yml` | Orchestration des 4 services Docker |
| `docker/` | Dockerfile PHP + config Nginx |
| `public/animations/` | GIFs (ai-chat.gif, pdf.gif) |

---

## 2. Les 4 services Docker

| Service | Conteneur | Rôle | Port |
|---------|-----------|------|------|
| `nginx` | `budgetflow_nginx` | Serveur web — reçoit toutes les requêtes HTTP | `8000:80` |
| `php` | `budgetflow_php` | Exécute le code PHP-FPM 8.3 | interne `9000` |
| `postgres` | `budgetflow_postgres` | Base de données PostgreSQL 16 | interne `5432` |
| `ollama` | `budgetflow_ollama` | Moteur IA local (llama3.2:1b) | `11434:11434` |

Les services se parlent par leur **nom de service** sur le réseau Docker interne `budgetflow`.  
PHP utilise `postgres` comme host (pas `localhost`) et `ollama:11434` pour l'IA.

---

## 3. Premier lancement

```bash
# 1. Démarrer les 4 services
cd ~/Downloads/budgetflow
docker compose up -d --build

# 2. Télécharger le modèle IA (une seule fois — environ 1.3 Go)
docker exec budgetflow_ollama ollama pull llama3.2:1b

# 3. Vérifier que tout tourne
docker compose ps

# 4. Ouvrir l'application
# http://localhost:8000
```

**Compte admin par défaut :**
```
Email    : admin@budgetflow.local
Password : password
```

---

## 4. Lancement quotidien

```bash
# Les volumes conservent les données et le modèle IA
docker compose up -d

# Vérifier que le modèle IA est toujours là
docker exec budgetflow_ollama ollama list
```

---

## 5. Reset complet (supprime toutes les données)

```bash
# Supprime conteneurs + volumes (BDD et modèles IA effacés)
docker compose down -v

# Relancer depuis zéro
docker compose up -d --build
docker exec budgetflow_ollama ollama pull llama3.2:1b
```

---

## 6. Configuration base de données

Les identifiants sont dans `docker-compose.yml` :

```yaml
POSTGRES_DB:       budgetflow
POSTGRES_USER:     budgetflow
POSTGRES_PASSWORD: budgetflow
```

PHP lit ces valeurs via `getenv()` dans `config/config.php` :

```yaml
DB_HOST:     postgres   # ← nom du service Docker, JAMAIS localhost
DB_PORT:     5432
DB_NAME:     budgetflow
DB_USER:     budgetflow
DB_PASSWORD: budgetflow
```

> **Important :** `DB_HOST=postgres` et non `localhost`.  
> Depuis le container PHP, `localhost` désigne le container PHP lui-même.

---

## 7. Initialisation du schéma SQL

```yaml
# docker-compose.yml
volumes:
  - ./database/schema.sql:/docker-entrypoint-initdb.d/01_schema.sql:ro
```

PostgreSQL exécute ce fichier **uniquement à la création du volume** (`postgres_data`).  
Si `schema.sql` est modifié après le premier lancement :

```bash
docker compose down -v && docker compose up -d --build
```

---

## 8. Commandes utiles

### Démarrage / Arrêt

```bash
docker compose up -d                      # démarrer en arrière-plan
docker compose up -d --build              # rebuild l'image PHP (après modif Dockerfile)
docker compose down                       # arrêter (données conservées)
docker compose down -v                    # arrêter + supprimer volumes (reset BDD)
docker compose restart php                # redémarrer un seul service
```

### État et logs

```bash
docker compose ps                         # état des 4 containers
docker compose logs -f                    # tous les logs en temps réel
docker compose logs -f php                # logs PHP uniquement
docker compose logs --tail=50 php         # 50 dernières lignes PHP
docker compose logs -f postgres           # logs PostgreSQL
docker compose logs -f ollama             # logs Ollama / IA
```

### Base de données

```bash
# Console SQL interactive
docker compose exec postgres psql -U budgetflow -d budgetflow

# Commandes SQL utiles
\dt                   -- lister les tables
\d users              -- structure d'une table
SELECT * FROM users;  -- voir tous les utilisateurs
\q                    -- quitter

# Lister les utilisateurs en une ligne
docker compose exec postgres psql -U budgetflow -d budgetflow \
  -c "SELECT id, name, email, role, is_active FROM users;"
```

### Synchroniser un fichier modifié vers les containers

```bash
# Après modification d'une vue ou d'un controller
docker cp chemin/local/fichier.php budgetflow_php:/var/www/html/chemin/fichier.php
docker cp chemin/local/fichier.php budgetflow_nginx:/var/www/html/chemin/fichier.php
```

### Assistant IA (Ollama)

```bash
docker exec budgetflow_ollama ollama list          # modèles installés
docker exec budgetflow_ollama ollama pull llama3.2:1b  # télécharger le modèle
docker exec -it budgetflow_ollama ollama run llama3.2:1b  # chat interactif
docker exec budgetflow_php curl -s http://ollama:11434/api/tags  # test connectivité
```

---

## 9. Comment fonctionne une requête

```
Navigateur → http://localhost:8000/dashboard
      ↓
  Nginx (port 80)
      ↓  try_files → index.php
  PHP-FPM (port 9000)
      ↓  public/index.php
  Router::dispatch()
      ↓  [DashboardController, 'index']
  DashboardController::index()
      ↓  Auth::requireRole('user')
      ↓  new Transaction() → Database::getInstance() → PostgreSQL
      ↓  require view/dashboard/index.php
  HTML → Nginx → Navigateur
```

---

## 10. Problèmes fréquents

### Port 8000 déjà utilisé

```yaml
# docker-compose.yml
ports:
  - "8001:80"   # changer 8000 → 8001
```

### Login admin échoue après modif du seed SQL

```bash
docker compose down -v && docker compose up -d --build
```

### L'assistant IA ne répond pas

```bash
# 1. Vérifier le container Ollama
docker ps | grep ollama

# 2. Vérifier le modèle
docker exec budgetflow_ollama ollama list

# 3. Tester depuis PHP
docker exec budgetflow_php curl -sf http://ollama:11434/api/tags

# 4. Si "ollama" n'est pas résolu (problème réseau Docker)
docker network connect --alias ollama budgetflow_budgetflow budgetflow_ollama
```

### Erreur "container name already in use"

```bash
docker rm -f budgetflow_ollama   # ou le container concerné
docker compose up -d
```

### Modifications de code non prises en compte

```bash
# Les fichiers PHP sont servis directement depuis le volume monté
# Pas besoin de rebuild — mais si un fichier est copié manuellement :
docker cp public/style.css budgetflow_php:/var/www/html/public/style.css
docker cp public/style.css budgetflow_nginx:/var/www/html/public/style.css
```
