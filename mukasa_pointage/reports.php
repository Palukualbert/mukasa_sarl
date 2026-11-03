<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();
require_once __DIR__ . '/includes/reporting.php';

$pageTitle = 'Rapports de présence';
$f = rp_filters($pdo);
$data = rp_get_data($pdo, $f);
$s = $data['summary'];
$entityName = 'Toutes les entités';
foreach ($f['entities'] as $ent) if ((int)$ent['id'] === $f['entityId']) $entityName = $ent['nom'];
$query = $_GET; unset($query['format']);
$exportQuery = http_build_query($query);
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
  <div><div class="eyebrow">Analyse du personnel</div><h1 class="h2 section-title mb-1">Rapports de présence</h1><p class="small-muted mb-0">Bureau, Site 1 et Site 2 — données centralisées.</p></div>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-outline-success" href="export.php?<?= e($exportQuery) ?>&amp;format=csv">Télécharger le rapport (.csv)</a>
  </div>
</div>
<form class="panel mb-4" method="get" id="reportFilter">
  <div class="row g-3 align-items-end">
    <div class="col-12 col-md-4"><label class="form-label fw-semibold" for="period">Période du rapport</label><select class="form-select" name="period" id="period"><option value="daily" <?= $f['period']==='daily'?'selected':'' ?>>Journalier</option><option value="monthly" <?= $f['period']==='monthly'?'selected':'' ?>>Mensuel</option><option value="yearly" <?= $f['period']==='yearly'?'selected':'' ?>>Annuel</option></select></div>
    <div class="col-12 col-md-4 period-field" data-period="daily"><label class="form-label fw-semibold">Date</label><input type="date" class="form-control" name="date" value="<?= e($f['date']) ?>"></div>
    <div class="col-12 col-md-4 period-field" data-period="monthly"><label class="form-label fw-semibold">Mois</label><input type="month" class="form-control" name="month" value="<?= e($f['month']) ?>"></div>
    <div class="col-12 col-md-4 period-field" data-period="yearly"><label class="form-label fw-semibold">Année</label><select class="form-select" name="year"><?php for($y=(int)date('Y')+1;$y>=2020;$y--): ?><option value="<?= $y ?>" <?= (int)$f['year']===$y?'selected':'' ?>><?= $y ?></option><?php endfor; ?></select></div>
    <div class="col-12 col-md-4"><label class="form-label fw-semibold">Entité</label><select class="form-select" name="entity_id"><option value="0">Toutes les entités</option><?php foreach($f['entities'] as $ent): ?><option value="<?= (int)$ent['id'] ?>" <?= $f['entityId']===(int)$ent['id']?'selected':'' ?>><?= e($ent['nom']) ?></option><?php endforeach; ?></select></div>
    <div class="col-12 col-md-4"><label class="form-label fw-semibold">Employé ou matricule</label><input class="form-control" name="employee" value="<?= e($f['employee']) ?>" placeholder="Ex. AK001 ou nom complet"></div>
    <div class="col-12 col-md-4"><label class="form-label fw-semibold">État du pointage</label><select class="form-select" name="status"><option value="all" <?= $f['status']==='all'?'selected':'' ?>>Tous les états</option><option value="complete" <?= $f['status']==='complete'?'selected':'' ?>>Pointage complet</option><option value="arrival_missing" <?= $f['status']==='arrival_missing'?'selected':'' ?>>Arrivée manquante</option><option value="departure_missing" <?= $f['status']==='departure_missing'?'selected':'' ?>>Sortie manquante</option><option value="no_punch" <?= $f['status']==='no_punch'?'selected':'' ?>>Aucun pointage / à vérifier</option></select></div>
    <div class="col-12 d-flex flex-wrap gap-2"><button class="btn btn-primary px-4" type="submit">Appliquer les filtres</button><a class="btn btn-light border" href="reports.php">Réinitialiser</a></div>
  </div>
</form>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3"><div><h2 class="h5 section-title mb-1"><?= e($f['label']) ?></h2><p class="small-muted mb-0"><?= e($entityName) ?> · du <?= e(date('d/m/Y',strtotime($f['start']))) ?> au <?= e(date('d/m/Y',strtotime($f['end']))) ?></p></div><span class="badge text-bg-light border"><?= count($data['rows']) ?> ligne(s)</span></div>
<div class="row g-3 mb-4">
<?php foreach ([['Effectif actif',$s['effectif']],['Pointages complets',$s['complets']],['Arrivées enregistrées',$s['arrivees']],['Sorties enregistrées',$s['sorties']],['Arrivées manquantes',$s['arrivees_manquantes']],['Sorties manquantes',$s['sorties_manquantes']],['À vérifier',$s['a_verifier']]] as $item): ?>
<div class="col-6 col-md-4 col-xl"><div class="stat-card h-100"><div class="stat-label"><?= e($item[0]) ?></div><div class="stat-value"><?= (int)$item[1] ?></div></div></div>
<?php endforeach; ?>
</div>
<section class="panel"><div class="mb-3"><h2 class="h5 section-title mb-1">Détail du rapport</h2><p class="small-muted mb-0"><?php if($f['period']==='daily'): ?>Le rapport journalier affiche tous les employés actifs, y compris ceux sans pointage.<?php else: ?>Les rapports mensuels et annuels listent les journées ayant au moins un enregistrement. Les journées sans pointage ne sont pas automatiquement considérées comme des absences.<?php endif; ?></p></div>
<div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Date</th><th>Matricule</th><th>Employé</th><th>Entité</th><th>Arrivée</th><th>Sortie</th><th>État</th></tr></thead><tbody>
<?php foreach($data['rows'] as $r): ?><tr><td><?= e(date('d/m/Y',strtotime($r['date_affichee']))) ?></td><td class="fw-semibold"><?= e($r['matricule']) ?></td><td><?= e($r['employe']) ?></td><td><?= e($r['entite']) ?></td><td><?= $r['heure_arrivee']?e(substr($r['heure_arrivee'],0,5)):'—' ?></td><td><?= $r['heure_sortie']?e(substr($r['heure_sortie'],0,5)):'—' ?></td><td><span class="badge <?= $r['etat']==='Pointage complet'?'text-bg-success':($r['etat']==='Aucun pointage — à vérifier'?'text-bg-secondary':'text-bg-warning') ?>"><?= e($r['etat']) ?></span></td></tr><?php endforeach; ?>
<?php if(!$data['rows']): ?><tr><td colspan="7" class="empty-state">Aucun résultat pour ces filtres.</td></tr><?php endif; ?>
</tbody></table></div></section>
<script>
(function(){const period=document.getElementById('period');const status=document.querySelector('select[name="status"]');function refresh(){document.querySelectorAll('.period-field').forEach(el=>{const active=el.dataset.period===period.value;el.hidden=!active;const input=el.querySelector('input,select');input.disabled=!active;});const noPunch=status.querySelector('option[value="no_punch"]');noPunch.disabled=period.value!=='daily';if(period.value!=='daily'&&status.value==='no_punch')status.value='all';}period.addEventListener('change',refresh);refresh();})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
