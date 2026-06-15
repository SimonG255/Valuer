<?php
// login.php

require_once 'includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (trenutniUporabnik()) {
    header('Location: cenitve.php');
    exit;
}

$napaka = '';
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $geslo = $_POST['geslo']      ?? '';

    if (!$email || !$geslo) {
        $napaka = 'Vpišite email in geslo.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $napaka = 'Neveljaven email naslov.';
    } else {
        try {
            $stmt = db()->prepare('SELECT * FROM uporabniki WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($geslo, $user['geslo'])) {
                $_SESSION[SESSION_KEY] = [
                    'id'      => $user['id'],
                    'ime'     => $user['ime'],
                    'priimek' => $user['priimek'],
                    'email'   => $user['email'],
                ];
                session_regenerate_id(true);
                header('Location: cenitve.php');
                exit;
            } else {
                $napaka = 'Napačen email ali geslo.';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $napaka = 'Napaka sistema. Poskusite znova.';
        }
    }
}

$pageTitle = 'Prijava – ' . APP_NAME;
require 'includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-card card">
        <div class="card-body">
            <div class="auth-hero">
                <div style="font-size:2.5rem;color:var(--gold);margin-bottom:.5rem">◈</div>
                <h1>Dobrodošli nazaj</h1>
                <p class="text-muted">Prijavite se v vaš račun</p>
            </div>

            <?php if ($napaka): ?>
                <div class="alert alert-danger"><?= e($napaka) ?></div>
            <?php endif; ?>

            <form method="post" action="login.php" data-validate>
                <div class="form-group">
                    <label class="form-label" for="email">Email naslov *</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?= e($email) ?>" required autocomplete="email">
                    <span class="form-error"></span>
                </div>
                <div class="form-group">
                    <label class="form-label" for="geslo">Geslo *</label>
                    <input type="password" id="geslo" name="geslo" class="form-control"
                           required autocomplete="current-password">
                    <span class="form-error"></span>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100">Prijava</button>
            </form>

            <p class="auth-footer">
                Nimate računa? <a href="register.php">Registrirajte se</a>
            </p>
        </div>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
