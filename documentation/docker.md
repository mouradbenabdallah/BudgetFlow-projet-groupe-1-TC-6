# Docker setup for BudgetFlow

This project uses Docker Compose to run the application with 3 containers:

- `nginx`: the web server that receives browser requests
- `php`: the PHP-FPM container that executes the PHP code
- `postgres`: the PostgreSQL database container that stores the data

The goal is to separate the responsibilities. Nginx handles HTTP traffic, PHP runs the application logic, and PostgreSQL stores users and application data.

## 1. Project structure

The Docker files used by the project are:

```text
budgetflow/
├── docker-compose.yml
├── docker/
│   ├── nginx.conf
│   └── php.Dockerfile
├── database/
│   └── schema.sql
├── public/
│   └── index.php
└── config/
    └── config.php
```

## 2. Container 1: PHP

The PHP container is created from `docker/php.Dockerfile`.

```dockerfile
FROM php:8.3-fpm-alpine

RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www/html

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
```

What this does:

- `php:8.3-fpm-alpine` gives us PHP-FPM, not Apache.
- `pdo` and `pdo_pgsql` allow PHP to connect to PostgreSQL.
- `WORKDIR /var/www/html` sets the application folder inside the container.
- `COPY . /var/www/html` copies the project into the container image.
- `EXPOSE 9000` means PHP-FPM listens on port `9000`.

Important: the browser does not talk directly to PHP. Nginx sends PHP requests to this container using `php:9000`.

## 3. Container 2: Nginx

The Nginx container uses the official image:

```yaml
nginx:
  image: nginx:1.25-alpine
  container_name: budgetflow_nginx
  restart: unless-stopped
  ports:
    - "8000:80"
  volumes:
    - .:/var/www/html
    - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf:ro
  depends_on:
    - php
  networks:
    - budgetflow
```

What this does:

- `image: nginx:1.25-alpine` downloads an existing Nginx image.
- `ports: "8000:80"` means:
  - your computer opens `localhost:8000`
  - inside the container, Nginx listens on port `80`
- `volumes: .:/var/www/html` shares the local project files with the container.
- `./docker/nginx.conf:/etc/nginx/conf.d/default.conf:ro` replaces the default Nginx config with our project config.
- `depends_on: php` starts PHP before Nginx.

The Nginx configuration is in `docker/nginx.conf`.

```nginx
server {
    listen 80;
    server_name _;
    root /var/www/html/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $fastcgi_script_name =404;
        include fastcgi_params;
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }
}
```

Important lines:

- `root /var/www/html/public;` means the public web folder is `public/`.
- `try_files $uri $uri/ /index.php?$query_string;` sends clean URLs like `/login` to `public/index.php`.
- `fastcgi_pass php:9000;` sends PHP execution to the PHP container.

The name `php` works because Docker Compose creates internal DNS names from service names.

## 4. Container 3: PostgreSQL

The PostgreSQL container is defined in `docker-compose.yml`.

```yaml
postgres:
  image: postgres:16-alpine
  container_name: budgetflow_postgres
  restart: unless-stopped
  environment:
    POSTGRES_DB: budgetflow
    POSTGRES_USER: budgetflow
    POSTGRES_PASSWORD: budgetflow
  volumes:
    - postgres_data:/var/lib/postgresql/data
    - ./database/schema.sql:/docker-entrypoint-initdb.d/01_schema.sql:ro
  networks:
    - budgetflow
```

What this does:

- `image: postgres:16-alpine` downloads PostgreSQL 16.
- `POSTGRES_DB` creates the database named `budgetflow`.
- `POSTGRES_USER` creates the database user `budgetflow`.
- `POSTGRES_PASSWORD` sets the password.
- `postgres_data:/var/lib/postgresql/data` keeps database data even if containers stop.
- `./database/schema.sql:/docker-entrypoint-initdb.d/01_schema.sql:ro` runs the SQL schema the first time the database volume is created.

PostgreSQL is not exposed to the host machine. Only the PHP container uses it through the internal Docker network.

## 5. How the containers communicate

All containers are connected to the same Docker network:

```yaml
networks:
  budgetflow:
    driver: bridge
```

Because they are on the same network:

- Nginx can call PHP using `php:9000`.
- PHP can call PostgreSQL using `postgres:5432`.
- The browser can only access Nginx through `localhost:8000`.

Request flow:

```text
Browser
  |
  | http://localhost:8000
  v
Nginx container
  |
  | PHP file request
  v
PHP-FPM container
  |
  | SQL query
  v
PostgreSQL container
```

## 6. Environment variables

The PHP container receives database configuration from `docker-compose.yml`.

```yaml
environment:
  APP_ENV: local
  APP_URL: http://localhost:8000
  APP_TIMEZONE: Africa/Tunis
  DB_HOST: postgres
  DB_PORT: 5432
  DB_NAME: budgetflow
  DB_USER: budgetflow
  DB_PASSWORD: budgetflow
```

These variables are read in `config/config.php` using `getenv()`.

The most important variable is:

```text
DB_HOST=postgres
```

Inside Docker, PHP must not use `localhost` for the database. If PHP uses `localhost`, it will search for PostgreSQL inside the PHP container. The correct host is `postgres`, which is the service name of the database container.

## 7. Step-by-step creation

### Step 1: Create the PHP Dockerfile

Create this file:

```text
docker/php.Dockerfile
```

Its job is to build the PHP container and install PostgreSQL support for PHP.

### Step 2: Create the Nginx config

Create this file:

```text
docker/nginx.conf
```

Its job is to:

- serve files from `public/`
- redirect all clean URLs to `public/index.php`
- send PHP files to `php:9000`

### Step 3: Create the Docker Compose file

Create this file:

```text
docker-compose.yml
```

Its job is to define and connect the 3 services:

- `php`
- `nginx`
- `postgres`

### Step 4: Create the database schema

Create this file:

```text
database/schema.sql
```

This file contains the SQL tables. Docker runs it automatically the first time PostgreSQL starts with a new empty volume.

### Step 5: Start the containers

Run:

```sh
docker compose up -d --build
```

What this command does:

- builds the PHP image from `docker/php.Dockerfile`
- downloads Nginx and PostgreSQL images if needed
- creates the `budgetflow` network
- creates the `postgres_data` volume
- starts the 3 containers in the background

### Step 6: Open the application

Open:

```text
http://localhost:8000
```

The request goes to Nginx, then Nginx sends PHP work to the PHP container.

## 8. Useful Docker commands

Start containers:

```sh
docker compose up -d
```

Start and rebuild:

```sh
docker compose up -d --build
```

Stop containers:

```sh
docker compose down
```

Stop containers and delete database data:

```sh
docker compose down -v
```

Warning: `docker compose down -v` deletes the PostgreSQL volume, so the database data will be removed.

Show running containers:

```sh
docker compose ps
```

Show logs:

```sh
docker compose logs
```

Show logs for one service:

```sh
docker compose logs nginx
docker compose logs php
docker compose logs postgres
```

Enter the PHP container:

```sh
docker compose exec php sh
```

Enter the PostgreSQL database:

```sh
docker compose exec postgres psql -U budgetflow -d budgetflow
```

## 9. Summary

BudgetFlow uses 3 containers:

```text
nginx    -> receives browser requests on localhost:8000
php      -> runs the PHP application with PHP-FPM
postgres -> stores the database
```

They work together through the `budgetflow` Docker network. Nginx talks to PHP using `php:9000`, and PHP talks to PostgreSQL using `postgres:5432`.
