<?php
Auth::requireRole('admin');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panneau Admin - BudgetFlow</title>
    <link href="/style.css" rel="stylesheet">
</head>
<body class="bg-light bf-page-admin">
    <main class="container py-5">
        <div class="card shadow-sm">
            <div class="card-body d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                <h1 class="h4 mb-0">Panneau Admin</h1>
                <a class="btn btn-outline-danger" href="/logout">Déconnexion</a>
            </div>
        </div>
    </main>
</body>
</html>
