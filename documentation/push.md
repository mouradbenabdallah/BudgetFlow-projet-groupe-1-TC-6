# BudgetFlow - Git, Docker et lancement du projet (Windows)

Ce guide explique comment pousser le projet sur Git et comment un autre développeur peut le récupérer puis le lancer avec Docker sur Windows.

## 1. Prérequis

Sur Windows, installe dans cet ordre :

- **Git for Windows** → https://git-scm.com/download/win
- **Docker Desktop** → https://www.docker.com/products/docker-desktop

Vérifie l'installation en ouvrant **PowerShell** ou **Git Bash** :

```powershell
git --version
docker --version
docker compose version
```

> **Important** : Lance toujours Docker Desktop avant d'utiliser les commandes Docker. L'icône Docker doit apparaître dans la barre des tâches (en bas à droite).

## 2. Installer Docker Desktop sur Windows

1. Télécharge Docker Desktop depuis https://www.docker.com/products/docker-desktop
2. Lance l'installateur `.exe` et suis les étapes
3. Quand il demande **"Use WSL 2 instead of Hyper-V"** → coche **WSL 2** (recommandé)
4. Redémarre Windows après l'installation
5. Lance Docker Desktop depuis le bureau ou le menu Démarrer
6. Attends que l'icône Docker dans la barre des tâches soit **verte** (Engine running)

Test :

```powershell
docker run hello-world
```

Si tu vois `Hello from Docker!` → Docker fonctionne correctement.

> **Problème WSL 2 ?** Si Docker demande d'installer WSL 2 :
> Ouvre PowerShell en administrateur et tape :
>
> ```powershell
> wsl --install
> ```
>
> Puis redémarre Windows.

## 3. Préparer Git dans le projet

Ouvre **PowerShell** ou **Git Bash** et va dans le dossier du projet :

```powershell
cd C:\Users\TonNom\Downloads\budgetflow
git init
```

Crée le fichier `.gitignore` :

```powershell
@"
.env
.DS_Store
vendor/
node_modules/
postgres_data/
*.log
"@ | Out-File -FilePath .gitignore -Encoding utf8
```

> Si tu utilises **Git Bash** au lieu de PowerShell, utilise cette commande à la place :
>
> ```bash
> cat > .gitignore <<'EOF'
> .env
> .DS_Store
> vendor/
> node_modules/
> postgres_data/
> *.log
> EOF
> ```

Ajoute les fichiers et fais le premier commit :

```powershell
git add .
git commit -m "Initial BudgetFlow auth and docker setup"
```

## 4. Créer un dépôt distant

Crée un repository vide sur GitHub ou GitLab, par exemple :

```
https://github.com/USERNAME/budgetflow.git
```

Connecte le projet local au dépôt distant :

```powershell
git remote add origin https://github.com/USERNAME/budgetflow.git
git branch -M main
git push -u origin main
```

Remplace `USERNAME` par ton nom d'utilisateur GitHub.

> **Première fois sur GitHub ?** Git va ouvrir une fenêtre de connexion GitHub dans le navigateur. Connecte-toi et autorise l'accès.

## 5. Envoyer des changements plus tard

À chaque modification :

```powershell
git status
git add .
git commit -m "Describe your change"
git push
```

Voir l'historique :

```powershell
git log --oneline
```

## 6. Récupérer le projet sur une autre machine

Ton ami clone le projet :

```powershell
git clone https://github.com/USERNAME/budgetflow.git
cd budgetflow
```

Puis lance Docker :

```powershell
docker compose up -d --build
```

Ouvrir le site dans le navigateur :

```
http://localhost:8000
```

Compte admin de test :

```
Email: admin@budgetflow.local
Password: password
```

## 7. Comment Docker fonctionne dans ce projet

Le fichier `docker-compose.yml` démarre trois services :

| Service    | Rôle                                            |
| ---------- | ----------------------------------------------- |
| `nginx`    | Serveur web accessible sur `localhost:8000`     |
| `php`      | Conteneur PHP 8.3 FPM qui exécute l'application |
| `postgres` | Base PostgreSQL 16                              |

Nginx reçoit la requête HTTP, puis transmet les fichiers PHP au service `php`.

PHP se connecte à la base avec ces variables :

