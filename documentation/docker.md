# Docker — BudgetFlow

Ce document explique comment les 4 services Docker de BudgetFlow ont été créés, comment ils fonctionnent ensemble, et toutes les commandes importantes pour les gérer.

---

## Les 4 services

```
nginx      → serveur web, reçoit http://localhost:8000
php        → exécute le code PHP (PHP-FPM 8.3)
postgres   → base de données PostgreSQL 16
ollama     → moteur IA local (modèle llama3.2:1b)
```

Tous les services sont connectés via le réseau Docker interne `budgetflow`.  
Ils se parlent par leur **nom de service** (ex: `php:9000`, `postgres:5432`, `ollama:11434`).

---

## Comment chaque service a été créé

### 1. PHP (`budgetflow_php`)

Le service PHP a été créé avec un **Dockerfile custom** (`docker/php.Dockerfile`) car on avait besoin d'installer l'extension PostgreSQL pour PHP.

**`docker/php.Dockerfile` :**

```dockerfile
FROM php:8.3-fpm-alpine

# Installe le driver PostgreSQL pour PHP (PDO)
RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www/html

COPY . /var/www/html

# Donne les droits d'écriture au processus PHP-FPM
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
```

**Pourquoi :**
- `php:8.3-fpm-alpine` = PHP 8.3 avec PHP-FPM (pas Apache), image légère Alpine Linux
- `pdo` + `pdo_pgsql` = permet à PHP de se connecter à PostgreSQL
- `EXPOSE 9000` = PHP-FPM écoute sur le port 9000 (utilisé par Nginx)
- Le navigateur ne parle jamais directement à PHP — c'est Nginx qui fait le relais

**Dans `docker-compose.yml` :**

```yaml
php:
  build:
    context: .
    dockerfile: docker/php.Dockerfile
  container_name: budgetflow_php
  restart: unless-stopped
  environment:
    DB_HOST: postgres       # ← nom du service, pas localhost
    DB_PORT: 5432
    DB_NAME: budgetflow
    DB_USER: budgetflow
    DB_PASSWORD: budgetflow
    MAIL_HOST: smtp.gmail.com
    MAIL_PORT: 587
    MAIL_USERNAME: mouradabdallah581@gmail.com
    MAIL_PASSWORD: ${MAIL_PASSWORD:-}
  volumes:
    - .:/var/www/html       # ← le code local est monté dans le conteneur
  depends_on:
    - postgres
  networks:
    - budgetflow
```

---

### 2. Nginx (`budgetflow_nginx`)

Le service Nginx utilise l'image officielle — pas besoin de Dockerfile custom.  
On lui fournit uniquement un fichier de configuration.

**`docker/nginx.conf` :**

```nginx
server {
    listen 80;
    root /var/www/html/public;   # dossier public/ du projet
    index index.php;

    location / {
        # Toutes les URLs (/login, /dashboard...) → public/index.php
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass php:9000;   # ← envoie les .php au service PHP
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\. {
        deny all;                # bloque .env, .git, etc.
    }
}
```

**Dans `docker-compose.yml` :**

```yaml
nginx:
  image: nginx:1.25-alpine
  container_name: budgetflow_nginx
  restart: unless-stopped
  ports:
    - "8000:80"               # localhost:8000 → port 80 du conteneur
  volumes:
    - .:/var/www/html
    - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf:ro
  depends_on:
    - php
  networks:
    - budgetflow
```

**Flux d'une requête :**

```
Navigateur → http://localhost:8000/dashboard
    ↓
Nginx (port 80) reçoit la requête
    ↓
URL propre → renvoie vers index.php
    ↓
fastcgi_pass php:9000 → PHP-FPM exécute le code
    ↓
PHP retourne le HTML → Nginx l'envoie au navigateur
```

---

### 3. PostgreSQL (`budgetflow_postgres`)

