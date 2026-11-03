<?php
require_once __DIR__ . '/includes/bootstrap.php'; require_admin();
$pageTitle='Tableau de bord';
$date=$_GET['date'] ?? date('Y-m-d'); if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)) $date=date('Y-m-d');
$entityId=(int)($_GET['entity_id'] ?? 0);
$entities=$pdo->query('SELECT id,nom FROM entities WHERE actif=1 ORDER BY id')->fetchAll();
$where='a.work_date=?'; $params=[$date]; if($entityId){$where.=' AND e.entity_id=?';$params[]=$entityId;}
$q=$pdo->prepare("SELECT COUNT(*) total, SUM(a.heure_arrivee IS NOT NULL) arrives, SUM(a.heure_sortie IS NOT NULL) sorties FROM attendance a JOIN employees e ON e.id=a.employee_id WHERE $where");$q->execute($params);$stats=$q->fetch();
$q=$pdo->prepare("SELECT e.matricule,e.nom,e.prenom,ent.nom entite,a.heure_arrivee,a.heure_sortie,CASE WHEN a.heure_arrivee IS NULL THEN 'Arrivée manquante' WHEN a.heure_sortie IS NULL THEN 'Sortie manquante' ELSE 'Pointage complet' END etat FROM employees e JOIN entities ent ON ent.id=e.entity_id LEFT JOIN attendance a ON a.employee_id=e.id AND a.work_date=? WHERE e.statut='actif'".($entityId?' AND e.entity_id=?':'')." ORDER BY ent.id,e.nom");
$params2=[$date];if($entityId)$params2[]=$entityId;$q=$pdo->prepare("SELECT e.matricule,e.nom,e.prenom,ent.nom entite,a.heure_arrivee,a.heure_sortie,CASE WHEN a.id IS NULL THEN 'À vérifier' WHEN a.heure_arrivee IS NULL THEN 'Arrivée manquante' WHEN a.heure_sortie IS NULL THEN 'Sortie manquante' ELSE 'Pointage complet' END etat FROM employees e JOIN entities ent ON ent.id=e.entity_id LEFT JOIN attendance a ON a.employee_id=e.id AND a.work_date=? WHERE e.statut='actif'".($entityId?' AND e.entity_id=?':'')." ORDER BY ent.id,e.nom");$q->execute($params2);$rows=$q->fetchAll();
$totalEmployees=count($rows);$present=count(array_filter($rows,fn($r)=>!empty($r['heure_arrivee'])));$complete=count(array_filter($rows,fn($r)=>!empty($r['heure_arrivee'])&&!empty($r['heure_sortie'])));
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4"><div><div class="eyebrow">Espace de contrôle</div><h1 class="h2 section-title mb-1">Tableau de bord</h1><p class="small-muted mb-0">Bonjour <?= e($_SESSION['admin_username'] ?? 'Administrateur') ?>, voici le suivi du personnel.</p></div><a class="btn btn-outline-primary" href="reports.php?period=daily&date=<?= e($date) ?>&entity_id=<?= $entityId ?>">Rapports et exports</a></div>
<form class="panel mb-4" method="get"><div class="row g-3 align-items-end"><div class="col-sm-5"><label class="form-label fw-semibold">Date</label><input type="date" class="form-control" name="date" value="<?= e($date) ?>"></div><div class="col-sm-5"><label class="form-label fw-semibold">Entité</label><select class="form-select" name="entity_id"><option value="0">Toutes les entités</option><?php foreach($entities as $ent): ?><option value="<?= $ent['id'] ?>" <?= $entityId===$ent['id']?'selected':'' ?>><?= e($ent['nom']) ?></option><?php endforeach; ?></select></div><div class="col-sm-2"><button class="btn btn-primary w-100">Filtrer</button></div></div></form>
<div class="row g-3 mb-4">
<div class="col-6 col-xl-3"><div class="stat-card"><div class="stat-label">Employés actifs</div><div class="stat-value"><?= $totalEmployees ?></div><div class="small-muted">Dans le filtre choisi</div></div></div>
<div class="col-6 col-xl-3"><div class="stat-card"><div class="stat-label">Arrivées enregistrées</div><div class="stat-value"><?= $present ?></div><div class="small-muted">Pour le <?= e(date('d/m/Y',strtotime($date))) ?></div></div></div>
<div class="col-6 col-xl-3"><div class="stat-card"><div class="stat-label">Pointages complets</div><div class="stat-value"><?= $complete ?></div><div class="small-muted">Arrivée et sortie</div></div></div>
<div class="col-6 col-xl-3"><div class="stat-card"><div class="stat-label">À vérifier</div><div class="stat-value"><?= $totalEmployees-$complete ?></div><div class="small-muted">Oublis ou absence à vérifier</div></div></div>
</div>
<section class="panel"><div class="d-flex justify-content-between flex-wrap gap-2 mb-3"><div><h2 class="h5 section-title mb-1">Suivi journalier</h2><div class="small-muted">L’absence de pointage ne prouve pas à elle seule une absence.</div></div><a class="btn btn-primary btn-sm align-self-start" href="attendance.php?date=<?= e($date) ?>">Gérer les pointages</a></div>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Matricule</th><th>Employé</th><th>Entité</th><th>Arrivée</th><th>Sortie</th><th>État</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td class="fw-semibold"><?= e($r['matricule']) ?></td><td><?= e(trim($r['prenom'].' '.$r['nom'])) ?></td><td><span class="badge pill-site"><?= e($r['entite']) ?></span></td><td><?= $r['heure_arrivee']?e(substr($r['heure_arrivee'],0,5)):'—' ?></td><td><?= $r['heure_sortie']?e(substr($r['heure_sortie'],0,5)):'—' ?></td><td><span class="badge <?= $r['etat']==='Pointage complet'?'text-bg-success':'text-bg-warning' ?>"><?= e($r['etat']) ?></span></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="6" class="empty-state">Aucun employé actif.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
