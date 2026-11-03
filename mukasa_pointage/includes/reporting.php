<?php
declare(strict_types=1);

function rp_filters(PDO $pdo): array {
    $period = (string)($_GET['period'] ?? 'daily');
    if (!in_array($period, ['daily','monthly','yearly'], true)) $period = 'daily';

    $today = date('Y-m-d');
    $date = (string)($_GET['date'] ?? $today);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ||
        !checkdate((int)substr($date,5,2), (int)substr($date,8,2), (int)substr($date,0,4))) $date = $today;

    $month = (string)($_GET['month'] ?? date('Y-m'));
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) $month = date('Y-m');

    $year = (string)($_GET['year'] ?? date('Y'));
    if (!preg_match('/^\d{4}$/', $year) || (int)$year < 2000 || (int)$year > 2100) $year = date('Y');

    if ($period === 'daily') {
        $start = $date; $end = $date; $label = 'Journalier — ' . date('d/m/Y', strtotime($date));
    } elseif ($period === 'monthly') {
        $start = $month . '-01'; $end = date('Y-m-t', strtotime($start)); $label = 'Mensuel — ' . date('m/Y', strtotime($start));
    } else {
        $start = $year . '-01-01'; $end = $year . '-12-31'; $label = 'Annuel — ' . $year;
    }

    $entityId = max(0, (int)($_GET['entity_id'] ?? 0));
    $employee = trim((string)($_GET['employee'] ?? ''));
    if (function_exists('mb_substr')) $employee = mb_substr($employee, 0, 80); else $employee = substr($employee, 0, 80);
    $status = (string)($_GET['status'] ?? 'all');
    if (!in_array($status, ['all','complete','arrival_missing','departure_missing','no_punch'], true)) $status = 'all';
    if ($period !== 'daily' && $status === 'no_punch') $status = 'all';

    $entities = $pdo->query('SELECT id,nom FROM entities WHERE actif=1 ORDER BY id')->fetchAll();
    return compact('period','date','month','year','start','end','label','entityId','employee','status','entities');
}

function rp_status(array $r): string {
    if (empty($r['attendance_id'])) return 'Aucun pointage — à vérifier';
    if (empty($r['heure_arrivee'])) return 'Arrivée manquante';
    if (empty($r['heure_sortie'])) return 'Sortie manquante';
    return 'Pointage complet';
}

function rp_get_data(PDO $pdo, array $f): array {
    $whereEmployee = '';
    $params = [];
    if ($f['entityId']) { $whereEmployee .= ' AND e.entity_id=?'; $params[] = $f['entityId']; }
    if ($f['employee'] !== '') {
        $whereEmployee .= " AND (e.matricule LIKE ? OR e.nom LIKE ? OR e.prenom LIKE ?)";
        $like = '%' . $f['employee'] . '%';
        array_push($params, $like, $like, $like);
    }

    $employeesQuery = $pdo->prepare("SELECT e.id,e.matricule,e.nom,e.prenom,ent.nom AS entite
        FROM employees e JOIN entities ent ON ent.id=e.entity_id
        WHERE e.statut='actif' $whereEmployee ORDER BY ent.id,e.nom,e.prenom");
    $employeesQuery->execute($params);
    $employees = $employeesQuery->fetchAll();

    if ($f['period'] === 'daily') {
        // Daily report includes every active employee, including those without a punch.
        $sql = "SELECT e.id AS employee_id,e.matricule,e.nom,e.prenom,ent.nom AS entite,
                    a.id AS attendance_id,a.work_date,a.heure_arrivee,a.heure_sortie
                FROM employees e JOIN entities ent ON ent.id=e.entity_id
                LEFT JOIN attendance a ON a.employee_id=e.id AND a.work_date=?
                WHERE e.statut='actif' $whereEmployee
                ORDER BY ent.id,e.nom,e.prenom";
        $q = $pdo->prepare($sql);
        $q->execute(array_merge([$f['date']], $params));
    } else {
        // Monthly/yearly report lists recorded workdays only; no punch is not automatically an absence.
        $sql = "SELECT e.id AS employee_id,e.matricule,e.nom,e.prenom,ent.nom AS entite,
                    a.id AS attendance_id,a.work_date,a.heure_arrivee,a.heure_sortie
                FROM attendance a JOIN employees e ON e.id=a.employee_id
                JOIN entities ent ON ent.id=e.entity_id
                WHERE a.work_date BETWEEN ? AND ? AND e.statut='actif' $whereEmployee
                ORDER BY a.work_date,ent.id,e.nom,e.prenom";
        $q = $pdo->prepare($sql);
        $q->execute(array_merge([$f['start'],$f['end']], $params));
    }

    $rows = $q->fetchAll();
    foreach ($rows as &$r) {
        $r['etat'] = rp_status($r);
        $r['employe'] = trim(($r['prenom'] ?? '') . ' ' . ($r['nom'] ?? ''));
        $r['date_affichee'] = $r['work_date'] ?: $f['date'];
    }
    unset($r);

    if ($f['status'] !== 'all') {
        $map = [
            'complete'=>'Pointage complet',
            'arrival_missing'=>'Arrivée manquante',
            'departure_missing'=>'Sortie manquante',
            'no_punch'=>'Aucun pointage — à vérifier'
        ];
        $wanted = $map[$f['status']];
        $rows = array_values(array_filter($rows, fn($r) => $r['etat'] === $wanted));
    }

    $summary = [
        'effectif'=>count($employees),
        'lignes'=>count($rows),
        'complets'=>0,
        'arrivees'=>0,
        'sorties'=>0,
        'arrivees_manquantes'=>0,
        'sorties_manquantes'=>0,
        'a_verifier'=>0
    ];
    foreach ($rows as $r) {
        if (!empty($r['heure_arrivee'])) $summary['arrivees']++;
        if (!empty($r['heure_sortie'])) $summary['sorties']++;
        if ($r['etat'] === 'Pointage complet') $summary['complets']++;
        elseif ($r['etat'] === 'Arrivée manquante') $summary['arrivees_manquantes']++;
        elseif ($r['etat'] === 'Sortie manquante') $summary['sorties_manquantes']++;
        else $summary['a_verifier']++;
    }
    return ['employees'=>$employees,'rows'=>$rows,'summary'=>$summary];
}
