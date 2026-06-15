<?php
require_once '../includes/config.php';
zahtevajPrijavo();
$user = trenutniUporabnik();

$search  = trim($_GET['q']       ?? '');
$namen   = $_GET['namen']        ?? '';
$datumOd = $_GET['datum_od']     ?? '';
$datumDo = $_GET['datum_do']     ?? '';

$where = ['uporabnik_id = ?']; $params = [$user['id']];
if ($search)  { $where[] = '(naziv_narocnika LIKE ? OR naslov_narocnika LIKE ?)'; $params[]= "%$search%"; $params[]= "%$search%"; }
if ($namen && array_key_exists($namen, NAMEN_LABELS)) { $where[] = 'namen_cenitve = ?'; $params[] = $namen; }
if ($datumOd) { $where[] = 'prvi_ogled >= ?'; $params[] = $datumOd.' 00:00:00'; }
if ($datumDo) { $where[] = 'prvi_ogled <= ?'; $params[] = $datumDo.' 23:59:59'; }

$stmt = db()->prepare('SELECT * FROM cenitve WHERE '.implode(' AND ',$where).' ORDER BY ustvarjeno DESC');
$stmt->execute($params);
$cenitve = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="cenitve-'.date('Y-m-d').'.csv"');
$out = fopen('php://output','w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM za Excel
fputcsv($out, ['ID','Naziv naročnika','Naslov naročnika','Namen cenitve','Podlaga vrednosti','Premisa vrednosti','Prvi ogled','Ustvarjeno'], ';');
foreach ($cenitve as $c) {
    fputcsv($out, [
        $c['id'], $c['naziv_narocnika'], $c['naslov_narocnika'],
        NAMEN_LABELS[$c['namen_cenitve']]       ?? $c['namen_cenitve'],
        PODLAGA_LABELS[$c['podlaga_vrednosti']] ?? $c['podlaga_vrednosti'],
        PREMISA_LABELS[$c['premisa_vrednosti']] ?? $c['premisa_vrednosti'],
        date('d. m. Y H:i', strtotime($c['prvi_ogled'])),
        date('d. m. Y H:i', strtotime($c['ustvarjeno'])),
    ], ';');
}
fclose($out);