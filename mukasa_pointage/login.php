<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (is_admin()) { header('Location: admin.php'); exit; }
if (!empty($_SESSION['employee_id'])) { header('Location: index.php'); exit; }

$error = '';
$step = 'matricule';
$matricule = strtoupper(trim((string)($_POST['matricule'] ?? $_SESSION['pending_admin_matricule'] ?? '')));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $submittedStep = (string)($_POST['step'] ?? 'matricule');
    if (!preg_match('/^[A-Z0-9-]{3,30}$/', $matricule)) {
        $error = 'Veuillez saisir un matricule valide.';
    } elseif ($submittedStep === 'matricule') {
        // The administrator lookup comes first so an admin matricule triggers the password step.
        $stmt = $pdo->prepare("SELECT id, matricule, role, username FROM admins WHERE matricule=? AND role='admin' LIMIT 1");
        $stmt->execute([$matricule]);
        $admin = $stmt->fetch();
        if ($admin) {
            $_SESSION['pending_admin_id'] = (int)$admin['id'];
            $_SESSION['pending_admin_matricule'] = $admin['matricule'];
            $step = 'password';
        } else {
            $stmt = $pdo->prepare("SELECT id, matricule, role FROM employees WHERE matricule=? AND statut='actif' LIMIT 1");
            $stmt->execute([$matricule]);
            $employee = $stmt->fetch();
            if ($employee && $employee['role'] === 'employe') {
                session_regenerate_id(true);
                $_SESSION['employee_id'] = (int)$employee['id'];
                $_SESSION['employee_matricule'] = $employee['matricule'];
                unset($_SESSION['pending_admin_id'], $_SESSION['pending_admin_matricule']);
                header('Location: index.php'); exit;
            }
            $error = 'Matricule introuvable ou compte inactif. Vérifiez-le auprès de l’administration.';
        }
    } elseif ($submittedStep === 'password') {
        $pendingId = (int)($_SESSION['pending_admin_id'] ?? 0);
        $pendingMatricule = (string)($_SESSION['pending_admin_matricule'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        if (!$pendingId || !hash_equals($pendingMatricule, $matricule)) {
            unset($_SESSION['pending_admin_id'], $_SESSION['pending_admin_matricule']);
            $error = 'La session de connexion a expiré. Saisissez de nouveau votre matricule.';
        } else {
            $stmt = $pdo->prepare("SELECT id, matricule, role, username, password_hash FROM admins WHERE id=? AND matricule=? AND role='admin' LIMIT 1");
            $stmt->execute([$pendingId, $pendingMatricule]);
            $admin = $stmt->fetch();
            if ($admin && password_verify($password, $admin['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int)$admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_matricule'] = $admin['matricule'];
                unset($_SESSION['pending_admin_id'], $_SESSION['pending_admin_matricule'], $_SESSION['employee_id'], $_SESSION['employee_matricule']);
                header('Location: admin.php'); exit;
            }
            $step = 'password';
            $error = 'Mot de passe incorrect. Veuillez réessayer.';
        }
    }
}

$pageTitle = 'Connexion';
require __DIR__ . '/includes/header.php';
?>
<div class="login-wrap"><div class="panel">
  <div class="text-center mb-4"><img src="assets/img/logo-mukasa.jpg" alt="Logo MUKASA SARL" class="login-logo mb-3"><h1 class="h3 section-title">Connexion MUKASA</h1><p class="small-muted">Saisissez votre matricule pour accéder à votre espace.</p></div>
  <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
  <?php if ($step === 'password'): ?>
    <form method="post" autocomplete="on">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="step" value="password">
      <label for="matricule" class="form-label fw-semibold">Matricule administrateur</label>
      <input class="form-control mb-3 text-uppercase" id="matricule" name="matricule" value="<?= e($matricule) ?>" readonly>
      <label for="password" class="form-label fw-semibold">Mot de passe</label>
      <input class="form-control mb-4" id="password" name="password" type="password" required autofocus autocomplete="current-password">
      <button class="btn btn-primary w-100" type="submit">Accéder au tableau de bord</button>
      <a class="btn btn-light border w-100 mt-2" href="login.php">Changer de matricule</a>
    </form>
  <?php else: ?>
    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="step" value="matricule">
      <label for="matricule" class="form-label fw-semibold">Matricule</label>
      <input class="form-control form-control-lg mb-4 text-uppercase" id="matricule" name="matricule" placeholder="Ex. AK001" maxlength="30" required autofocus value="<?= e($matricule) ?>">
      <button class="btn btn-primary w-100" type="submit">Se connecter</button>
    </form>
  <?php endif; ?>
</div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
