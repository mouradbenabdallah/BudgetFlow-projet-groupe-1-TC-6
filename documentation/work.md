# BudgetFlow - Guide de lancement et configuration

## 1. Architecture du projet

BudgetFlow utilise une architecture MVC PHP native :

- `public/index.php` : point d'entrée unique. Toutes les routes passent ici.
- `core/` : outils communs comme le routeur, la session, l'authentification, CSRF et la connexion PDO.
- `app/controllers/` : logique des pages, par exemple inscription et connexion.
- `app/models/` : accès base de données avec PDO.
- `app/views/` : fichiers HTML/PHP affichés à l'utilisateur.
- `app/views/home.php` : page d'accueil publique affichée sur `/`.
- `config/config.php` : configuration lue par PHP.
- `database/schema.sql` : création des tables PostgreSQL et données de départ.
- `docker-compose.yml` : démarre PHP, Nginx et PostgreSQL ensemble.
- `docker/` : configuration PHP-FPM et Nginx.

## 2. Services Docker

Le fichier `docker-compose.yml` démarre trois services :

| Service    | Rôle                                           |
| ---------- | ---------------------------------------------- |
| `nginx`    | Serveur web public sur `http://localhost:8000` |
| `php`      | Exécute le code PHP avec PHP-FPM               |
| `postgres` | Base de données PostgreSQL 16                  |

Important : PostgreSQL n'expose plus le port `5432` sur ton Linux. C'est volontaire pour éviter le conflit avec un PostgreSQL déjà installé sur la machine. PHP se connecte à PostgreSQL via le réseau Docker interne avec le host `postgres`.

## 3. Lancer le projet

Depuis le dossier du projet :

```bash
cd ~/Downloads/budgetflow
docker compose up --build
```

En arrière-plan :

```bash
docker compose up -d --build
```

Ouvre ensuite :

```text
http://localhost:8000
```

La première page affichée est maintenant la page d'accueil publique. Les boutons de cette page mènent vers `/login`, `/register` ou le tableau de bord.

## 4. Compte admin de test

Le fichier `database/schema.sql` crée un admin au premier démarrage de la base :

```text
Email: admin@budgetflow.local
Password: password
```

Les comptes créés avec `/register` sont enregistrés avec `is_active = false`. C'est normal : la fonction admin de validation viendra plus tard.

## 5. Configuration de la base de données

Les identifiants sont dans `docker-compose.yml` :

```yaml
POSTGRES_DB: budgetflow
POSTGRES_USER: budgetflow
POSTGRES_PASSWORD: budgetflow
```

Le PHP lit ces mêmes valeurs via les variables :

```yaml
DB_HOST: postgres
DB_PORT: 5432
DB_NAME: budgetflow
DB_USER: budgetflow
DB_PASSWORD: budgetflow
```

Dans `config/config.php`, ces valeurs sont récupérées avec `getenv()`. Si une variable n'existe pas, une valeur par défaut est utilisée.

Ne mets pas `DB_HOST=localhost` dans Docker. Depuis le conteneur PHP, `localhost` veut dire "le conteneur PHP lui-même". Il faut utiliser `postgres`, qui est le nom du service Compose.

## 6. Initialisation du schéma SQL

Cette ligne dans `docker-compose.yml` monte le schéma dans PostgreSQL :

```yaml
./database/schema.sql:/docker-entrypoint-initdb.d/01_schema.sql:ro
```

PostgreSQL exécute ce fichier seulement quand le volume `postgres_data` est créé pour la première fois.

Si tu modifies `schema.sql` après le premier lancement, les changements ne seront pas rejoués automatiquement. Pour recréer la base :

```bash
docker compose down -v
docker compose up -d --build
```

Attention : `down -v` supprime les données de la base.

## 7. Commandes utiles

Voir les conteneurs :

```bash
docker compose ps
```

Voir les logs :

```bash
docker compose logs -f
```

Voir les logs PostgreSQL seulement :

```bash
docker compose logs postgres
```

Entrer dans PostgreSQL :

```bash
docker compose exec postgres psql -U budgetflow -d budgetflow
```

Lister les utilisateurs :

```bash
docker compose exec postgres psql -U budgetflow -d budgetflow -c "SELECT id, email, role, is_active FROM users;"
```

Arrêter les services :

```bash
docker compose down
```

Arrêter et supprimer la base :

```bash
docker compose down -v
```

## 8. Comment fonctionne une requête

1. Le navigateur appelle `http://localhost:8000/login`.
2. Nginx reçoit la requête.
3. Nginx envoie la requête PHP vers le service `php:9000`.
4. `public/index.php` charge les classes et enregistre les routes.
5. `core/Router.php` appelle `AuthController`.
6. Le contrôleur utilise `User.php` pour parler à PostgreSQL via `Database.php`.
7. La vue PHP est rendue et renvoyée au navigateur.

## 9. Problèmes fréquents

Si le port `8000` est déjà utilisé, change cette ligne dans `docker-compose.yml` :

```yaml
ports:
  - "8001:80"
```

Puis ouvre :

```text
http://localhost:8001
```

Si le login admin échoue après une modification du seed SQL, recrée la base :

```bash
docker compose down -v
docker compose up -d --build
```

Si PostgreSQL n'est pas lancé :

```bash
docker compose logs postgres
```

docker compose up --build -d
docker compose exec -T postgres psql -U budgetflow -d budgetflow < database/seed_dashboard.sql
Then open:

text

http://localhost:8000/login
Use:

text

Email: demo@budgetflow.local
Password: password
