<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();
require_once __DIR__ . '/includes/reporting.php';

// Le même filtre journalier, mensuel ou annuel est utilisé à l'écran et dans le CSV.
$f = rp_filters($pdo);
$data = rp_get_data($pdo, $f);

$baseName = 'rapport-presences-' . $f['period'];
if ($f['period'] === 'daily') {
    $baseName .= '-' . str_replace('-', '', $f['date']);
} elseif ($f['period'] === 'monthly') {
    $baseName .= '-' . str_replace('-', '', $f['month']);
} else {
    $baseName .= '-' . $f['year'];
}

// UTF-8 BOM pour une ouverture correcte dans Microsoft Excel.
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $baseName . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
if ($out === false) {
    http_response_code(500);
    exit('Impossible de générer le fichier CSV.');
}

// Séparateur point-virgule : adapté aux paramètres régionaux d'Excel en français.
fputcsv($out, [
    'Date',
    'Matricule',
    'Employé',
    'Entité',
    'Heure d’arrivée',
    'Heure de sortie',
    'État du pointage'
], ';', '"', '\\');

foreach ($data['rows'] as $r) {
    fputcsv($out, [
        date('d/m/Y', strtotime($r['date_affichee'])),
        $r['matricule'],
        $r['employe'],
        $r['entite'],
        $r['heure_arrivee'] ? substr($r['heure_arrivee'], 0, 5) : '',
        $r['heure_sortie'] ? substr($r['heure_sortie'], 0, 5) : '',
        $r['etat']
    ], ';', '"', '\\');
}

fclose($out);
exit;
