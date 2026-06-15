<?php
require_once 'includes/config.php';
zahtevajPrijavo();
$user = trenutniUporabnik();

$napaka = '';
$uspeh  = '';

// ── Dodaj ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['akcija'] ?? '') === 'dodaj') {
    $data = [
        'naziv_narocnika'  => trim($_POST['naziv_narocnika']  ?? ''),
        'naslov_narocnika' => trim($_POST['naslov_narocnika'] ?? ''),
        'namen_cenitve'    => $_POST['namen_cenitve']         ?? '',
        'podlaga_vrednosti'=> $_POST['podlaga_vrednosti']     ?? '',
        'premisa_vrednosti'=> $_POST['premisa_vrednosti']     ?? '',
        'prvi_ogled'       => $_POST['prvi_ogled']            ?? '',
    ];
    $napake = validirajCenitev($data);
    if ($napake) {
        $napaka = implode(' ', $napake);
    } else {
        try {
            db()->prepare('INSERT INTO cenitve (uporabnik_id,naziv_narocnika,naslov_narocnika,namen_cenitve,podlaga_vrednosti,premisa_vrednosti,prvi_ogled) VALUES (?,?,?,?,?,?,?)')
               ->execute([$user['id'],$data['naziv_narocnika'],$data['naslov_narocnika'],$data['namen_cenitve'],$data['podlaga_vrednosti'],$data['premisa_vrednosti'],str_replace('T',' ',$data['prvi_ogled'])]);
            $uspeh = 'Cenitev je bila uspešno dodana.';
        } catch (PDOException $e) { $napaka = 'Napaka: ' . $e->getMessage(); }
    }
}

// ── Uredi ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['akcija'] ?? '') === 'uredi') {
    $id   = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $data = [
        'naziv_narocnika'  => trim($_POST['naziv_narocnika']  ?? ''),
        'naslov_narocnika' => trim($_POST['naslov_narocnika'] ?? ''),
        'namen_cenitve'    => $_POST['namen_cenitve']         ?? '',
        'podlaga_vrednosti'=> $_POST['podlaga_vrednosti']     ?? '',
        'premisa_vrednosti'=> $_POST['premisa_vrednosti']     ?? '',
        'prvi_ogled'       => $_POST['prvi_ogled']            ?? '',
    ];
    $napake = validirajCenitev($data);
    if (!$id) $napake[] = 'Neveljaven ID.';
    if ($napake) {
        $napaka = implode(' ', $napake);
    } else {
        try {
            db()->prepare('UPDATE cenitve SET naziv_narocnika=?,naslov_narocnika=?,namen_cenitve=?,podlaga_vrednosti=?,premisa_vrednosti=?,prvi_ogled=? WHERE id=? AND uporabnik_id=?')
               ->execute([$data['naziv_narocnika'],$data['naslov_narocnika'],$data['namen_cenitve'],$data['podlaga_vrednosti'],$data['premisa_vrednosti'],str_replace('T',' ',$data['prvi_ogled']),$id,$user['id']]);
            $uspeh = 'Cenitev je bila uspešno posodobljena.';
        } catch (PDOException $e) { $napaka = 'Napaka: ' . $e->getMessage(); }
    }
}

// ── Filtri ──
$search      = trim($_GET['q']       ?? '');
$filterNamen = $_GET['namen']        ?? '';
$datumOd     = $_GET['datum_od']     ?? '';
$datumDo     = $_GET['datum_do']     ?? '';

