<?php require_once __DIR__ . '/bootstrap.php'; $pageTitle = $pageTitle ?? 'MUKASA POINTAGE'; ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | MUKASA SARL</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark app-nav">
  <div class="container">
    <a class="navbar-brand fw-bold d-inline-flex align-items-center gap-2" href="index.php"><img src="assets/img/logo-mukasa.jpg" alt="Logo MUKASA SARL" class="navbar-logo"></a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navMenu"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="navMenu">
      <div class="navbar-nav ms-auto align-items-lg-center">
        <?php if (is_admin()): ?>
          <a class="nav-link" href="admin.php">Tableau de bord</a>
          <a class="nav-link" href="employees.php">Employés</a>
          <a class="nav-link" href="attendance.php">Présences</a>
          <a class="nav-link" href="reports.php">Rapports</a>
          <a class="nav-link" href="logout.php">Déconnexion</a>
        <?php elseif (!empty($_SESSION['employee_id'])): ?>
          <a class="nav-link" href="index.php">Mon pointage</a>
          <a class="nav-link" href="logout.php">Déconnexion</a>
        <?php else: ?><a class="nav-link" href="login.php">Connexion</a><?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<main class="container py-4 py-lg-5">
<?php render_flash(); ?>
