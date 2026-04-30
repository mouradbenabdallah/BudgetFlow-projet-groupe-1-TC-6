<?php

declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['app']['timezone']);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/CSRF.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

// Démarre la session avant d'utiliser Auth, CSRF ou les flash messages.
new Session();

$router = new Router();

// Route racine : affiche la page d'accueil publique.
$router->get('/', function (): void {
    require __DIR__ . '/../app/views/home.php';
});

// Routes de la fonction 1 : inscription, connexion et déconnexion.
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/logout', [AuthController::class, 'logout']);

// Pages provisoires pour vérifier les redirections par rôle.
$router->get('/dashboard', function (): void {
    require __DIR__ . '/../app/views/dashboard_placeholder.php';
});

$router->get('/admin', function (): void {
    require __DIR__ . '/../app/views/admin_placeholder.php';
});

$router->dispatch();