$where  = ['uporabnik_id = ?'];
$params = [$user['id']];
if ($search) { $where[] = '(naziv_narocnika LIKE ? OR naslov_narocnika LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($filterNamen && array_key_exists($filterNamen, NAMEN_LABELS)) { $where[] = 'namen_cenitve = ?'; $params[] = $filterNamen; }
if ($datumOd) { $where[] = 'prvi_ogled >= ?'; $params[] = $datumOd . ' 00:00:00'; }
if ($datumDo) { $where[] = 'prvi_ogled <= ?'; $params[] = $datumDo . ' 23:59:59'; }

try {
    $stmt = db()->prepare('SELECT * FROM cenitve WHERE ' . implode(' AND ', $where) . ' ORDER BY ustvarjeno DESC');
    $stmt->execute($params);
    $cenitve = $stmt->fetchAll();
    $stmtVse = db()->prepare('SELECT COUNT(*) FROM cenitve WHERE uporabnik_id = ?');
    $stmtVse->execute([$user['id']]);
    $skupajVse = (int)$stmtVse->fetchColumn();
} catch (PDOException $e) {
    $cenitve = []; $skupajVse = 0; $napaka = 'Napaka baze: ' . $e->getMessage();
}

function validirajCenitev(array $d): array {
    $err = [];
    if (!$d['naziv_narocnika'])   $err[] = 'Naziv naročnika je obvezen.';
    if (!$d['naslov_narocnika'])  $err[] = 'Naslov naročnika je obvezen.';
    if (!$d['namen_cenitve'])     $err[] = 'Namen cenitve je obvezen.';
    if (!$d['podlaga_vrednosti']) $err[] = 'Podlaga vrednosti je obvezna.';
    if (!$d['premisa_vrednosti']) $err[] = 'Premisa vrednosti je obvezna.';
    if (!$d['prvi_ogled'])        $err[] = 'Datum prvega ogleda je obvezen.';
    return $err;
}

function namenbadge(string $k): string {
    $map = [
        'zavarovano_posojanje'   => ['badge-blue',   'Zavar. posojanje'],
        'sodni_postopek'         => ['badge-purple',  'Sodni postopek'],
        'stecajni_postopek'      => ['badge-orange',  'Stečajni postopek'],
        'racunovodsko_porocanje' => ['badge-gray',    'Računov. poročanje'],
        'davcni_postopek'        => ['badge-green',   'Davčni postopek'],
        'poslovna_odlocitev'     => ['badge-blue',    'Posl. odločitev'],
    ];
    [$cls, $lbl] = $map[$k] ?? ['badge-gray', $k];
    return "<span class=\"badge {$cls}\">{$lbl}</span>";
}

$exportParams = http_build_query(array_filter(['q'=>$search,'namen'=>$filterNamen,'datum_od'=>$datumOd,'datum_do'=>$datumDo]));
$pageTitle = 'Moje cenitve – ' . APP_NAME;
require 'includes/header.php';
?>

<div class="container">
    <?php if ($napaka): ?><div class="alert alert-danger"><?= e($napaka) ?></div><?php endif; ?>
    <?php if ($uspeh):  ?><div class="alert alert-success"><?= e($uspeh) ?></div><?php endif; ?>

    <div class="card mb-3">
        <div class="card-header">
            <div>
                <h2 style="margin-bottom:.2rem">Moje cenitve</h2>
                <p class="text-muted mb-0">
                    Prikazano: <strong><?= count($cenitve) ?></strong> od
                    <strong id="stevec-cenitev"><?= $skupajVse ?></strong> cenitev
                </p>
            </div>
            <div style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:center">
                <a href="api/export.php?<?= e($exportParams) ?>" class="btn btn-ghost btn-sm">📊 CSV</a>
                <button class="btn btn-ghost btn-sm" onclick="toggleFiltre()">
                    🔍 Filtri <?= ($search||$filterNamen||$datumOd||$datumDo) ? '<span class="filter-dot"></span>' : '' ?>
                </button>
                <button class="btn btn-gold" data-modal-open="modal-dodaj">+ Dodaj cenitev</button>
            </div>
        </div>

        <div id="filter-panel" class="filter-panel <?= ($search||$filterNamen||$datumOd||$datumDo) ? 'active' : '' ?>">
            <form method="get" action="cenitve.php">
                <div class="filter-row">
                    <div class="filter-field">
                        <label class="form-label">Iskanje</label>
                        <input type="text" name="q" id="live-search" class="form-control"
                               placeholder="Naziv ali naslov..." value="<?= e($search) ?>">
                    </div>
                    <div class="filter-field">
                        <label class="form-label">Namen</label>
                        <select name="namen" class="form-control">
                            <option value="">— Vsi —</option>
                            <?php foreach (NAMEN_LABELS as $k => $v): ?>
                            <option value="<?= e($k) ?>" <?= $filterNamen===$k?'selected':'' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="form-label">Od datuma</label>
                        <input type="date" name="datum_od" class="form-control" value="<?= e($datumOd) ?>">
                    </div>
                    <div class="filter-field">
                        <label class="form-label">Do datuma</label>
                        <input type="date" name="datum_do" class="form-control" value="<?= e($datumDo) ?>">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary btn-sm">Išči</button>
                        <a href="cenitve.php" class="btn btn-ghost btn-sm">Ponastavi</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table id="tabela-cenitev">
                <thead>
                    <tr>
                        <th>#</th><th>Naročnik</th><th>Namen</th>
                        <th>Podlaga vrednosti</th><th>Premisa vrednosti</th>
                        <th>Prvi ogled</th><th>Dejanja</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($cenitve)): ?>
                    <tr><td colspan="7"><div class="empty-state">
                        <div class="empty-icon"><?= ($search||$filterNamen||$datumOd||$datumDo)?'🔍':'📋' ?></div>
                        <h3><?= ($search||$filterNamen)?'Ni rezultatov':'Ni še nobene cenitve' ?></h3>
                        <p><?= ($search||$filterNamen)?'Poskusite z drugimi filtri.':'Dodajte svojo prvo cenitev.' ?></p>
                    </div></td></tr>
                <?php else: ?>
                    <?php foreach ($cenitve as $i => $c): ?>
                    <tr id="vrstica-<?= e($c['id']) ?>" class="cenitev-vrstica">
                        <td class="text-muted"><?= $i+1 ?></td>
                        <td>
                            <strong><?= e($c['naziv_narocnika']) ?></strong><br>
                            <small class="text-muted"><?= e($c['naslov_narocnika']) ?></small>
                        </td>
                        <td><?= namenbadge($c['namen_cenitve']) ?></td>
                        <td><?= e(PODLAGA_LABELS[$c['podlaga_vrednosti']] ?? $c['podlaga_vrednosti']) ?></td>
                        <td><?= e(PREMISA_LABELS[$c['premisa_vrednosti']] ?? $c['premisa_vrednosti']) ?></td>
                        <td><?= e(date('d. m. Y H:i', strtotime($c['prvi_ogled']))) ?></td>
                        <td>
                            <div class="td-actions">
                                <a href="api/pdf.php?id=<?= e($c['id']) ?>" class="btn btn-ghost btn-sm" target="_blank">📄 PDF</a>
                                <button class="btn btn-ghost btn-sm"
                                    onclick="urediCenitev(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)">✎ Uredi</button>
                                <button class="btn btn-danger btn-sm"
                                    onclick="brisiCenitev(<?= e($c['id']) ?>, document.getElementById('vrstica-<?= e($c['id']) ?>'))">✕ Briši</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Dodaj -->
