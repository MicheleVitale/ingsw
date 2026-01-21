<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "vulnerabile";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Aggiorna la pagina senza rifare il login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; 
}

$current_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT Nome, Cognome, Email, Ruolo FROM utenti WHERE ID = ?");
$stmt->bind_param("i", $current_id);
$stmt->execute();
$resUser = $stmt->get_result();

if ($resUser->num_rows === 0) {
    die("Errore: Utente loggato non trovato nel database.");
}

$adminUser = $resUser->fetch_assoc();
$ruoliPermessi = ['Amministratore', 'Manager'];

if (!in_array($adminUser['Ruolo'], $ruoliPermessi)) {
    die("<h1>Accesso Negato</h1><p>Non hai i permessi (Admin o Manager) per accedere a questa dashboard.</p>");
}

$adminNomeCompleto = htmlspecialchars($adminUser['Nome'] . ' ' . $adminUser['Cognome']);
$adminEmail = htmlspecialchars($adminUser['Email']);
$adminIniziali = strtoupper(substr($adminUser['Nome'], 0, 1) . substr($adminUser['Cognome'], 0, 1));

$sql = "SELECT ID, Nome, Cognome, DataNascita, Username, Email, Ruolo, Stipendio FROM utenti";
$result = $conn->query($sql);

$dipendenti = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $dipendenti[] = $row;
    }
}
$conn->close();

