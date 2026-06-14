<?php
// index.php – začetna stran

require_once 'includes/config.php';
$user = trenutniUporabnik();

$pageTitle = APP_NAME . ' – Sistem za upravljanje cenitev';
require 'includes/header.php';
?>

<div class="container">
    <div class="hero-section">
        <h1>Upravljanje cenitev<br>nepremičnin</h1>
        <p>Profesionalni sistem za evidenco in upravljanje cenitev. Dodajajte, urejajte
           in pregledujte cenitve na enem mestu, hitro in varno.</p>
        <?php if ($user): ?>
            <a href="cenitve.php" class="btn btn-gold btn-lg">Moje cenitve →</a>
        <?php else: ?>
            <div style="display:flex;gap:.75rem;flex-wrap:wrap">
                <a href="register.php" class="btn btn-gold btn-lg">Začnite brezplačno</a>
                <a href="login.php"    class="btn btn-ghost btn-lg" style="color:#fff;border-color:rgba(255,255,255,.3)">Prijava</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-num">6</div>
            <div class="stat-label">Namenov cenitev</div>
        </div>
        <div class="stat-card">
            <div class="stat-num">4</div>
            <div class="stat-label">Podlag vrednosti</div>
        </div>
        <div class="stat-card">
            <div class="stat-num">3</div>
            <div class="stat-label">Premise vrednosti</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.25rem;margin-bottom:2rem">
        <div class="card">
            <div class="card-body">
                <div style="font-size:1.75rem;margin-bottom:.75rem">🔐</div>
                <h3 class="mb-1">Varni računi</h3>
                <p>Registracija in prijava z bcrypt hashiranjem gesel ter zaščito sej.</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div style="font-size:1.75rem;margin-bottom:.75rem">📋</div>
                <h3 class="mb-1">Upravljanje cenitev</h3>
                <p>Dodajanje, urejanje in brisanje cenitev z vsemi zahtevanimi polji.</p>
            </div>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
