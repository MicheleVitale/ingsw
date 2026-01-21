<?php
session_start();
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_OFF);
require_once 'config.php';

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "vulnerabile";
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) { 
    echo json_encode(["status" => "error", "message" => "Connessione DB fallita"]);
    exit;
}

// 2. Verifica Autenticazione
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Sessione scaduta"]);
    exit;
}

// 3. Verifica CSRF e Metodo (UNIFICATO)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Metodo non consentito"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!$MODALITA_VULNERABILE) {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            echo json_encode(["status" => "error", "message" => "Errore CSRF: Token non valido"]);
            exit;
        }
    }
}

// 4. Verifica Permessi
$current_id = $_SESSION['user_id'];
$stmtAuth = $conn->prepare("SELECT Ruolo FROM utenti WHERE ID = ?");
$stmtAuth->bind_param("i", $current_id);
$stmtAuth->execute();
$resAuth = $stmtAuth->get_result();
$userRow = $resAuth->fetch_assoc();

if (!$userRow || !in_array($userRow['Ruolo'], ['Amministratore', 'Manager'])) {
    echo json_encode(["status" => "error", "message" => "Non hai i permessi necessari"]);
    exit;
}

$myRole = $userRow['Ruolo'];
    
    $action = $_POST['action'] ?? '';
    $target_id = $_POST['id'] ?? 0;

    if ($target_id == $current_id) {
        echo json_encode([
            "status" => "error", 
            "message" => "Azione Negata: Non puoi modificare o eliminare il tuo stesso account"
        ]);
        exit;
    }

    $stmtTarget = $conn->prepare("SELECT Ruolo FROM utenti WHERE ID = ?");
    $stmtTarget->bind_param("i", $target_id);
    $stmtTarget->execute();
    $resTarget = $stmtTarget->get_result();

    if ($resTarget->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Utente bersaglio non trovato"]);
        exit;
    }

    $targetData = $resTarget->fetch_assoc();
    $targetRole = $targetData['Ruolo'];

    if (in_array($myRole, ['Admin', 'Amministratore']) && $targetRole === 'Manager') {
        echo json_encode([
            "status" => "error", 
            "message" => "Azione Negata: Un Amministratore non può modificare un Manager"
        ]);
        exit;
    }

    switch ($action) {
        
        case 'update_ruolo':
            $nuovoRuolo = $_POST['ruolo'];
            $stmt = $conn->prepare("UPDATE utenti SET Ruolo = ? WHERE ID = ?");
            $stmt->bind_param("si", $nuovoRuolo, $target_id);
            
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Ruolo aggiornato con successo"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Errore SQL: " . $conn->error]);
            }
            $stmt->close();
            break;

        case 'update_stipendio':
            $nuovoStipendio = $_POST['stipendio'];
            if (!is_numeric($nuovoStipendio)) { 
                echo json_encode(["status" => "error", "message" => "Lo stipendio deve essere un numero"]);
                exit; 
            }
            
            $stmt = $conn->prepare("UPDATE utenti SET Stipendio = ? WHERE ID = ?");
            $stmt->bind_param("di", $nuovoStipendio, $target_id);
            
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Stipendio aggiornato con successo"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Errore SQL: " . $conn->error]);
            }
            $stmt->close();
            break;

        case 'delete_utente':
            $stmt = $conn->prepare("DELETE FROM utenti WHERE ID = ?");
            $stmt->bind_param("i", $target_id);
            
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Utente eliminato (Cascade attivo)"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Errore SQL: " . $conn->error]);
            }
            $stmt->close();
            break;

        default:
            echo json_encode(["status" => "error", "message" => "Azione non valida"]);
            break;
    }

$conn->close();
?>