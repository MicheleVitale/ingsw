<?php
session_start();

require_once 'config.php';
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "vulnerabile";

$messaggio = "";
$erroreLogin = "";

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'Amministratore' || $_SESSION['user_role'] === 'Manager') {
        header("Location: dashboard.php");
        exit();
    }
}

if (isset($_POST['accedi'])) {
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) { die("Connessione fallita: " . $conn->connect_error); }
    $email = $_POST['email'];
    $password = $_POST['password'];
    if ($MODALITA_VULNERABILE) {
        $sql = "SELECT ID, Nome, Cognome, Ruolo, Passwd FROM utenti WHERE Email = '$email'";
        $result = $conn->query($sql);
        if (!$result) {
            die("Errore SQL (Simulazione): " . $conn->error);
        }

    } else {
        $stmt = $conn->prepare("SELECT ID, Nome, Cognome, Ruolo, Passwd FROM utenti WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    }

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stored_password = $row['Passwd'];
        if ($password === $stored_password || password_verify($password, $stored_password)) {
            $_SESSION['user_id'] = $row['ID'];
            $_SESSION['user_role'] = $row['Ruolo'];
            $_SESSION['user_name'] = $row['Nome'] . " " . $row['Cognome'];

            if ($row['Ruolo'] === 'Amministratore' || $row['Ruolo'] === 'Manager') {
                header("Location: dashboard.php");
                exit();
            }
            else {
                header("Location: reclami.php");
                exit();
            }
        }
        else {
            $erroreLogin = "Email o password errate. Riprova.";
        }
    }
    else {
        $erroreLogin = "Email o password errate. Riprova.";
    }

    $conn->close();
}

if (isset($_POST['registrati'])) {
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) { die("Connessione fallita: " . $conn->connect_error); }

    $nome = $_POST["nome"];
    $cognome = $_POST["cognome"];
    $giorno = $_POST["giorno"];
    $mese = $_POST["mese"];
    $anno = $_POST["anno"];
    $email = $_POST["email"];
    $password = $_POST["password"];

    $anno_short = substr($anno, -2);
    $username = strtolower($nome . $cognome . $anno_short);
    $nascita = "$anno-$mese-$giorno";
    $ruolo = "Impiegato";

    if ($MODALITA_VULNERABILE) {
        $password_da_salvare = $password; 
    } else {
        $password_da_salvare = password_hash($password, PASSWORD_DEFAULT);
    }

    $stmt = $conn->prepare("INSERT INTO utenti(Nome, Cognome, DataNascita, Username, Email, Passwd, Ruolo) VALUES(?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $nome, $cognome, $nascita, $username, $email, $password_da_salvare, $ruolo);

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = "Registrazione completata! Effettua l'accesso.";
        $_SESSION['flash_type'] = "success";
        header("Location: " . $_SERVER['PHP_SELF']); 
        exit();
    } else {
        $messaggio = "Errore: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css"> <title>Login</title>
</head>
<body>

    <div id="toast" class="toast-notification">
    <div id="toastIcon" class="toast-icon"></div>
    <span id="toastMessage">Messaggio</span>
</div>

    <div class="container">
        <div class="form-box active" id="login-form">
            <form action="" method="POST">
                <h2>Accedi</h2>
                <div class="field">
                    <input type="text" name="email" placeholder=" " required>
                    <label>Email</label>
                </div>
                <div class="field">
                    <input type="password" name="password" placeholder=" " required>
                    <label>Password</label>
                </div>
                <button type="submit" name="accedi">Accedi</button>
                <p><a href="#" onclick="showForm('register-form')">Crea un account aziendale</a></p>
            </form>
        </div>

        <div class="form-box" id="register-form">
            <form action="" method="POST">
                <h2>Registrati</h2>
                <div class="row">
                    <div class="field">
                        <input type="text" name="nome" placeholder=" " required>
                        <label>Nome</label>
                    </div>
                    <div class="field">
                        <input type="text" name="cognome" placeholder=" " required>
                        <label>Cognome</label>
                    </div>
                </div>
                <div class="row">
                    <div class="field">
                        <select name="giorno" required>
                            <option value="" disabled selected hidden>Giorno</option>
                            <?php for($d = 1; $d <= 31; $d++): ?>
                                <option value="<?= $d ?>"><?= $d ?></option>
                            <?php endfor; ?>
                        </select>
                        <label>Giorno</label>
                    </div>
                    <div class="field">
                        <select name="mese" required>
                            <option value="" disabled selected hidden>Mese</option>
                            <?php
                                $meseArr = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
                                foreach ($meseArr as $i => $m): ?>
                                    <option value="<?= $i+1 ?>"><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>Mese</label>
                    </div>
                    <div class="field">
                        <select name="anno" required>
                            <option value="" disabled selected hidden>Anno</option>
                            <?php
                                $current = (int)date('Y');
                                for ($y = $current; $y >= 1875; $y--): ?>
                                    <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                        <label>Anno</label>
                    </div>
                </div>
                <div class="field">
                    <input type="email" name="email" placeholder=" " required>
                    <label>nome@example.com</label>
                </div>
                <div class="field">
                    <input type="password" name="password" placeholder=" " required>
                    <label>Password</label>
                </div>
                <button type="submit" name="registrati">Registrati</button>
                <p>Hai già un account aziendale? <a href="#" onclick="showForm('login-form')">Accedi<img width="15" height="15" src="icons8-up-right-15.png" alt="up-right-arrow"/></a></p>
            </form>
        </div>
    </div>

    <script src="script.js"></script>

    <script>
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const msgSpan = document.getElementById('toastMessage');
            const iconDiv = document.getElementById('toastIcon');
            msgSpan.innerText = message;
            iconDiv.className = 'toast-icon ' + type; 
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        <?php if (!empty($erroreLogin)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showToast("<?php echo addslashes($erroreLogin); ?>", 'error');
            });
        <?php endif; ?>

        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');

        if (status) {
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof showForm === 'function') {
                    showForm('login-form'); 
                }

                window.history.replaceState({}, document.title, window.location.pathname);
                if (status === 'success') {
                    showToast("Registrazione completata! Effettua l'accesso.", 'success');
                } 
                else if (status === 'reset') {
                    showToast("Database ripristinato correttamente.", 'info');
                }
            });
        }
    </script>
</body>
</html>