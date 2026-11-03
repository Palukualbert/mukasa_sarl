<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (is_admin()) { header('Location: admin.php'); exit; }
if (empty($_SESSION['employee_id'])) { header('Location: login.php'); exit; }
$pageTitle = 'Pointer ma présence';
$notice = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $matricule = (string)($_SESSION['employee_matricule'] ?? '');
    $action = $_POST['action'] ?? '';
    if (!preg_match('/^[A-Z0-9-]{3,30}$/', $matricule) || !in_array($action, ['arrivee','sortie'], true)) {
        $notice = ['danger', 'Session invalide. Veuillez vous reconnecter.'];
    } else {
        try {
            $stmt = $pdo->prepare("SELECT e.id,e.nom,e.prenom,e.matricule,e.statut,ent.nom AS entite FROM employees e JOIN entities ent ON ent.id=e.entity_id WHERE e.matricule=? AND e.statut='actif' LIMIT 1");
            $stmt->execute([$matricule]); $employee = $stmt->fetch();
            if (!$employee) {
                $notice = ['danger', 'Matricule introuvable ou employé inactif. Vérifiez la saisie auprès de l’administration.'];
            } else {
                $today = date('Y-m-d');
                $pdo->beginTransaction();
                $q = $pdo->prepare('SELECT * FROM attendance WHERE employee_id=? AND work_date=? FOR UPDATE');
                $q->execute([$employee['id'],$today]); $record = $q->fetch();
                if ($action === 'arrivee') {
                    if ($record && $record['heure_arrivee']) {
                        $pdo->rollBack(); $notice = ['warning', 'Votre arrivée a déjà été enregistrée aujourd’hui à '.date('H:i', strtotime($record['heure_arrivee'])).'.'];
                    } else {
                        $now = date('H:i:s');
                        if ($record) {
                            $u=$pdo->prepare('UPDATE attendance SET heure_arrivee=?,updated_at=NOW() WHERE id=?'); $u->execute([$now,$record['id']]);
                        } else {
                            $u=$pdo->prepare('INSERT INTO attendance(employee_id,work_date,heure_arrivee) VALUES(?,?,?)'); $u->execute([$employee['id'],$today,$now]);
                        }
                        $pdo->commit();
                        $notice = ['success', 'Arrivée enregistrée à '.date('H:i',strtotime($now)).' — '.trim($employee['prenom'].' '.$employee['nom']).' ('.$employee['entite'].').'];
                    }
                } else {
                    if (!$record || !$record['heure_arrivee']) {
                        $pdo->rollBack(); $notice = ['danger', 'Impossible d’enregistrer une sortie sans pointage d’arrivée.'];
                    } elseif ($record['heure_sortie']) {
                        $pdo->rollBack(); $notice = ['warning', 'Votre sortie a déjà été enregistrée aujourd’hui à '.date('H:i',strtotime($record['heure_sortie'])).'.'];
                    } else {
                        $now=date('H:i:s'); $u=$pdo->prepare('UPDATE attendance SET heure_sortie=?,updated_at=NOW() WHERE id=?'); $u->execute([$now,$record['id']]); $pdo->commit();
                        $notice = ['success', 'Sortie enregistrée à '.date('H:i',strtotime($now)).' — bonne fin de journée, '.trim($employee['prenom'].' '.$employee['nom']).' !'];
                    }
                }
            }
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $notice = ['danger', 'Le pointage n’a pas pu être enregistré. Réessayez ou contactez l’administration.'];
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="hero mb-4 mb-lg-5">
  <div class="eyebrow mb-3">MUKASA SARL · Gestion du personnel</div>
  <h1 class="display-5 fw-bold mb-3">Votre présence, enregistrée simplement.</h1>
  <p class="fs-5 mb-4">Saisissez votre matricule pour enregistrer votre arrivée ou votre sortie. L’heure est générée automatiquement par le système.</p>
</section>
<div class="row g-4 align-items-stretch">
 <div class="col-lg-7">
  <section class="panel h-100">
   <h2 class="h4 section-title mb-1">Enregistrer un pointage</h2><p class="small-muted mb-4">Connecté avec le matricule <strong><?= e($_SESSION['employee_matricule'] ?? '') ?></strong>.</p>
   <?php if ($notice): ?><div class="alert alert-<?= e($notice[0]) ?>"><?= e($notice[1]) ?></div><?php endif; ?>
   <form method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="row g-3">
      <div class="col-sm-6"><button class="btn btn-success attendance-action w-100" name="action" value="arrivee"><span class="action-icon">↘</span><span>Enregistrer l’arrivée</span><small class="fw-normal">Début de journée</small></button></div>
      <div class="col-sm-6"><button class="btn btn-primary attendance-action w-100" name="action" value="sortie"><span class="action-icon">↗</span><span>Enregistrer la sortie</span><small class="fw-normal">Fin de journée</small></button></div>
    </div>
   </form>
   <p class="small-muted mt-3 mb-0"><span class="text-success fw-bold">●</span> Date et heure calculées par le serveur. <a href="logout.php">Changer d’utilisateur</a></p>
  </section>
 </div>
 <div class="col-lg-5">
  <section class="panel h-100">
   <h2 class="h5 section-title mb-3">Comment ça marche ?</h2>
   <div class="d-flex gap-3 mb-4"><div class="rounded-circle bg-primary-subtle text-primary fw-bold p-3">1</div><div><strong>Identifiez-vous</strong><div class="small-muted">Saisissez votre matricule sur la page de connexion.</div></div></div>
   <div class="d-flex gap-3 mb-4"><div class="rounded-circle bg-info-subtle text-info fw-bold p-3">2</div><div><strong>Choisissez votre pointage</strong><div class="small-muted">Arrivée le matin, sortie en fin de journée.</div></div></div>
   <div class="d-flex gap-3"><div class="rounded-circle bg-success-subtle text-success fw-bold p-3">3</div><div><strong>Vérifiez la confirmation</strong><div class="small-muted">Le système confirme l’enregistrement ou indique le problème.</div></div></div>
  </section>
 </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
