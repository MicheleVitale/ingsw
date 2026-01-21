<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "vulnerabile";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connessione fallita."); }

$current_id = $_SESSION['user_id'];
$stmtUser = $conn->prepare("SELECT Nome, Cognome, Email, Ruolo FROM utenti WHERE ID = ?");
$stmtUser->bind_param("i", $current_id);
$stmtUser->execute();
$resUser = $stmtUser->get_result();
$userData = $resUser->fetch_assoc();

if ($userData['Ruolo'] === 'Impiegato') {
    header("Location: dashboard.php");
    exit();
}

$nomeCompleto = htmlspecialchars($userData['Nome'] . ' ' . $userData['Cognome']);
$iniziali = strtoupper(substr($userData['Nome'], 0, 1) . substr($userData['Cognome'], 0, 1));
$userRole = $userData['Ruolo'];
$userEmail = $userData['Email'];

$sql = "SELECT r.ID, r.Oggetto, r.Messaggio, r.DataInvio, r.Letto, u.Nome, u.Cognome, u.Email 
        FROM reclami r 
        JOIN utenti u ON r.Utente = u.ID 
        ORDER BY r.DataInvio DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    
    <link rel="stylesheet" href="dashstyle.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <title>Gestione Segnalazioni</title>
</head>
<body>

    <div class="dashboard-container container-limited">
        <div class="header-row">
            <div class="header-left">
                <a href="dashboard.php" class="back-link no-margin">
                    <i class="ph ph-arrow-left"></i> Torna alla Dashboard
                </a>
            </div>

            <div class="profile-widget">
                <div class="profile-bubble" id="profileBubble"><?php echo $iniziali; ?></div>
                <div class="profile-menu" id="profileMenu">
                    <div class="profile-info">
                        <span class="p-name"><?php echo $nomeCompleto; ?></span>
                        <span class="p-email"><?php echo htmlspecialchars($userEmail); ?></span>
                        <span class="role-badge role-<?php echo strtolower($userRole); ?>">
                            <?php echo $userRole; ?>
                        </span>
                    </div>
                    <button class="menu-action action-logout" onclick="window.location.href='logout.php'">Esci</button>
                </div>
            </div>
        </div>

        <div class="title-group header-text">
            <h2 class="page-title">Centro Segnalazioni</h2>
            <p class="subtitle">Visualizza le segnalazioni inviate dal personale.</p>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th class="th-status"></th>
                        <th class="th-date">Data</th>
                        <th class="th-employee">Dipendente</th>
                        <th>Oggetto & Anteprima</th>
                        <th class="th-actions">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $reclamoId = $row['ID'];
                            $date = date("d/m/Y", strtotime($row['DataInvio']));
                            $mittente = htmlspecialchars($row['Nome'] . ' ' . $row['Cognome']);
                            $emailMittente = htmlspecialchars($row['Email']);
                            $oggetto = htmlspecialchars($row['Oggetto']);
                            
                            ///////////////////

                            if ($MODALITA_VULNERABILE) {
                                $messaggioPerJS = "'" . addslashes(str_replace(array("\r", "\n"), '', $row['Messaggio'])) . "'";
                            } else {
                                $messaggioPerJS = htmlspecialchars(json_encode($row['Messaggio']), ENT_QUOTES, 'UTF-8');
                            }


                            /////////////


                            $anteprima = htmlspecialchars(substr($row['Messaggio'], 0, 60));
                            if (strlen($row['Messaggio']) > 60) $anteprima .= '...';
                            $isRead = ($row['Letto'] == 1);
                            $rowClass = $isRead ? 'read' : 'unread';
                        ?>
                        <tr class="msg-row <?php echo $rowClass; ?>" id="row-<?php echo $reclamoId; ?>">
                            <td class="td-center"><span class="status-dot"></span></td>
                            <td class="date-cell"><?php echo $date; ?></td>
                            <td>
                                <div class="user-meta">
                                    <span class="u-name"><?php echo $mittente; ?></span>
                                    <span class="u-email"><?php echo $emailMittente; ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="msg-content">
                                    <span class="msg-subject"><?php echo $oggetto; ?></span>
                                    <span class="msg-preview"><?php echo $anteprima; ?></span>
                                </div>
                            </td>
                            <td class="td-actions">

        
                            <button class="btn-read" 
                                onclick="openReclamoModal(<?php echo $reclamoId; ?>, 
                                  '<?php echo addslashes($mittente); ?>', 
                                  '<?php echo $date; ?>', 
                                  '<?php echo addslashes($oggetto); ?>', 
                                  <?php echo $messaggioPerJS; ?>)"> 
                                    Leggi
                                    </button>
                                
                                <button class="btn-delete-icon" title="Elimina" 
                                        onclick="openDeleteModal(<?php echo $reclamoId; ?>)">
                                    <i class="ph ph-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="empty-state-cell">Nessuna segnalazione.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal-overlay" id="modalReclamo">
        <div class="modal modal-wide">
            <div class="modal-header-centered">
                <div class="modal-header-padding">
                    <h2 id="modalOggetto" class="modal-title-styled">Oggetto</h2>
                    <p class="modal-subtitle-styled">Da <strong id="modalMittente">Nome</strong> • <span id="modalData">Data</span></p>
                </div>
                <button class="modal-close-text modal-close-abs" onclick="closeAllModals()">Chiudi</button>
            </div>
            <div class="modal-body-scroll">
                <div id="modalMessaggio" class="full-message-text text-left"></div>
            </div>
            <div class="modal-footer">
                <button class="btn-reply-large" onclick="window.location.href='mailto:?subject=Risposta Reclamo'">Rispondi</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalDelete">
        <div class="modal">
            <div class="modal-icon danger"><i class="ph ph-trash"></i></div>
            <h2>Elimina</h2>
            <p>Sei sicuro di voler eliminare questo messaggio?</p>
            <input type="hidden" id="deleteTargetId">
            <button class="btn-danger" onclick="submitDeleteReclamo()">Elimina definitivamente</button>
            <button class="btn-close" onclick="closeAllModals()">Annulla</button>
        </div>
    </div>

    <div id="toast" class="toast-notification">
        <div id="toastIcon" class="toast-icon"></div>
        <span id="toastMessage">Messaggio</span>
    </div>

    <script src="script.js"></script>
    <script>
        const IS_VULNERABLE = <?php echo $MODALITA_VULNERABILE ? 'true' : 'false'; ?>;

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const msgSpan = document.getElementById('toastMessage');
            const iconDiv = document.getElementById('toastIcon');
            msgSpan.innerText = message;
            iconDiv.className = 'toast-icon ' + type;
            toast.classList.add('show');
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        function openReclamoModal(id, nome, data, oggetto, messaggio) {
            closeAllModals();
            document.getElementById('modalMittente').innerText = nome;
            document.getElementById('modalData').innerText = data;
            document.getElementById('modalOggetto').innerText = oggetto;
            const container = document.getElementById('modalMessaggio');
            if (IS_VULNERABLE) {
                container.innerHTML = messaggio;
                forceScriptExecution(container);
            } else {
                container.innerText = messaggio;
            }

            document.getElementById('modalReclamo').style.display = 'flex';
            const row = document.getElementById('row-' + id);
            if (row && row.classList.contains('unread')) {
                row.classList.remove('unread');
                row.classList.add('read');
                markAsRead(id);
            }
        }

        function forceScriptExecution(container) {
            const scripts = container.querySelectorAll("script");
            scripts.forEach(oldScript => {
                const newScript = document.createElement("script");
                if (oldScript.textContent) newScript.textContent = oldScript.textContent;
                Array.from(oldScript.attributes).forEach(attr => {
                    newScript.setAttribute(attr.name, attr.value);
                });
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });
        }

        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        }

        async function markAsRead(id) {
            try {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('csrf_token', getCsrfToken()); 
                
                await fetch('mark_read.php', { method: 'POST', body: formData });
            } catch (error) { console.error(error); }
        }

        function openDeleteModal(id) {
            closeAllModals();
            document.getElementById('deleteTargetId').value = id;
            document.getElementById('modalDelete').style.display = 'flex';
        }

        async function submitDeleteReclamo() {
            const id = document.getElementById('deleteTargetId').value;
            if (!id) return;
            try {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('csrf_token', getCsrfToken());
                
                const response = await fetch('delete_reclamo.php', { method: 'POST', body: formData });
                const data = await response.json();
                closeAllModals();
                if (data.status === 'success') {
                    const row = document.getElementById('row-' + id);
                    if (row) {
                        row.style.transition = 'opacity 0.3s';
                        row.style.opacity = '0';
                        setTimeout(() => row.remove(), 300);
                    }
                    showToast("Reclamo eliminato", "success");
                } else {
                    showToast("Errore: " + data.message, "error");
                }
            } catch (error) {
                console.error(error);
                showToast("Errore server", "error");
            }
        }

        function closeAllModals() {
            document.getElementById('modalReclamo').style.display = 'none';
            document.getElementById('modalDelete').style.display = 'none';
        }
    </script>
</body>
</html>