function formatCurrency($amount) {
    return '€ ' . number_format($amount, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <link rel="stylesheet" href="dashstyle.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <title>Dashboard - <?php echo $adminUser['Ruolo']; ?></title>
</head>
<body>
    
    <div class="dashboard-container">
        <div class="header-row">
            <div class="header-text">
                <img src="logo.png" alt="Logo Azienda" class="dashboard-logo">
                <p class="subtitle">Bentornato, gestisci il personale.</p>
            </div>
            
            <div class="profile-widget">
                <div class="profile-bubble" onclick="toggleProfileMenu(event)"><?php echo $adminIniziali; ?></div>
                
                <div class="profile-menu" id="profileMenu">
                    <div class="profile-info">
                        <span class="p-name"><?php echo $adminNomeCompleto; ?></span>
                        <span class="p-email"><?php echo $adminEmail; ?></span>
                        <span class="role-badge role-<?php echo strtolower($adminUser['Ruolo']); ?>">
                            <?php echo $adminUser['Ruolo']; ?>
                        </span>
                    </div>

                    <button class="menu-action" onclick="window.location.href='gestione_reclami.php'">Gestisci reclami</button>
                    <button class="menu-action action-logout" onclick="window.location.href='logout.php'">Esci</button>
                </div>
            </div>
        </div>

        <div class="toolbar">
            <div class="input-group">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="Cerca" onkeyup="filterTable()">
            </div>
            <div class="filter-wrapper">
                <select id="roleFilter" onchange="filterTable()">
                    <option value="all">Tutti i ruoli</option>
                    <option value="Amministratore">Amministratore</option>
                    <option value="Manager">Manager</option>
                    <option value="Impiegato">Impiegato</option>
                </select>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nome & Email</th>
                        <th>Username</th>
                        <th>Data Nascita</th>
                        <th>Ruolo</th>
                        <th>Stipendio</th>
                        <th class="text-right">Opzioni</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($dipendenti)): ?>
                        <tr><td colspan="6" class="table-empty-state">Nessun dipendente trovato</td></tr>
                    <?php else: ?>
                        <?php foreach ($dipendenti as $emp): 
                            $roleClass = strtolower($emp['Ruolo']); 
                            $dateStr = date('d/m/Y', strtotime($emp['DataNascita']));
                            $fullName = htmlspecialchars($emp['Nome'] . ' ' . $emp['Cognome'], ENT_QUOTES);
                            $salaryVal = $emp['Stipendio'];
                        ?>
                        <tr class="data-row" data-role="<?php echo $emp['Ruolo']; ?>">
                            <td>
                                <span class="user-name"><?php echo $fullName; ?></span>
                                <span class="user-email"><?php echo htmlspecialchars($emp['Email']); ?></span>
                            </td>
                            <td>@<?php echo htmlspecialchars($emp['Username']); ?></td>
                            <td><?php echo $dateStr; ?></td>
                            <td><span class="role-badge role-<?php echo $roleClass; ?>"><?php echo $emp['Ruolo']; ?></span></td>
                            <td><span class="salary-text"><?php echo formatCurrency($emp['Stipendio']); ?></span></td>
                            
                            <td class="action-cell">
                                <button class="action-btn" onclick="toggleMenu(event, <?php echo $emp['ID']; ?>)">
                                    <i class="ph ph-dots-three-circle"></i>
                                </button>
                                
                                <div class="action-menu" id="menu-<?php echo $emp['ID']; ?>">
                                    <button class="menu-item" onclick="openRoleModal(<?php echo $emp['ID']; ?>, '<?php echo $fullName; ?>', '<?php echo $emp['Ruolo']; ?>')">
                                        <i class="ph ph-briefcase"></i> Modifica ruolo
                                    </button>
                                    <div class="menu-divider"></div>
                                    <button class="menu-item" onclick="openSalaryModal(<?php echo $emp['ID']; ?>, '<?php echo $fullName; ?>', <?php echo $salaryVal; ?>)">
                                        <i class="ph ph-currency-eur"></i> Modifica stipendio
                                    </button>
                                    <div class="menu-divider"></div>
                                    <button class="menu-item menu-delete" onclick="openDeleteModal(<?php echo $emp['ID']; ?>, '<?php echo $fullName; ?>')">
                                        <i class="ph ph-trash"></i> Elimina
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal-overlay" id="modalRole">
        <div class="modal">
            <div class="modal-icon"><i class="ph ph-briefcase"></i></div>
            <h2>Cambia ruolo</h2>
            <p>Per <strong id="roleModalName">Utente</strong>.</p>
            <input type="hidden" id="roleEditId">
            <div class="form-control">
                <select id="newRoleSelect">
                    <option value="Impiegato">Impiegato</option>
                    <option value="Manager">Manager</option>
                    <option value="Amministratore">Amministratore</option>
                </select>
            </div>
            <button class="btn-primary" onclick="submitRoleUpdate()">Aggiorna ruolo</button>
            <button class="btn-close" onclick="closeModals()">Annulla</button>
        </div>
    </div>

    <div class="modal-overlay" id="modalSalary">
        <div class="modal">
            <div class="modal-icon"><i class="ph ph-currency-eur"></i></div>
            <h2>Nuovo stipendio</h2>
            <p>Per <strong id="salaryModalName">Utente</strong>.</p>
            <input type="hidden" id="salaryEditId">
            <div class="form-control">
                <input type="number" id="newSalaryInput" step="500">
            </div>
            <button class="btn-primary" onclick="submitSalaryUpdate()">Conferma</button>
            <button class="btn-close" onclick="closeModals()">Annulla</button>
        </div>
    </div>

    <div class="modal-overlay" id="modalDelete">
        <div class="modal">
            <div class="modal-icon danger"><i class="ph ph-trash"></i></div>
            <h2>Elimina</h2>
            <p>Sei sicuro di voler eliminare <br><strong id="deleteModalName">Utente</strong>?</p>
            <p class="modal-warning-text">Questa azione è irreversibile.</p>
            
            <input type="hidden" id="deleteId">
            
            <div class="form-control"></div> 
            
            <button class="btn-danger" onclick="submitDeleteUser()">Elimina definitivamente</button>
            <button class="btn-close" onclick="closeModals()">Annulla</button>
        </div>
    </div>

    <div id="toast" class="toast-notification">
        <div class="toast-icon" id="toastIcon"></div>
        <span id="toastMessage">Messaggio</span>
    </div>

    <script src="script.js"></script>
</body>
</html>