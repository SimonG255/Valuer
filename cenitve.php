<?php
// cenitve.php – upravljanje cenitev

require_once 'includes/config.php';
zahtevajPrijavo();
$user = trenutniUporabnik();

$napaka = '';
$uspeh  = '';

// ── Dodaj cenitev ──
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
            $stmt = db()->prepare(
                'INSERT INTO cenitve
                 (uporabnik_id, naziv_narocnika, naslov_narocnika, namen_cenitve,
                  podlaga_vrednosti, premisa_vrednosti, prvi_ogled)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $user['id'],
                $data['naziv_narocnika'],
                $data['naslov_narocnika'],
                $data['namen_cenitve'],
                $data['podlaga_vrednosti'],
                $data['premisa_vrednosti'],
                str_replace('T', ' ', $data['prvi_ogled']),
            ]);
            $uspeh = 'Cenitev je bila uspešno dodana.';
        } catch (PDOException $e) {
            $napaka = 'Napaka: ' . $e->getMessage();
        }
    }
}

// ── Uredi cenitev ──
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
            $stmt = db()->prepare(
                'UPDATE cenitve
                 SET naziv_narocnika=?, naslov_narocnika=?, namen_cenitve=?,
                     podlaga_vrednosti=?, premisa_vrednosti=?, prvi_ogled=?
                 WHERE id=? AND uporabnik_id=?'
            );
            $stmt->execute([
                $data['naziv_narocnika'],
                $data['naslov_narocnika'],
                $data['namen_cenitve'],
                $data['podlaga_vrednosti'],
                $data['premisa_vrednosti'],
                str_replace('T', ' ', $data['prvi_ogled']),
                $id,
                $user['id'],
            ]);
            $uspeh = 'Cenitev je bila uspešno posodobljena.';
        } catch (PDOException $e) {
            $napaka = 'Napaka uredi: ' . $e->getMessage();
        }
    }
}

// ── Pridobi cenitve ──
try {
    $stmt = db()->prepare(
        'SELECT * FROM cenitve WHERE uporabnik_id = ? ORDER BY ustvarjeno DESC'
    );
    $stmt->execute([$user['id']]);
    $cenitve = $stmt->fetchAll();
} catch (PDOException $e) {
    $cenitve = [];
    $napaka  = 'Napaka baze: ' . $e->getMessage();
}

// ── Pomožne funkcije ──
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
        'zavarovano_posojanje'  => ['badge-blue',   'Zavar. posojanje'],
        'sodni_postopek'        => ['badge-purple',  'Sodni postopek'],
        'stecajni_postopek'     => ['badge-orange',  'Stečajni postopek'],
        'racunovodsko_porocanje'=> ['badge-gray',    'Računov. poročanje'],
        'davcni_postopek'       => ['badge-green',   'Davčni postopek'],
        'poslovna_odlocitev'    => ['badge-blue',    'Posl. odločitev'],
    ];
    [$cls, $lbl] = $map[$k] ?? ['badge-gray', $k];
    return "<span class=\"badge {$cls}\">{$lbl}</span>";
}

$pageTitle = 'Moje cenitve – ' . APP_NAME;
require 'includes/header.php';
?>

<div class="container">

    <?php if ($napaka): ?>
        <div class="alert alert-danger"><?= e($napaka) ?></div>
    <?php endif; ?>
    <?php if ($uspeh): ?>
        <div class="alert alert-success"><?= e($uspeh) ?></div>
    <?php endif; ?>

    <!-- Glava strani -->
    <div class="card mb-4">
        <div class="card-header">
            <div>
                <h2 style="margin-bottom:.2rem">Moje cenitve</h2>
                <p class="text-muted mb-0">
                    Skupaj: <strong id="stevec-cenitev"><?= count($cenitve) ?></strong>
                    <?= count($cenitve) === 1 ? 'cenitev' : 'cenitve/cenitev' ?>
                </p>
            </div>
            <button class="btn btn-gold" data-modal-open="modal-dodaj">
                + Dodaj cenitev
            </button>
        </div>
    </div>

    <!-- Tabela cenitev -->
    <div class="card">
        <div class="table-wrap">
            <table id="tabela-cenitev">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Naročnik</th>
                        <th>Namen</th>
                        <th>Podlaga vrednosti</th>
                        <th>Premisa vrednosti</th>
                        <th>Prvi ogled</th>
                        <th>Dejanja</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($cenitve)): ?>
                    <tr><td colspan="7">
                        <div class="empty-state">
                            <div class="empty-icon">📋</div>
                            <h3>Ni še nobene cenitve</h3>
                            <p>Dodajte svojo prvo cenitev z gumbom zgoraj.</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($cenitve as $i => $c): ?>
                    <tr id="vrstica-<?= e($c['id']) ?>">
                        <td class="text-muted"><?= $i + 1 ?></td>
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
                                <button class="btn btn-ghost btn-sm"
                                    onclick="urediCenitev(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)">
                                    ✎ Uredi
                                </button>
                                <button class="btn btn-danger btn-sm"
                                    onclick="brisiCenitev(<?= e($c['id']) ?>, document.getElementById('vrstica-<?= e($c['id']) ?>'))">
                                    ✕ Briši
                                </button>
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

