<?php
session_start();

if (!isset($_SESSION['user_id'])) { die("Non sei loggato."); }

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "vulnerabile";
$conn = new mysqli($host, $user, $pass, $dbname);
if (isset($_GET['nome']) && isset($_GET['cognome']) && isset($_GET['nuovo_stipendio'])) {
    
    $target_nome = $_GET['nome'];
    $target_cognome = $_GET['cognome'];
    $amount = floatval($_GET['nuovo_stipendio']);
    $stmt = $conn->prepare("UPDATE utenti SET Stipendio = ? WHERE Nome = ? AND Cognome = ?");
    $stmt->bind_param("dss", $amount, $target_nome, $target_cognome);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            file_put_contents("log_truffe.txt", "Aumento a $target_nome $target_cognome eseguito.\n", FILE_APPEND);
            echo "Stipendio aggiornato!";
        } else {
            echo "Nessun utente trovato con questo Nome e Cognome.";
        }
    } else {
        echo "Errore SQL.";
    }
}
$conn->close();
?>