<?php
// api/delete.php – AJAX brisanje cenitve

require_once '../includes/config.php';

header('Content-Type: application/json');

// Preveri prijavo
$user = trenutniUporabnik();
if (!$user) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Niste prijavljeni.']);
    exit;   
}

// Preveri metodo
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Napačna metoda.']);
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    echo json_encode(['ok' => false, 'error' => 'Neveljaven ID.']);
    exit;
}

try {
    // Briši samo cenitev ki pripada prijavljenemu uporabniku
    $stmt = db()->prepare(
        'DELETE FROM cenitve WHERE id = ? AND uporabnik_id = ?'
    );
    $stmt->execute([$id, $user['id']]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['ok' => false, 'error' => 'Cenitev ne obstaja ali nimate dovoljenja.']);
    } else {
        echo json_encode(['ok' => true]);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Napaka baze podatkov.']);
}
