<?php
// register.php

require_once 'includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Preusmeri prijavljenega
if (trenutniUporabnik()) {
    header('Location: cenitve.php');
    exit;
}

$napaka  = '';
$uspeh   = '';
$values  = ['ime' => '', 'priimek' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ime     = trim($_POST['ime']     ?? '');
    $priimek = trim($_POST['priimek'] ?? '');
    $email   = trim($_POST['email']   ?? '');
    $geslo   = $_POST['geslo']        ?? '';
    $geslo2  = $_POST['geslo2']       ?? '';

    $values  = compact('ime', 'priimek', 'email');

    // Validacija
    if (!$ime || !$priimek || !$email || !$geslo) {
        $napaka = 'Vsa polja so obvezna.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $napaka = 'Vpišite veljaven email naslov.';
    } elseif (mb_strlen($geslo) < 6) {
        $napaka = 'Geslo mora vsebovati vsaj 6 znakov.';
    } elseif ($geslo !== $geslo2) {
        $napaka = 'Gesli se ne ujemata.';
    } else {
        try {
            // Preveri unikatnost emaila
            $check = db()->prepare('SELECT id FROM uporabniki WHERE email = ?');
            $check->execute([$email]);
            if ($check->fetch()) {
                $napaka = 'Ta email naslov je že registriran.';
            } else {
                $hash = password_hash($geslo, PASSWORD_BCRYPT);
                $ins  = db()->prepare(
                    'INSERT INTO uporabniki (ime, priimek, email, geslo) VALUES (?, ?, ?, ?)'
                );
                $ins->execute([$ime, $priimek, $email, $hash]);
                $uspeh = 'Registracija je uspela! Prijavite se.';
                $values = ['ime' => '', 'priimek' => '', 'email' => ''];
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $napaka = 'Napaka sistema. Poskusite znova.';
        }
    }
}

$pageTitle = 'Registracija – ' . APP_NAME;
require 'includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-card card">
        <div class="card-body">
            <div class="auth-hero">
                <h1>Ustvarite račun</h1>
                <p class="text-muted">Registrirajte se za dostop do sistema cenitev</p>
            </div>

            <?php if ($napaka): ?>
                <div class="alert alert-danger"><?= e($napaka) ?></div>
            <?php endif; ?>
            <?php if ($uspeh): ?>
                <div class="alert alert-success"><?= e($uspeh) ?></div>
            <?php endif; ?>

            <form method="post" action="register.php" data-validate>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="ime">Ime *</label>
                        <input type="text" id="ime" name="ime" class="form-control"
                               value="<?= e($values['ime']) ?>" required autocomplete="given-name">
                        <span class="form-error"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="priimek">Priimek *</label>
                        <input type="text" id="priimek" name="priimek" class="form-control"
                               value="<?= e($values['priimek']) ?>" required autocomplete="family-name">
                        <span class="form-error"></span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Email naslov *</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?= e($values['email']) ?>" required autocomplete="email">
                    <span class="form-error"></span>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="geslo">Geslo *</label>
                        <input type="password" id="geslo" name="geslo" class="form-control"
                               required autocomplete="new-password" minlength="6">
                        <span class="form-error"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="geslo2">Potrdite geslo *</label>
                        <input type="password" id="geslo2" name="geslo2" class="form-control"
                               required autocomplete="new-password">
                        <span class="form-error"></span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100">Registracija</button>
            </form>

            <p class="auth-footer">
                Že imate račun? <a href="login.php">Prijavite se</a>
            </p>
        </div>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