Le service PostgreSQL utilise l'image officielle.  
Le schéma de la base (tables) est créé automatiquement au premier démarrage.

**Dans `docker-compose.yml` :**

```yaml
postgres:
  image: postgres:16-alpine
  container_name: budgetflow_postgres
  restart: unless-stopped
  environment:
    POSTGRES_DB: budgetflow       # crée la base de données
    POSTGRES_USER: budgetflow     # crée l'utilisateur
    POSTGRES_PASSWORD: budgetflow # définit le mot de passe
  ports:
    - "5432"                      # accessible depuis l'hôte pour Beekeeper Studio
  volumes:
    - postgres_data:/var/lib/postgresql/data          # données persistantes
    - ./database/schema.sql:/docker-entrypoint-initdb.d/01_schema.sql:ro
  networks:
    - budgetflow
```

**Pourquoi le schéma se crée automatiquement :**  
PostgreSQL exécute tous les fichiers `.sql` placés dans `/docker-entrypoint-initdb.d/` au **premier démarrage** du volume.  
Donc `schema.sql` crée les tables `users`, `budgets`, `transactions`, `categories`, `budget_members` automatiquement.

> **Important :** `DB_HOST: postgres` dans le service PHP — à l'intérieur de Docker, le nom du service `postgres` remplace `localhost`.

---

### 4. Ollama (`budgetflow_ollama`)

Le service Ollama utilise l'image officielle `ollama/ollama`.  
Il fournit une API REST pour utiliser des modèles LLM localement.

**Dans `docker-compose.yml` :**

```yaml
ollama:
  image: ollama/ollama:latest
  container_name: budgetflow_ollama
  restart: unless-stopped
  volumes:
    - ollama_data:/root/.ollama   # conserve les modèles téléchargés
  ports:
    - "11434:11434"               # API accessible sur localhost:11434
  networks:
    - budgetflow
  healthcheck:
    test: ["CMD", "curl", "-f", "http://localhost:11434/api/tags"]
    interval: 30s
    timeout: 10s
    retries: 5
    start_period: 60s
```

**Après le démarrage, télécharger le modèle :**

```bash
docker exec budgetflow_ollama ollama pull llama3.2:1b
```

Le modèle (~1.3 GB) est stocké dans le volume `ollama_data` — il survive aux redémarrages.

**Comment PHP parle à Ollama :**  
`AiController.php` fait un appel cURL vers `http://ollama:11434/api/chat`  
(le nom `ollama` est résolu automatiquement par le réseau Docker interne).

---

## Réseau et volumes

```yaml
networks:
  budgetflow:
    driver: bridge    # réseau interne isolé, les conteneurs se voient par nom

volumes:
  postgres_data:      # données PostgreSQL persistantes
  ollama_data:        # modèles IA persistants
```

**Schéma de communication :**

```
localhost:8000
      │
      ▼
   Nginx ──(php:9000)──► PHP ──(postgres:5432)──► PostgreSQL
                          │
                          └──(ollama:11434)──► Ollama
```

---

## Toutes les commandes importantes

### Démarrage et arrêt

```bash
# Démarrer tous les services (en arrière-plan)
docker compose up -d

# Démarrer et reconstruire l'image PHP (après modif du Dockerfile)
docker compose up -d --build

# Arrêter les conteneurs (données conservées)
docker compose down

# Arrêter et supprimer tous les volumes (remet à zéro la BDD et les modèles IA)
docker compose down -v

# Redémarrer un seul service
docker compose restart php
docker compose restart nginx
```

### État et logs

```bash
# Voir les 4 conteneurs et leur statut
docker compose ps

# Voir tous les logs en temps réel
docker compose logs -f

# Logs d'un seul service
docker compose logs -f php
docker compose logs -f nginx
docker compose logs -f postgres
docker compose logs -f ollama

# Voir les 50 dernières lignes
docker compose logs --tail=50 php
```

### Accéder aux conteneurs