```yaml
DB_HOST: postgres
DB_PORT: 5432
DB_NAME: budgetflow
DB_USER: budgetflow
DB_PASSWORD: budgetflow
```

> **Important** : Dans Docker, `DB_HOST` doit être `postgres`, pas `localhost`.

## 8. Base de données

Le schéma est dans :

```
database/schema.sql
```

Il crée les 5 tables :

- `users`
- `categories`
- `budgets`
- `transactions`
- `budget_members`

Il crée aussi :

- un admin de test
- les catégories par défaut

PostgreSQL exécute `schema.sql` seulement au premier démarrage du volume Docker.

## 9. Réinitialiser la base

Si tu modifies `database/schema.sql`, supprime le volume PostgreSQL pour rejouer le script :

```powershell
docker compose down -v
docker compose up -d --build
```

> **Attention** : `down -v` supprime toutes les données de la base.

## 10. Commandes Docker utiles

Lancer les conteneurs :

```powershell
docker compose up -d --build
```

Voir les conteneurs actifs :

```powershell
docker compose ps
```

Voir les logs en temps réel :

```powershell
docker compose logs -f
```

Voir les logs PostgreSQL uniquement :

```powershell
docker compose logs postgres
```

Arrêter les conteneurs :

```powershell
docker compose down
```

Arrêter et supprimer la base de données :

```powershell
docker compose down -v
```

Entrer dans PostgreSQL :

```powershell
docker compose exec postgres psql -U budgetflow -d budgetflow
```

Lister les utilisateurs :

```powershell
docker compose exec postgres psql -U budgetflow -d budgetflow -c "SELECT id, email, role, is_active FROM users;"
```

## 11. Connexion avec Beekeeper Studio (interface graphique PostgreSQL)

Télécharge Beekeeper Studio → https://www.beekeeperstudio.io

Dans `docker-compose.yml`, expose le port PostgreSQL :

```yaml
postgres:
  ports:
    - "5433:5432"
```

Puis relance Docker :

```powershell
docker compose down
docker compose up -d
```

Dans Beekeeper Studio, crée une nouvelle connexion :

```
Connection type : PostgreSQL
Host            : localhost
Port            : 5433
Database        : budgetflow
User            : budgetflow
Password        : budgetflow
SSL             : disabled
```

> On utilise `5433` sur la machine locale pour éviter le conflit avec un PostgreSQL local déjà installé sur `5432`.

## 12. Problèmes fréquents sur Windows

### Le port 8000 est déjà utilisé

Change le port dans `docker-compose.yml` :

```yaml
ports:
  - "8001:80"
```

Puis ouvre :

```
http://localhost:8001
```

### Le login admin ne marche pas

```powershell
docker compose down -v
docker compose up -d --build
```

Reconnecte-toi avec :

```
admin@budgetflow.local
password
```

### Docker Desktop ne démarre pas

- Vérifie que la virtualisation est activée dans le BIOS
- Vérifie que WSL 2 est installé :
  ```powershell
  wsl --status
  ```
- Redémarre Docker Desktop en tant qu'administrateur

### `docker compose` non reconnu

Si tu as une ancienne version de Docker, utilise :

```powershell
docker-compose up -d --build
```

### Erreur de permissions sur les fichiers

Sur Windows avec Docker Desktop, les fichiers montés en volume peuvent avoir des problèmes de permissions. Solution : dans Docker Desktop → Settings → Resources → File Sharing → ajoute le dossier de ton projet.

### PostgreSQL ne démarre pas

```powershell
docker compose logs postgres
```

Si tu vois une erreur de volume corrompu :

```powershell
docker compose down -v
docker volume prune
docker compose up -d --build
```

### Git : fin de ligne CRLF / LF

Sur Windows, Git peut convertir les fins de ligne et causer des erreurs dans les scripts shell Docker. Configure Git pour éviter ça :

```powershell
git config --global core.autocrlf false
```

À faire **avant** le premier `git clone` ou `git add`.

## 13. Fichiers importants à partager

Ces fichiers doivent être dans Git :

```
app/
config/
core/
database/schema.sql
docker/
docker-compose.yml
public/
work.md
push.md
```

Ne pousse **jamais** :

- mots de passe réels
- fichiers `.env` privés
- dumps de base de données
- volumes Docker locaux (`postgres_data/`)
