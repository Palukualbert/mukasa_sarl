<?php
require_once __DIR__ . '/includes/bootstrap.php'; require_admin();
$pageTitle='Gestion des employés';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf(); $id=(int)($_POST['id']??0);$mat=strtoupper(trim($_POST['matricule']??''));$nom=trim($_POST['nom']??'');$prenom=trim($_POST['prenom']??'');$entity=(int)($_POST['entity_id']??0);$status=$_POST['statut']??'actif';
 if(!$mat||!$nom||!$entity||!in_array($status,['actif','inactif'],true)){flash('danger','Veuillez remplir les champs obligatoires.');}
 else {try {if($id){$s=$pdo->prepare('UPDATE employees SET matricule=?,nom=?,prenom=?,entity_id=?,statut=? WHERE id=?');$s->execute([$mat,$nom,$prenom,$entity,$status,$id]);flash('success','Employé mis à jour.');}else{$s=$pdo->prepare('INSERT INTO employees(matricule,nom,prenom,entity_id,statut) VALUES(?,?,?,?,?)');$s->execute([$mat,$nom,$prenom,$entity,$status]);flash('success','Employé ajouté.');}}catch(PDOException $e){flash('danger','Matricule déjà utilisé ou erreur de base de données.');}}
 header('Location: employees.php');exit;
}
$entities=$pdo->query('SELECT id,nom FROM entities WHERE actif=1 ORDER BY id')->fetchAll();
$rows=$pdo->query('SELECT e.*,ent.nom entite FROM employees e JOIN entities ent ON ent.id=e.entity_id ORDER BY ent.id,e.nom,e.prenom')->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="mb-4"><div class="eyebrow">Ressources humaines</div><h1 class="h2 section-title">Gestion des employés</h1><p class="small-muted">Ajoutez les employés réels et affectez chacun à une entité.</p></div>
<div class="row g-4"><div class="col-lg-4"><section class="panel"><h2 class="h5 section-title mb-3">Ajouter un employé</h2><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="0">
<label class="form-label">Matricule *</label><input class="form-control mb-3 text-uppercase" name="matricule" placeholder="Ex. AK001" required>
<label class="form-label">Nom *</label><input class="form-control mb-3" name="nom" required>
<label class="form-label">Prénom</label><input class="form-control mb-3" name="prenom">
<label class="form-label">Entité *</label><select class="form-select mb-3" name="entity_id" required><?php foreach($entities as $ent): ?><option value="<?= $ent['id'] ?>"><?= e($ent['nom']) ?></option><?php endforeach; ?></select>
<label class="form-label">Statut</label><select class="form-select mb-4" name="statut"><option value="actif">Actif</option><option value="inactif">Inactif</option></select>
<button class="btn btn-primary w-100">Ajouter l’employé</button></form></section></div>
<div class="col-lg-8"><section class="panel"><h2 class="h5 section-title mb-3">Liste du personnel <span class="badge pill-site"><?= count($rows) ?></span></h2><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Matricule</th><th>Nom</th><th>Entité</th><th>Statut</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td class="fw-semibold"><?= e($r['matricule']) ?></td><td><?= e(trim($r['prenom'].' '.$r['nom'])) ?></td><td><?= e($r['entite']) ?></td><td><span class="badge <?= $r['statut']==='actif'?'text-bg-success':'text-bg-secondary' ?>"><?= e($r['statut']) ?></span></td></tr><?php endforeach; ?></tbody></table></div><p class="small-muted mb-0">Les matricules doivent être uniques dans toute l’entreprise.</p></section></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