<div id="modal-dodaj" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3>Nova cenitev</h3>
            <button class="modal-close" data-modal-close="modal-dodaj">&times;</button>
        </div>
        <form method="post" action="cenitve.php" data-validate>
            <input type="hidden" name="akcija" value="dodaj">
            <div class="modal-body"><?= formPoljaTemplate() ?></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close="modal-dodaj">Prekliči</button>
                <button type="submit" class="btn btn-gold">Shrani cenitev</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Uredi -->
<div id="modal-uredi" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3>Uredi cenitev</h3>
            <button class="modal-close" data-modal-close="modal-uredi">&times;</button>
        </div>
        <form id="form-uredi" method="post" action="cenitve.php" data-validate>
            <input type="hidden" name="akcija" value="uredi">
            <input type="hidden" name="id" value="">
            <div class="modal-body"><?= formPoljaTemplate() ?></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close="modal-uredi">Prekliči</button>
                <button type="submit" class="btn btn-primary">Posodobi</button>
            </div>
        </form>
    </div>
</div>

<?php
function formPoljaTemplate(): string {
    $sN = $sP = $sPr = '';
    foreach (NAMEN_LABELS   as $k=>$v) $sN  .= "<option value=\"".e($k)."\">".e($v)."</option>";
    foreach (PODLAGA_LABELS as $k=>$v) $sP  .= "<option value=\"".e($k)."\">".e($v)."</option>";
    foreach (PREMISA_LABELS as $k=>$v) $sPr .= "<option value=\"".e($k)."\">".e($v)."</option>";
    return <<<HTML
    <div class="form-group">
        <label class="form-label">Naziv naročnika *</label>
        <input type="text" name="naziv_narocnika" class="form-control" required placeholder="npr. Janez Novak d.o.o.">
        <span class="form-error"></span>
    </div>
    <div class="form-group">
        <label class="form-label">Naslov naročnika *</label>
        <input type="text" name="naslov_narocnika" class="form-control" required placeholder="npr. Slovenska cesta 1, 1000 Ljubljana">
        <span class="form-error"></span>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Namen cenitve *</label>
            <select name="namen_cenitve" class="form-control" required>
                <option value="">— Izberite —</option>$sN
            </select>
            <span class="form-error"></span>
        </div>
        <div class="form-group">
            <label class="form-label">Podlaga vrednosti *</label>
            <select name="podlaga_vrednosti" class="form-control" required>
                <option value="">— Izberite —</option>$sP
            </select>
            <span class="form-error"></span>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label">Premisa vrednosti *</label>
            <select name="premisa_vrednosti" class="form-control" required>
                <option value="">— Izberite —</option>$sPr
            </select>
            <span class="form-error"></span>
        </div>
        <div class="form-group">
            <label class="form-label">Datum in ura prvega ogleda *</label>
            <input type="datetime-local" name="prvi_ogled" class="form-control" required>
            <span class="form-error"></span>
        </div>
    </div>
    HTML;
}
require 'includes/footer.php';
?>