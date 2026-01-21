<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "vulnerabile";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connessione fallita."); }

$toastMessage = "";
$toastType = ""; 

if (isset($_SESSION['flash_message'])) {
    $toastMessage = $_SESSION['flash_message'];
    $toastType = $_SESSION['flash_type'];
    
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

$current_id = $_SESSION['user_id'];
$stmtUser = $conn->prepare("SELECT Nome, Cognome, Email, Ruolo FROM utenti WHERE ID = ?");
$stmtUser->bind_param("i", $current_id);
$stmtUser->execute();
$resUser = $stmtUser->get_result();

if ($resUser->num_rows === 0) {
    header("Location: logout.php");
    exit();
}

$userData = $resUser->fetch_assoc();
$userRole = $userData['Ruolo'];
$userEmail = $userData['Email'];
$nomeCompleto = htmlspecialchars($userData['Nome'] . ' ' . $userData['Cognome']);
$iniziali = strtoupper(substr($userData['Nome'], 0, 1) . substr($userData['Cognome'], 0, 1));

if ($userRole === 'Impiegato') {
    $showBackLink = false;
    $pageSubtitle = "Benvenuto, " . htmlspecialchars($userData['Nome']) . ". Invia qui le tue segnalazioni.";
} else {
    $showBackLink = true;
    $pageSubtitle = "Compila il modulo per inviare una segnalazione.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $oggetto = trim($_POST['oggetto']);
    $messaggio = trim($_POST['messaggio']);

    if (empty($oggetto) || empty($messaggio)) {
        $toastMessage = "Compila tutti i campi.";
        $toastType = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO reclami (Utente, Oggetto, Messaggio) VALUES (?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("iss", $current_id, $oggetto, $messaggio);

            if ($stmt->execute()) {
                $_SESSION['flash_message'] = "Segnalazione inviata con successo";
                $_SESSION['flash_type'] = "success";
                header("Location: " . $_SERVER['PHP_SELF']); 
                exit(); 

            } else {
                $toastMessage = "Errore invio: " . $stmt->error;
                $toastType = "error";
            }
            $stmt->close();
        } else {
            $toastMessage = "Errore Query: " . $conn->error;
            $toastType = "error";
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashstyle.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <title>Invia Reclamo</title>
</head>
<body>

    <div class="dashboard-container reclami-view-container">
        
        <div class="header-row">
            <div class="header-left">
                <?php if ($showBackLink): ?>
                    <a href="dashboard.php" class="back-link back-link-nomargin">
                        <i class="ph ph-arrow-left"></i> Torna alla Dashboard
                    </a>
                <?php else: ?>
                    <img src="logo.png" alt="Logo" class="dashboard-logo">
                <?php endif; ?>
            </div>

            <div class="profile-widget">
                <div class="profile-bubble" id="profileBubble"><?php echo $iniziali; ?></div>
                <div class="profile-menu" id="profileMenu">
                    <div class="profile-info">
                        <span class="p-name"><?php echo $nomeCompleto; ?></span>
                        <span class="p-email"><?php echo htmlspecialchars($userEmail); ?></span>
                        <span class="role-badge role-<?php echo strtolower($userRole); ?> role-badge-margin">
                            <?php echo $userRole; ?>
                        </span>
                    </div>
                    <button class="menu-action action-logout" onclick="window.location.href='logout.php'">Esci</button>
                </div>
            </div>
        </div>

        <div class="title-group">
            <h2 class="page-title">Assistenza & Reclami</h2>
            <p class="page-desc"><?php echo $pageSubtitle; ?></p>
        </div>

        <form action="" method="POST" id="formReclami">
            <div class="form-control form-control-top">
                <label class="form-label-styled">Oggetto</label>
                <input type="text" name="oggetto" placeholder="Es. Errore busta paga..." required
                       value="<?php echo ($toastType == 'error' && isset($_POST['oggetto'])) ? htmlspecialchars($_POST['oggetto']) : ''; ?>">
            </div>

            <div class="form-control">
                <label class="form-label-styled">Messaggio</label>
                <textarea name="messaggio" placeholder="Descrivi il problema..." required><?php echo ($toastType == 'error' && isset($_POST['messaggio'])) ? htmlspecialchars($_POST['messaggio']) : ''; ?></textarea>
            </div>

            <div class="btn-wrapper">
                <button type="submit" class="btn-primary">Invia segnalazione</button>
            </div>
        </form>
    </div>

    <div id="toast" class="toast-notification">
        <div class="toast-icon" id="toastIcon"></div>
        <span id="toastMessage">Messaggio</span>
    </div>

    <script src="script.js"></script>