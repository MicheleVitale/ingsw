<?php
$host = "localhost";
$user = "root";
$pass = "";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass);
    $conn->query("DROP DATABASE IF EXISTS vulnerabile");
    $conn->query("CREATE DATABASE vulnerabile CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $conn->select_db("vulnerabile");
    $sqlUtenti = "CREATE TABLE utenti (
        ID INT(6) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        Nome VARCHAR(15),
        Cognome VARCHAR(25),
        DataNascita DATE, 
        Username VARCHAR(40),
        Email VARCHAR(60),
        Passwd VARCHAR(255), 
        Ruolo ENUM('Impiegato', 'Amministratore', 'Manager') DEFAULT 'Impiegato',
        Stipendio INT(6) DEFAULT 0
    ) ENGINE=InnoDB";

    $conn->query($sqlUtenti);
    $sqlReclami = "CREATE TABLE reclami (
        ID INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        Utente INT(11) NOT NULL,
        Oggetto VARCHAR(255) NOT NULL,
        Messaggio TEXT NOT NULL,
        DataInvio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        Letto TINYINT(1) DEFAULT 0,
        FOREIGN KEY (Utente) REFERENCES utenti(ID) ON DELETE CASCADE
    ) ENGINE=InnoDB";

    $conn->query($sqlReclami);
    $insertUtenti = "INSERT INTO utenti (Nome, Cognome, DataNascita, Username, Email, Passwd, Ruolo, Stipendio) VALUES 
        ('Mario', 'Rossi', '1980-05-15', 'mariorossi80', 'mario.rossi@scamspa.it', 'admin123', 'Amministratore', 50000),
        ('Luigi', 'Verdi', '1985-11-20', 'luigiverdi85', 'luigi.verdi@scamspa.it', 'manager123', 'Manager', 40000),
        ('Anna', 'Bianchi', '1995-07-30', 'annabianchi95', 'anna.bianchi@scamspa.it', 'user123', 'Impiegato', 25000)";
    
    $conn->query($insertUtenti);
    $conn->query("INSERT INTO reclami (Utente, Oggetto, Messaggio) VALUES 
        (3, 'PC Lento', 'Il computer impiega troppo tempo ad avviarsi al mattino.')");
        
    $conn->close();
    header("Location: login.php?status=reset");
    exit();

} catch (Exception $e) {
    die("Errore Critico Setup: " . $e->getMessage());
}
?>