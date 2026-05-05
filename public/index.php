<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/CSRF.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Category.php';
require_once __DIR__ . '/../app/models/Budget.php';
require_once __DIR__ . '/../app/models/Transaction.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/DashboardController.php';
require_once __DIR__ . '/../app/controllers/TransactionController.php';
require_once __DIR__ . '/../app/controllers/CategoryController.php';

$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['app']['timezone']);

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
$router->post('/logout', [AuthController::class, 'logout']);

// Fonction 2 : tableau de bord utilisateur.
$router->get('/dashboard', function (): void {
    Auth::requireRole('user');
    $controller = new DashboardController();
    $controller->index();
});

// Fonction 3 : gestion des transactions et des catégories.
$router->get('/transactions', function (): void {
    Auth::requireRole('user');
    $controller = new TransactionController();
    $controller->index();
});
$router->get('/transactions/create', function (): void {
    Auth::requireRole('user');
    $controller = new TransactionController();
    $controller->showCreate();
});
$router->post('/transactions/create', function (): void {
    Auth::requireRole('user');
    $controller = new TransactionController();
    $controller->create();
});
$router->get('/transactions/edit', function (): void {
    Auth::requireRole('user');
    $controller = new TransactionController();
    $controller->showEdit();
});
$router->post('/transactions/edit', function (): void {
    Auth::requireRole('user');
    $controller = new TransactionController();
    $controller->edit();
});
$router->post('/transactions/delete', function (): void {
    Auth::requireRole('user');
    $controller = new TransactionController();
    $controller->delete();
});

$router->get('/categories', function (): void {
    Auth::requireRole('user');
    $controller = new CategoryController();
    $controller->index();
});
$router->post('/categories/create', function (): void {
    Auth::requireRole('user');
    $controller = new CategoryController();
    $controller->create();
});
$router->post('/categories/edit', function (): void {
    Auth::requireRole('user');
    $controller = new CategoryController();
    $controller->edit();
});
$router->post('/categories/delete', function (): void {
    Auth::requireRole('user');
    $controller = new CategoryController();
    $controller->delete();
});

$router->get('/admin', function (): void {
    Auth::requireRole('admin');
    require __DIR__ . '/../app/views/admin_placeholder.php';
});

$router->dispatch();
