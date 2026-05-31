<?php

declare(strict_types=1);

/**
 * Entry point — every HTTP request is routed through this file.
 *
 * Responsibilities:
 *  1. Load all core classes, models, and controllers (no autoloader).
 *  2. Apply application-level configuration (timezone).
 *  3. Start the session.
 *  4. Register every route and dispatch the request.
 *
 * Route convention:
 *  - GET  routes display pages or return data.
 *  - POST routes mutate state; all require a valid CSRF token.
 *  - IDs are passed via query-string (e.g. ?id=5), not URL segments.
 */

// Core framework classes
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/CSRF.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Mailer.php';

// Models
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Category.php';
require_once __DIR__ . '/../app/models/Budget.php';
require_once __DIR__ . '/../app/models/Transaction.php';

// Controllers
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/DashboardController.php';
require_once __DIR__ . '/../app/controllers/TransactionController.php';
require_once __DIR__ . '/../app/controllers/CategoryController.php';
require_once __DIR__ . '/../app/controllers/BudgetController.php';
require_once __DIR__ . '/../app/controllers/ProfileController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';

$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['app']['timezone']);

// Session must start before Auth, CSRF, or flash messages are used.
new Session();

$router = new Router();

// ── Public ────────────────────────────────────────────────────────────────────
$router->get('/', function (): void {
    require __DIR__ . '/../app/views/home.php';
});

// ── Auth ──────────────────────────────────────────────────────────────────────
$router->get('/login',    [AuthController::class, 'showLogin']);
$router->post('/login',   [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register',[AuthController::class, 'register']);
$router->post('/logout',  [AuthController::class, 'logout']);

// ── Dashboard ─────────────────────────────────────────────────────────────────
$router->get('/dashboard', [DashboardController::class, 'index']);

// ── Transactions ──────────────────────────────────────────────────────────────
$router->get('/transactions',         [TransactionController::class, 'index']);
$router->get('/transactions/create',  [TransactionController::class, 'showCreate']);
$router->post('/transactions/create', [TransactionController::class, 'create']);
$router->get('/transactions/edit',    [TransactionController::class, 'showEdit']);
$router->post('/transactions/edit',   [TransactionController::class, 'edit']);
$router->post('/transactions/delete', [TransactionController::class, 'delete']);

// ── Categories ────────────────────────────────────────────────────────────────
$router->get('/categories',         [CategoryController::class, 'index']);
$router->post('/categories/create', [CategoryController::class, 'create']);
$router->post('/categories/edit',   [CategoryController::class, 'edit']);
$router->post('/categories/delete', [CategoryController::class, 'delete']);

// ── Budgets ───────────────────────────────────────────────────────────────────
$router->get('/budgets',                [BudgetController::class, 'index']);
$router->get('/budgets/shared',         [BudgetController::class, 'sharedIndex']);
$router->get('/budgets/create',         [BudgetController::class, 'showCreate']);
$router->post('/budgets/create',        [BudgetController::class, 'create']);
$router->get('/budgets/show',           [BudgetController::class, 'show']);
$router->get('/budgets/edit',           [BudgetController::class, 'showEdit']);
$router->post('/budgets/edit',          [BudgetController::class, 'edit']);
$router->post('/budgets/delete',        [BudgetController::class, 'delete']);
$router->post('/budgets/invite',        [BudgetController::class, 'invite']);
$router->post('/budgets/remove-member', [BudgetController::class, 'removeMember']);

// ── Profile ───────────────────────────────────────────────────────────────────
$router->get('/profile',                  [ProfileController::class, 'index']);
$router->post('/profile/update-info',     [ProfileController::class, 'updateInfo']);
$router->post('/profile/update-password', [ProfileController::class, 'updatePassword']);
$router->post('/profile/request-deletion',[ProfileController::class, 'requestDeletion']);

// ── Admin ─────────────────────────────────────────────────────────────────────
$router->get('/admin',                       [AdminController::class, 'index']);
$router->post('/admin/test-email',           [AdminController::class, 'testEmail']);
$router->get('/admin/users',                 [AdminController::class, 'users']);
$router->get('/admin/users/export',          [AdminController::class, 'exportUsers']);
$router->post('/admin/users/validate',       [AdminController::class, 'validateUser']);
$router->post('/admin/users/role',           [AdminController::class, 'changeRole']);
$router->post('/admin/users/delete',         [AdminController::class, 'deleteUser']);
$router->post('/admin/users/reset-password', [AdminController::class, 'resetPassword']);
$router->get('/admin/budgets',               [AdminController::class, 'budgets']);
$router->get('/admin/profile',               [AdminController::class, 'profile']);
$router->get('/admin/send-email',            [AdminController::class, 'showSendEmail']);
$router->post('/admin/send-email',           [AdminController::class, 'sendBulkEmail']);

$router->dispatch();