```bash
# Entrer dans le conteneur PHP (shell)
docker compose exec php sh

# Entrer dans le conteneur Nginx
docker compose exec nginx sh

# Accéder à la base PostgreSQL (console SQL)
docker compose exec postgres psql -U budgetflow -d budgetflow

# Entrer dans le conteneur Ollama
docker exec -it budgetflow_ollama bash
```

### Base de données (PostgreSQL)

```bash
# Ouvrir la console SQL
docker compose exec postgres psql -U budgetflow -d budgetflow

# Commandes SQL utiles dans la console :
\dt                          -- lister les tables
\d users                     -- structure de la table users
SELECT * FROM users;         -- voir tous les utilisateurs
\q                           -- quitter

# Lister les utilisateurs en une ligne
docker compose exec postgres psql -U budgetflow -d budgetflow \
  -c "SELECT id, name, email, role, is_active FROM users;"

# Réinitialiser complètement la base de données
docker compose down -v && docker compose up -d --build
```

### Ollama (IA)

```bash
# Voir si le conteneur Ollama tourne
docker ps | grep ollama

# Voir les modèles installés
docker exec budgetflow_ollama ollama list

# Télécharger le modèle (première fois, ~1.3 GB)
docker exec budgetflow_ollama ollama pull llama3.2:1b

# Supprimer un modèle
docker exec budgetflow_ollama ollama rm llama3.2:1b

# Tester le modèle en mode interactif (chat dans le terminal)
docker exec -it budgetflow_ollama ollama run llama3.2:1b

# Voir les logs Ollama
docker logs budgetflow_ollama
docker logs -f budgetflow_ollama

# Tester l'API Ollama depuis le terminal
curl http://localhost:11434/api/tags

# Tester que PHP peut joindre Ollama (depuis l'intérieur du réseau Docker)
docker exec budgetflow_php curl -s http://ollama:11434/api/tags

# Envoyer un message test directement à Ollama
curl -s -X POST http://localhost:11434/api/chat \
  -H "Content-Type: application/json" \
  -d '{
    "model": "llama3.2:1b",
    "messages": [{"role": "user", "content": "Dis bonjour en français."}],
    "stream": false
  }' | python3 -c "import sys,json; print(json.load(sys.stdin)['message']['content'])"
```

### Problème courant : conflit de nom de conteneur

```bash
# Erreur : "The container name /budgetflow_ollama is already in use"
# Solution : supprimer l'ancien conteneur manuel, laisser docker compose gérer

docker rm -f budgetflow_ollama
docker compose up -d
```

### Images et nettoyage

```bash
# Lister les images Docker du projet
docker images | grep budgetflow

# Supprimer les images inutilisées (libère de l'espace disque)
docker image prune

# Supprimer tout ce qui n'est pas utilisé (images, conteneurs, volumes orphelins)
docker system prune

# Voir l'espace utilisé par Docker
docker system df
```

---

## Procédure complète — premier lancement

```bash
# 1. Cloner le projet
git clone <repo-url>
cd budgetflow

# 2. Lancer les 4 services
docker compose up -d --build

# 3. Télécharger le modèle IA (une seule fois)
docker exec budgetflow_ollama ollama pull llama3.2:1b

# 4. Vérifier que tout tourne
docker compose ps

# 5. Ouvrir l'application
# http://localhost:8000
# Admin : admin@budgetflow.local / password
```

## Procédure — après un redémarrage normal

```bash
# Les données sont conservées dans les volumes
docker compose up -d

# Le modèle IA est encore là — pas besoin de le re-télécharger
docker exec budgetflow_ollama ollama list
```

## Procédure — reset complet

```bash
# Supprime les conteneurs ET les volumes (BDD + modèles IA effacés)
docker compose down -v

# Tout relancer depuis zéro
docker compose up -d --build
docker exec budgetflow_ollama ollama pull llama3.2:1b
```
 