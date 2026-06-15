<?php
require_once '../includes/config.php';
zahtevajPrijavo();
$user = trenutniUporabnik();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); die('Neveljaven ID.'); }

$stmt = db()->prepare('SELECT * FROM cenitve WHERE id = ? AND uporabnik_id = ?');
$stmt->execute([$id, $user['id']]);
$c = $stmt->fetch();
if (!$c) { http_response_code(404); die('Cenitev ne obstaja.'); }

$namen   = NAMEN_LABELS[$c['namen_cenitve']]       ?? $c['namen_cenitve'];
$podlaga = PODLAGA_LABELS[$c['podlaga_vrednosti']] ?? $c['podlaga_vrednosti'];
$premisa = PREMISA_LABELS[$c['premisa_vrednosti']] ?? $c['premisa_vrednosti'];
$datum   = date('d. m. Y H:i', strtotime($c['prvi_ogled']));
$danes   = date('d. m. Y');
$cenik   = e($user['ime'].' '.$user['priimek']);
$id_fmt  = 'C-'.str_pad($c['id'], 4, '0', STR_PAD_LEFT);

$html = <<<HTML
<!DOCTYPE html><html lang="sl"><head><meta charset="UTF-8">
<style>
* { margin:0;padding:0;box-sizing:border-box; }
body { font-family:DejaVu Sans,Arial,sans-serif;font-size:11pt;color:#1A2332;padding:2cm 2.5cm; }
.header { border-bottom:3px solid #C9A84C;padding-bottom:1cm;margin-bottom:1cm;display:flex;justify-content:space-between; }
.logo { font-size:18pt;font-weight:bold; }
.meta { text-align:right;font-size:9pt;color:#5A6E84; }
.meta strong { color:#1A2332;font-size:12pt;display:block; }
h1 { font-size:16pt;margin-bottom:.3cm; }
.sub { color:#5A6E84;font-size:10pt;margin-bottom:1cm; }
.sec-title { font-size:8pt;font-weight:bold;text-transform:uppercase;letter-spacing:1px;color:#C9A84C;border-bottom:1px solid #EDF1F5;padding-bottom:3px;margin:0 0 .4cm; }
.section { margin-bottom:.8cm; }
.grid { width:100%;border-collapse:collapse; }
.grid td { width:50%;padding:.2cm .3cm .2cm 0;vertical-align:top; }
.label { font-size:8pt;color:#5A6E84;margin-bottom:2px; }
.value { font-size:11pt; }
.box { background:#F6F3EE;border-left:4px solid #C9A84C;padding:.3cm .5cm;border-radius:3px; }
.sign { border-top:1px solid #1A2332;width:8cm;margin-top:1.5cm;padding-top:4px;font-size:9pt;color:#5A6E84; }
.footer { margin-top:1.5cm;border-top:1px solid #EDF1F5;padding-top:.4cm;font-size:9pt;color:#5A6E84;display:flex;justify-content:space-between; }
</style></head><body>
<div class="header">
    <div><div class="logo">◈ Cenitve Nepremičnin</div><div style="font-size:9pt;color:#5A6E84">Sistem za upravljanje cenitev</div></div>
    <div class="meta"><strong>{$id_fmt}</strong>Datum: {$danes}<br>Ceniteljeval: {$cenik}</div>
</div>
<h1>Naročilo cenitve nepremičnine</h1>
<p class="sub">Ustvarjeno dne {$danes}</p>
<div class="section">
    <div class="sec-title">Podatki o naročniku</div>
    <table class="grid"><tr>
        <td><div class="label">Naziv naročnika</div><div class="value">{$c['naziv_narocnika']}</div></td>
        <td><div class="label">Naslov naročnika</div><div class="value">{$c['naslov_narocnika']}</div></td>
    </tr></table>
</div>
<div class="section">
    <div class="sec-title">Parametri cenitve</div>
    <table class="grid"><tr>
        <td><div class="label">Namen cenitve</div><div class="value box">{$namen}</div></td>
        <td><div class="label">Podlaga vrednosti</div><div class="value box">{$podlaga}</div></td>
    </tr></table>
</div>
<div class="section">
    <div class="sec-title">Premisa in termin</div>
    <table class="grid"><tr>
        <td><div class="label">Premisa vrednosti</div><div class="value">{$premisa}</div></td>
        <td><div class="label">Datum prvega ogleda</div><div class="value"><strong>{$datum}</strong></div></td>
    </tr></table>
</div>
<div class="sign">{$cenik}, pooblaščeni cenilec</div>
<div class="footer"><span>Zaupno</span><span>{$id_fmt} &mdash; {$danes}</span></div>
</body></html>
HTML;

// Poiščemo dompdf
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

if (class_exists('Dompdf\Dompdf')) {
    $options = new \Dompdf\Options();
    $options->set('defaultFont', 'DejaVu Sans');
    $pdf = new \Dompdf\Dompdf($options);
    $pdf->loadHtml($html);
    $pdf->setPaper('A4', 'portrait');
    $pdf->render();
    
    $filename = 'cenitev-' . $c['id'] . '-' . $danes . '.pdf';
    $pdfVsebina = $pdf->output();
    $base64 = base64_encode($pdfVsebina);
    
    // Vrni HTML stran ki hkrati prenese in prikaže PDF
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
    <!DOCTYPE html>
    <html><head><meta charset="UTF-8"><title>Cenitev {$id_fmt}</title>
    <style>
        * { margin:0;padding:0;box-sizing:border-box; }
        body { font-family:Arial,sans-serif; background:#1A2332; }
        iframe { width:100vw; height:100vh; border:none; display:block; }
        .bar { background:#C9A84C; color:#1A2332; padding:.6rem 1rem;
               font-weight:bold; font-size:.9rem; display:flex;
               align-items:center; justify-content:space-between; }
        .bar a { background:#1A2332; color:#fff; padding:.4rem .9rem;
                 border-radius:6px; text-decoration:none; font-size:.85rem; }
    </style>
    </head>
    <body>
    <div class="bar">
        <span>◈ {$id_fmt} — {$cenik}</span>
        <a id="dl" href="#">⬇ Prenesi PDF</a>
    </div>
    <iframe id="viewer"></iframe>
    <script>
        const b64  = '{$base64}';
        const bin  = atob(b64);
        const buf  = new Uint8Array(bin.length);
        for (let i = 0; i < bin.length; i++) buf[i] = bin.charCodeAt(i);
        const blob = new Blob([buf], { type: 'application/pdf' });
        const url  = URL.createObjectURL(blob);

        // Prikaži v iframeu
        document.getElementById('viewer').src = url;

        // Hkrati sproži prenos
        const a = document.createElement('a');
        a.href = url;
        a.download = '{$filename}';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);

        // Gumb za ponovni prenos
        document.getElementById('dl').href = url;
        document.getElementById('dl').download = '{$filename}';
    </script>
    </body></html>
    HTML;

} else {
    // Fallback brez dompdf
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    echo '<script>window.onload=()=>window.print()</script>';
}