<!-- ── Modal: Dodaj cenitev ── -->
<div id="modal-dodaj" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3>Nova cenitev</h3>
            <button class="modal-close" data-modal-close="modal-dodaj" aria-label="Zapri">&times;</button>
        </div>
        <form method="post" action="cenitve.php" data-validate>
            <input type="hidden" name="akcija" value="dodaj">
            <div class="modal-body">
                <?= formPoljaTemplate() ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close="modal-dodaj">Prekliči</button>
                <button type="submit" class="btn btn-gold">Shrani cenitev</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Modal: Uredi cenitev ── -->
<div id="modal-uredi" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3>Uredi cenitev</h3>
            <button class="modal-close" data-modal-close="modal-uredi" aria-label="Zapri">&times;</button>
        </div>
        <form id="form-uredi" method="post" action="cenitve.php" data-validate>
            <input type="hidden" name="akcija" value="uredi">
            <input type="hidden" name="id" value="">
            <div class="modal-body">
                <?= formPoljaTemplate() ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close="modal-uredi">Prekliči</button>
                <button type="submit" class="btn btn-primary">Posodobi cenitev</button>
            </div>
        </form>
    </div>
</div>

<?php
// Pomožna funkcija: HTML polja obrazca (skupna za dodaj/uredi)
function formPoljaTemplate(): string {
    $nameni   = NAMEN_LABELS;
    $podlage  = PODLAGA_LABELS;
    $premise  = PREMISA_LABELS;

    $selectNamen = '';
    foreach ($nameni as $k => $v) {
        $selectNamen .= "<option value=\"" . e($k) . "\">" . e($v) . "</option>";
    }
    $selectPodlaga = '';
    foreach ($podlage as $k => $v) {
        $selectPodlaga .= "<option value=\"" . e($k) . "\">" . e($v) . "</option>";
    }
    $selectPremisa = '';
    foreach ($premise as $k => $v) {
        $selectPremisa .= "<option value=\"" . e($k) . "\">" . e($v) . "</option>";
    }

    return <<<HTML
    <div class="form-group">
        <label class="form-label" for="naziv_narocnika">Naziv naročnika *</label>
        <input type="text" id="naziv_narocnika" name="naziv_narocnika"
               class="form-control" required placeholder="npr. Janez Novak d.o.o.">
        <span class="form-error"></span>
    </div>
    <div class="form-group">
        <label class="form-label" for="naslov_narocnika">Naslov naročnika *</label>
        <input type="text" id="naslov_narocnika" name="naslov_narocnika"
               class="form-control" required placeholder="npr. Slovenska cesta 1, 1000 Ljubljana">
        <span class="form-error"></span>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label" for="namen_cenitve">Namen cenitve *</label>
            <select id="namen_cenitve" name="namen_cenitve" class="form-control" required>
                <option value="">— Izberite namen —</option>
                $selectNamen
            </select>
            <span class="form-error"></span>
        </div>
        <div class="form-group">
            <label class="form-label" for="podlaga_vrednosti">Podlaga vrednosti *</label>
            <select id="podlaga_vrednosti" name="podlaga_vrednosti" class="form-control" required>
                <option value="">— Izberite podlago —</option>
                $selectPodlaga
            </select>
            <span class="form-error"></span>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label class="form-label" for="premisa_vrednosti">Premisa vrednosti *</label>
            <select id="premisa_vrednosti" name="premisa_vrednosti" class="form-control" required>
                <option value="">— Izberite premiso —</option>
                $selectPremisa
            </select>
            <span class="form-error"></span>
        </div>
        <div class="form-group">
            <label class="form-label" for="prvi_ogled">Datum in ura prvega ogleda *</label>
            <input type="datetime-local" id="prvi_ogled" name="prvi_ogled"
                   class="form-control" required>
            <span class="form-error"></span>
        </div>
    </div>
    HTML;
}

require 'includes/footer.php';
?>
