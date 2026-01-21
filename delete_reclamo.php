<?php
session_start();
header('Content-Type: application/json');

require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Non autorizzato']);
    exit;
}

if (!$MODALITA_VULNERABILE) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'Violazione sicurezza: Token CSRF non valido.']);
        exit;
    }
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "vulnerabile";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM reclami WHERE ID = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Errore SQL']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ID non valido']);
    }
}
$conn->close();
?>