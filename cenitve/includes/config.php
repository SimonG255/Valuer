<?php
// includes/config.php – konfiguracija aplikacije

define('DB_HOST',     'localhost');
define('DB_NAME', 'cenitve_db1');
define('DB_USER',     'root');       // prilagodite svojemu okolju
define('DB_PASS',     '');           // prilagodite svojemu okolju
define('DB_CHARSET',  'utf8mb4');

define('APP_NAME',    'Cenitve Nepremičnin');
define('SESSION_KEY', 'cenitve_user');

// Vzpostavi PDO povezavo
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// Vrne ID prijavljenega uporabnika ali null
function trenutniUporabnik(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION[SESSION_KEY] ?? null;
}

// Zahteva prijavo – preusmeri na login če ni prijavljen
function zahtevajPrijavo(): void {
    if (trenutniUporabnik() === null) {
        header('Location: login.php');
        exit;
    }
}

// XSS-varna izpis vrednosti
function e(mixed $val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Labeliranje enum vrednosti
const NAMEN_LABELS = [
    'zavarovano_posojanje'  => 'Zavarovano posojanje',
    'sodni_postopek'        => 'Sodni postopek',
    'stecajni_postopek'     => 'Stečajni postopek',
    'racunovodsko_porocanje'=> 'Računovodsko poročanje',
    'davcni_postopek'       => 'Davčni postopek',
    'poslovna_odlocitev'    => 'Poslovna odločitev naročnika',
];

const PODLAGA_LABELS = [
    'trzna_vrednost'         => 'Tržna vrednost',
    'likvidacijska_vrednost' => 'Likvidacijska vrednost',
    'trzna_najemnina'        => 'Tržna najemnina',
    'pravicna_vrednost'      => 'Pravična vrednost',
];

const PREMISA_LABELS = [
    'sedanja_uporaba'          => 'Sedanja ali obstoječa uporaba',
    'najgospodarnejsa_uporaba' => 'Najgospodarnejša uporaba',
    'redna_likvidacija'        => 'Redna likvidacija',
];
