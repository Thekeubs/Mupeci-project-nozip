<?php
session_start();
require_once '../includes/functions.php';
require_once '../includes/auth.php';

checkAuth('admin');
if (!isset($_SESSION['admin_verified'])) {
    header('Location: ../login/admin-secret.php');
    exit();
}

$message = '';
$error = '';
$pdo = getDB();

// Traitement des actions administrateur
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_department') {
        $name = trim($_POST['dept_name'] ?? '');
        if (!empty($name)) {
            $stmt = $pdo->prepare('INSERT INTO departments (name, active) VALUES (?, 1)');
            $stmt->execute([$name]);
            $message = 'Département ajouté avec succès';
        }
    } elseif ($action === 'toggle_department') {
        $deptId = (int)$_POST['dept_id'];
        $stmt = $pdo->prepare('UPDATE departments SET active = NOT active WHERE id = ?');
        $stmt->execute([$deptId]);
        $message = 'Statut du département modifié';
    } elseif ($action === 'add_receptionist') {
        $name = trim($_POST['rec_name'] ?? '');
        $email = trim($_POST['rec_email'] ?? '');
        $password = $_POST['rec_password'] ?? '';
        
        if (!empty($name) && !empty($email) && !empty($password)) {
            if (validateEmail($email)) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, user_type, active) VALUES (?, ?, ?, "receptionist", 1)');
                $stmt->execute([$name, $email, $hash]);
                $message = 'Réceptionniste ajouté avec succès';
            } else {
                $error = 'Format d\'email invalide';
            }
        }
    } elseif ($action === 'toggle_receptionist') {
        $recId = (int)$_POST['rec_id'];
        $stmt = $pdo->prepare('UPDATE users SET active = NOT active WHERE id = ? AND user_type = "receptionist"');
        $stmt->execute([$recId]);
        $message = 'Statut du réceptionniste modifié';
    } elseif ($action === 'toggle_admin') {
        $adminId = (int)$_POST['admin_id'];
        $stmt = $pdo->prepare('UPDATE users SET active = NOT active WHERE id = ? AND user_type = "admin"');
        $stmt->execute([$adminId]);
        $message = 'Statut de l\'administrateur modifié';
    }
}

// Récupérer les départements depuis la base
$departments = $pdo->query('SELECT * FROM departments ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les réceptionnistes depuis la base
$receptionists = $pdo->query('SELECT * FROM users WHERE user_type = "receptionist" ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

$waitingCount = count(getVisitorsByStatus('waiting'));
$completedToday = count(array_filter(getTodayVisitors(), function($v) { return $v['status'] === 'completed'; }));
$activeDepartmentsCount = count(getActiveDepartments());
$activeReceptionistsCount = count(array_filter($receptionists, function($r) { return $r['active']; }));

$departmentStats = getDepartmentStats();
$hourlyStats = getHourlyStats();

// Récupérer les administrateurs depuis la base
$admins = $pdo->query('SELECT * FROM users WHERE user_type = "admin" ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - MUPECI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <header class="app-header">
            <div class="header-content">
                <h1>MUPECI - Administration</h1>
                <div class="user-info">
                    <span>Administrateur: <?php echo $_SESSION['user']['name']; ?></span>
                    <a href="../logout.php" class="btn btn-sm btn-secondary">Déconnexion</a>
                </div>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="main-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <h3><?php echo $waitingCount; ?></h3>
                        <p>En Attente</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3><?php echo $completedToday; ?></h3>
                        <p>Visites Terminées</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🏢</div>
                    <div class="stat-content">
                        <h3><?php echo $activeDepartmentsCount; ?></h3>
                        <p>Départements Actifs</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3><?php echo $activeReceptionistsCount; ?></h3>
                        <p>Réceptionnistes Actifs</p>
                    </div>
                </div>
            </div>

            <div class="admin-sections">
                <div class="admin-section">
                    <h3>Gestion des Départements</h3>
                    <!-- Bouton pour ouvrir la modale d'ajout de département -->
                    <button type="button" class="btn btn-green" onclick="openDeptModal()">Ajouter un département</button>
                    
                    <!-- Modale d'ajout de département -->
                    <div id="deptModal" class="modal" style="display:none;">
                      <div class="modal-content">
                        <span class="close" onclick="closeDeptModal()">&times;</span>
                        <h3>Ajouter un département</h3>
                        <form id="addDeptForm" onsubmit="return submitDeptForm(event)">
                          <input type="text" name="dept_name" id="dept_name" placeholder="Nom du département" required style="margin-bottom:10px;width:100%;padding:10px;">
                          <button type="submit" class="btn btn-green">Ajouter</button>
                          <div id="dept-modal-error" style="color:red;margin-top:10px;"></div>
                        </form>
                      </div>
                    </div>
                    
                    <!-- Modale d'édition de département -->
                    <div id="editDeptModal" class="modal" style="display:none;">
                      <div class="modal-content">
                        <span class="close" onclick="closeEditDeptModal()">&times;</span>
                        <h3>Éditer le département</h3>
                        <form id="editDeptForm" onsubmit="return submitEditDeptForm(event)">
                          <input type="hidden" name="edit_dept_id" id="edit_dept_id">
                          <input type="text" name="edit_dept_name" id="edit_dept_name" placeholder="Nom du département" required style="margin-bottom:10px;width:100%;padding:10px;">
                          <button type="submit" class="btn btn-green">Enregistrer</button>
                          <div id="edit-dept-modal-error" style="color:red;margin-top:10px;"></div>
                        </form>
                      </div>
                    </div>
                    
                    <table class="admin-table" id="departments-table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Statut</th>
                                <th>Activation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departments as $dept): ?>
                                <tr data-id="<?php echo $dept['id']; ?>">
                                    <td><?php echo $dept['name']; ?></td>
                                    <td>
                                        <span class="status status-<?php echo $dept['active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $dept['active'] ? 'Actif' : 'Inactif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm <?php echo $dept['active'] ? 'btn-red' : 'btn-green'; ?>" onclick="toggleDepartment(<?php echo $dept['id']; ?>)">
                                            <?php echo $dept['active'] ? 'Désactiver' : 'Activer'; ?>
                                        </button>
                                    </td>
                                    <td>
                                        <button class="icon-btn" onclick="openEditDeptModal(<?php echo $dept['id']; ?>, '<?php echo htmlspecialchars($dept['name'], ENT_QUOTES); ?>')" title="Éditer">
                                          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M3 17.25V14.75L13.81 3.94C14.2 3.55 14.83 3.55 15.22 3.94L16.06 4.78C16.45 5.17 16.45 5.8 16.06 6.19L5.25 17H3Z" stroke="#ff9800" stroke-width="2" fill="none"/>
                                          </svg>
                                        </button>
                                        <button class="icon-btn" onclick="deleteDept(<?php echo $dept['id']; ?>)" title="Supprimer">
                                          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <line x1="5" y1="5" x2="15" y2="15" stroke="#e53935" stroke-width="2"/>
                                            <line x1="15" y1="5" x2="5" y2="15" stroke="#e53935" stroke-width="2"/>
                                          </svg>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="admin-section">
                    <h3>Gestion des Réceptionnistes</h3>
                    <!-- Bouton pour ouvrir la modale d'ajout de réceptionniste -->
                    <button type="button" class="btn btn-green" onclick="openRecModal()">Ajouter un réceptionniste</button>
                    
                    <!-- Modale d'ajout de réceptionniste -->
                    <div id="recModal" class="modal" style="display:none;">
                      <div class="modal-content">
                        <span class="close" onclick="closeRecModal()">&times;</span>
                        <h3>Ajouter un réceptionniste</h3>
                        <form id="addRecForm" onsubmit="return submitRecForm(event)">
                          <input type="text" name="rec_name" id="rec_name" placeholder="Nom complet" required style="margin-bottom:10px;width:100%;padding:10px;">
                          <input type="email" name="rec_email" id="rec_email" placeholder="Email" required style="margin-bottom:10px;width:100%;padding:10px;">
                          <input type="password" name="rec_password" id="rec_password" placeholder="Mot de passe" required style="margin-bottom:10px;width:100%;padding:10px;">
                          <button type="submit" class="btn btn-green">Ajouter</button>
                          <div id="rec-modal-error" style="color:red;margin-top:10px;"></div>
                        </form>
                      </div>
                    </div>
                    
                    <!-- Modale d'édition de réceptionniste -->
                    <div id="editRecModal" class="modal" style="display:none;">
                      <div class="modal-content">
                        <span class="close" onclick="closeEditRecModal()">&times;</span>
                        <h3>Éditer le réceptionniste</h3>
                        <form id="editRecForm" onsubmit="return submitEditRecForm(event)">
                          <input type="hidden" name="edit_rec_id" id="edit_rec_id">
                          <input type="text" name="edit_rec_name" id="edit_rec_name" placeholder="Nom complet" required style="margin-bottom:10px;width:100%;padding:10px;">
                          <input type="email" name="edit_rec_email" id="edit_rec_email" placeholder="Email" required style="margin-bottom:10px;width:100%;padding:10px;">
                          <input type="password" name="edit_rec_password" id="edit_rec_password" placeholder="Nouveau mot de passe (laisser vide pour ne pas changer)" style="margin-bottom:10px;width:100%;padding:10px;">
                          <button type="submit" class="btn btn-green">Enregistrer</button>
                          <div id="edit-rec-modal-error" style="color:red;margin-top:10px;"></div>
                        </form>
                      </div>
                    </div>
                    
                    <table class="admin-table" id="receptionists-table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Statut</th>
                                <th>Activation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($receptionists as $rec): ?>
                                <tr data-id="<?php echo $rec['id']; ?>">
                                    <td><?php echo $rec['name']; ?></td>
                                    <td><?php echo $rec['email']; ?></td>
                                    <td>
                                        <span class="status status-<?php echo $rec['active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $rec['active'] ? 'Actif' : 'Inactif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm <?php echo $rec['active'] ? 'btn-red' : 'btn-green'; ?>" onclick="toggleReceptionist(<?php echo $rec['id']; ?>)">
                                            <?php echo $rec['active'] ? 'Désactiver' : 'Activer'; ?>
                                        </button>
                                    </td>
                                    <td>
                                        <button class="icon-btn" onclick="openEditRecModal(<?php echo $rec['id']; ?>, '<?php echo htmlspecialchars($rec['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($rec['email'], ENT_QUOTES); ?>')" title="Éditer">
                                          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M3 17.25V14.75L13.81 3.94C14.2 3.55 14.83 3.55 15.22 3.94L16.06 4.78C16.45 5.17 16.45 5.8 16.06 6.19L5.25 17H3Z" stroke="#ff9800" stroke-width="2" fill="none"/>
                                          </svg>
                                        </button>
                                        <button class="icon-btn" onclick="deleteRec(<?php echo $rec['id']; ?>)" title="Supprimer">
                                          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <line x1="5" y1="5" x2="15" y2="15" stroke="#e53935" stroke-width="2"/>
                                            <line x1="15" y1="5" x2="5" y2="15" stroke="#e53935" stroke-width="2"/>
                                          </svg>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="admin-section">
                    <h3>Gestion des Administrateurs</h3>
                    <button type="button" class="btn btn-purple" onclick="openAdminModal()">Ajouter un administrateur</button>
                    <!-- Modale d'ajout d'administrateur -->
                    <div id="adminModal" class="modal" style="display:none;">
                      <div class="modal-content">
                        <span class="close" onclick="closeAdminModal()">&times;</span>
                        <h3>Ajouter un administrateur</h3>
                        <form id="addAdminForm" onsubmit="return submitAdminForm(event)">
                          <input type="text" name="admin_name" id="admin_name" placeholder="Nom complet" required style="margin-bottom:10px;width:100%;padding:10px;">
                          <input type="email" name="admin_email" id="admin_email" placeholder="Email" required style="margin-bottom:10px;width:100%;padding:10px;">
                          <input type="password" name="admin_password" id="admin_password" placeholder="Mot de passe" required style="margin-bottom:10px;width:100%;padding:10px;">
                          <input type="text" name="admin_secret" id="admin_secret" placeholder="Code secret" required style="margin-bottom:10px;width:100%;padding:10px;">
                          <button type="submit" class="btn btn-purple">Ajouter</button>
                          <div id="admin-modal-error" style="color:red;margin-top:10px;"></div>
                        </form>
                      </div>
                    </div>
                    <!-- Modale d'édition d'administrateur -->
                    <div id="editAdminModal" class="modal" style="display:none;">
                      <div class="modal-content">
                        <span class="close" onclick="closeEditAdminModal()">&times;</span>
                        <h3>Éditer l'administrateur</h3>
                        <form id="editAdminForm" onsubmit="return submitEditAdminForm(event)">
                          <input type="hidden" name="edit_admin_id" id="edit_admin_id">
                          <input type="text" name="edit_admin_name" id="edit_admin_name" placeholder="Nom complet" required style="margin-bottom:10px;width:100%;padding:10px;">
                          <input type="email" name="edit_admin_email" id="edit_admin_email" placeholder="Email" required style="margin-bottom:10px;width:100%;padding:10px;">
                          <input type="password" name="edit_admin_password" id="edit_admin_password" placeholder="Nouveau mot de passe (laisser vide pour ne pas changer)" style="margin-bottom:10px;width:100%;padding:10px;">
                          <input type="text" name="edit_admin_secret" id="edit_admin_secret" placeholder="Code secret" required style="margin-bottom:10px;width:100%;padding:10px;">
                          <button type="submit" class="btn btn-purple">Enregistrer</button>
                          <div id="edit-admin-modal-error" style="color:red;margin-top:10px;"></div>
                        </form>
                      </div>
                    </div>
                    <table class="admin-table" id="admins-table">
                      <thead>
                        <tr>
                          <th>Nom</th>
                          <th>Email</th>
                          <th>Statut</th>
                          <th>Activation</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($admins as $admin): ?>
                        <tr data-id="<?php echo $admin['id']; ?>">
                          <td><?php echo $admin['name']; ?></td>
                          <td><?php echo $admin['email']; ?></td>
                          <td>
                            <span class="status status-<?php echo $admin['active'] ? 'active' : 'inactive'; ?>">
                              <?php echo $admin['active'] ? 'Actif' : 'Inactif'; ?>
                            </span>
                          </td>
                          <td>
                            <button type="button" class="btn btn-sm <?php echo $admin['active'] ? 'btn-red' : 'btn-green'; ?>" onclick="toggleAdmin(<?php echo $admin['id']; ?>)">
                              <?php echo $admin['active'] ? 'Désactiver' : 'Activer'; ?>
                            </button>
                          </td>
                          <td>
                            <button class="icon-btn" onclick="openEditAdminModal(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($admin['email'], ENT_QUOTES); ?>')" title="Éditer">
                              <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 17.25V14.75L13.81 3.94C14.2 3.55 14.83 3.55 15.22 3.94L16.06 4.78C16.45 5.17 16.45 5.8 16.06 6.19L5.25 17H3Z" stroke="#ff9800" stroke-width="2" fill="none"/>
                              </svg>
                            </button>
                            <button class="icon-btn" onclick="deleteAdmin(<?php echo $admin['id']; ?>)" title="Supprimer">
                              <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <line x1="5" y1="5" x2="15" y2="15" stroke="#e53935" stroke-width="2"/>
                                <line x1="15" y1="5" x2="5" y2="15" stroke="#e53935" stroke-width="2"/>
                              </svg>
                            </button>
                          </td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                </div>
            </div>

            <div class="charts-grid">
                <div class="chart-container">
                    <h3>Classement des Départements</h3>
                    <div class="department-ranking">
                        <?php
                        $bgGradients = [
                            '#d1fae5', '#a7f3d0', '#6ee7b7', '#34d399', '#10b981', '#059669', '#047857',
                        ];
                        $total = max(1, count($departmentStats));
                        ?>
                        <?php foreach ($departmentStats as $index => $dept): ?>
                            <?php
                            $deptRow = null;
                            foreach ($departments as $d) {
                                if ($d['name'] === $dept['name']) {
                                    $deptRow = $d;
                                    break;
                                }
                            }
                            $isInactive = $deptRow && !$deptRow['active'];
                            $bgIdx = (int) round(($index / max(1, $total-1)) * (count($bgGradients)-1));
                            $bgColor = $bgGradients[$bgIdx];
                            $medal = '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">'
                                .'<circle cx="14" cy="14" r="12" fill="'.$bgColor.'" stroke="'.$bgColor.'" stroke-width="2"/>'
                                .'<text x="14" y="19" text-anchor="middle" font-size="13" font-weight="bold" fill="#228B22">'.($index+1).'</text>'
                                .'</svg>';
                            ?>
                            <div class="ranking-item<?php echo $isInactive ? ' inactive-dept' : ''; ?>" style="background: <?php echo $bgColor; ?>; border-radius: 8px; padding: 6px 12px;">
                                <div class="rank-medal" style="margin-right:10px;display:flex;align-items:center;justify-content:center;min-width:28px;">
                                    <?php echo $medal; ?>
                                </div>
                                <div class="dept-info">
                                    <span class="dept-name"><?php echo $dept['name']; ?><?php if ($isInactive): ?><span class="inactive-label"> (inactif)</span><?php endif; ?></span>
                                    <span class="dept-count" style="color:#ff9800;font-weight:bold;"><?php echo $dept['count']; ?> visiteurs</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="chart-container">
                    <h3>Affluence par Heure</h3>
                    <canvas id="hourlyChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Graphique d'affluence par heure
        const ctx = document.getElementById('hourlyChart').getContext('2d');
        const hourlyData = <?php echo json_encode(array_values($hourlyStats)); ?>;
        const hours = ['8h', '9h', '10h', '11h', '12h', '13h', '14h', '15h', '16h', '17h'];
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: hours,
                datasets: [{
                    label: 'Nombre de visiteurs',
                    data: hourlyData,
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>

    <style>
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0; top: 0; width: 100vw; height: 100vh;
      background: rgba(0,0,0,0.3);
      justify-content: center;
      align-items: center;
    }
    .modal-content {
      background: #fff;
      padding: 30px 20px;
      border-radius: 12px;
      max-width: 400px;
      margin: 100px auto;
      position: relative;
      box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    }
    .close {
      position: absolute;
      right: 18px;
      top: 10px;
      font-size: 28px;
      cursor: pointer;
    }
    .icon-btn {
      background: none;
      border: none;
      padding: 4px;
      cursor: pointer;
      vertical-align: middle;
    }
    .icon-btn svg:hover path,
    .icon-btn svg:hover line {
      filter: brightness(0.8);
    }
    .inactive-dept .dept-name {
      color: #aaa;
      font-style: italic;
    }
    .inactive-label {
      color: #e53935;
      font-size: 0.95em;
      margin-left: 2px;
    }
    .rank-medal {
      margin-right: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      min-width: 28px;
    }
    .ranking-item {
      display: flex;
      align-items: center;
      margin-bottom: 8px;
    }
    .btn-purple {
      background: #8e24aa !important;
      color: #fff !important;
      border: none;
    }
    .btn-purple:hover {
      background: #6d1b7b !important;
    }
    </style>
    
    <script>
    // ===== DÉPARTEMENTS =====
    function openDeptModal() {
      document.getElementById('deptModal').style.display = 'flex';
      document.getElementById('dept_name').value = '';
      document.getElementById('dept-modal-error').textContent = '';
    }
    function closeDeptModal() {
      document.getElementById('deptModal').style.display = 'none';
    }
    function submitDeptForm(e) {
      e.preventDefault();
      var name = document.getElementById('dept_name').value.trim();
      var errorDiv = document.getElementById('dept-modal-error');
      errorDiv.textContent = '';
      if (!name) {
        errorDiv.textContent = 'Le nom est requis';
        return false;
      }
      var formData = new FormData();
      formData.append('dept_name', name);
      fetch('add_department.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          var table = document.getElementById('departments-table').querySelector('tbody');
          var tr = document.createElement('tr');
          tr.setAttribute('data-id', data.department.id);
          tr.innerHTML = `<td>${data.department.name}</td>
            <td><span class="status status-active">Actif</span></td>
            <td><button type="button" class="btn btn-sm btn-red" onclick="toggleDepartment(${data.department.id})">Désactiver</button></td>
            <td>
              <button class="icon-btn" onclick="openEditDeptModal(${data.department.id}, '${data.department.name.replace(/'/g, "\\'")}')">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M3 17.25V14.75L13.81 3.94C14.2 3.55 14.83 3.55 15.22 3.94L16.06 4.78C16.45 5.17 16.45 5.8 16.06 6.19L5.25 17H3Z" stroke="#ff9800" stroke-width="2" fill="none"/>
                </svg>
              </button>
              <button class="icon-btn" onclick="deleteDept(${data.department.id})">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <line x1="5" y1="5" x2="15" y2="15" stroke="#e53935" stroke-width="2"/>
                  <line x1="15" y1="5" x2="5" y2="15" stroke="#e53935" stroke-width="2"/>
                </svg>
              </button>
            </td>`;
          table.appendChild(tr);
          closeDeptModal();
        } else {
          errorDiv.textContent = data.message || 'Erreur lors de l\'ajout';
        }
      })
      .catch(() => {
        errorDiv.textContent = 'Erreur lors de l\'ajout';
      });
      return false;
    }

    function toggleDepartment(id) {
      var formData = new FormData();
      formData.append('dept_id', id);
      fetch('toggle_department.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          location.reload(); // Recharger la page pour mettre à jour l'affichage
        } else {
          alert(data.message || 'Erreur lors du changement de statut');
        }
      })
      .catch(() => {
        alert('Erreur lors du changement de statut');
      });
    }

    function openEditDeptModal(id, name) {
      document.getElementById('editDeptModal').style.display = 'flex';
      document.getElementById('edit_dept_id').value = id;
      document.getElementById('edit_dept_name').value = name;
      document.getElementById('edit-dept-modal-error').textContent = '';
    }
    function closeEditDeptModal() {
      document.getElementById('editDeptModal').style.display = 'none';
    }
    function submitEditDeptForm(e) {
      e.preventDefault();
      var id = document.getElementById('edit_dept_id').value;
      var name = document.getElementById('edit_dept_name').value.trim();
      var errorDiv = document.getElementById('edit-dept-modal-error');
      errorDiv.textContent = '';
      if (!name) {
        errorDiv.textContent = 'Le nom est requis';
        return false;
      }
      var formData = new FormData();
      formData.append('dept_id', id);
      formData.append('dept_name', name);
      fetch('edit_department.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          var tr = document.querySelector(`#departments-table tr[data-id='${id}']`);
          if (tr) {
            tr.querySelector('td').textContent = name;
          }
          closeEditDeptModal();
        } else {
          errorDiv.textContent = data.message || 'Erreur lors de la modification';
        }
      })
      .catch(() => {
        errorDiv.textContent = 'Erreur lors de la modification';
      });
      return false;
    }
    function deleteDept(id) {
      if (!confirm('Supprimer ce département ?')) return;
      var formData = new FormData();
      formData.append('dept_id', id);
      fetch('delete_department.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          var tr = document.querySelector(`#departments-table tr[data-id='${id}']`);
          if (tr) tr.remove();
        } else {
          alert(data.message || 'Erreur lors de la suppression');
        }
      })
      .catch((err) => {
        alert('Erreur lors de la suppression');
      });
    }

    // ===== RÉCEPTIONNISTES =====
    function openRecModal() {
      document.getElementById('recModal').style.display = 'flex';
      document.getElementById('rec_name').value = '';
      document.getElementById('rec_email').value = '';
      document.getElementById('rec_password').value = '';
      document.getElementById('rec-modal-error').textContent = '';
    }
    function closeRecModal() {
      document.getElementById('recModal').style.display = 'none';
    }
    function submitRecForm(e) {
      e.preventDefault();
      var name = document.getElementById('rec_name').value.trim();
      var email = document.getElementById('rec_email').value.trim();
      var password = document.getElementById('rec_password').value;
      var errorDiv = document.getElementById('rec-modal-error');
      errorDiv.textContent = '';
      if (!name || !email || !password) {
        errorDiv.textContent = 'Tous les champs sont requis';
        return false;
      }
      var formData = new FormData();
      formData.append('rec_name', name);
      formData.append('rec_email', email);
      formData.append('rec_password', password);
      fetch('add_receptionist.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          var table = document.getElementById('receptionists-table').querySelector('tbody');
          var tr = document.createElement('tr');
          tr.setAttribute('data-id', data.receptionist.id);
          tr.innerHTML = `<td>${data.receptionist.name}</td>
            <td>${data.receptionist.email}</td>
            <td><span class="status status-active">Actif</span></td>
            <td><button type="button" class="btn btn-sm btn-red" onclick="toggleReceptionist(${data.receptionist.id})">Désactiver</button></td>
            <td>
              <button class="icon-btn" onclick="openEditRecModal(${data.receptionist.id}, '${data.receptionist.name.replace(/'/g, "\\'")}', '${data.receptionist.email.replace(/'/g, "\\'")}')">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M3 17.25V14.75L13.81 3.94C14.2 3.55 14.83 3.55 15.22 3.94L16.06 4.78C16.45 5.17 16.45 5.8 16.06 6.19L5.25 17H3Z" stroke="#ff9800" stroke-width="2" fill="none"/>
                </svg>
              </button>
              <button class="icon-btn" onclick="deleteRec(${data.receptionist.id})">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <line x1="5" y1="5" x2="15" y2="15" stroke="#e53935" stroke-width="2"/>
                  <line x1="15" y1="5" x2="5" y2="15" stroke="#e53935" stroke-width="2"/>
                </svg>
              </button>
            </td>`;
          table.appendChild(tr);
          closeRecModal();
        } else {
          errorDiv.textContent = data.message || 'Erreur lors de l\'ajout';
        }
      })
      .catch(() => {
        errorDiv.textContent = 'Erreur lors de l\'ajout';
      });
      return false;
    }

    function toggleReceptionist(id) {
      var formData = new FormData();
      formData.append('rec_id', id);
      fetch('toggle_receptionist.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          location.reload(); // Recharger la page pour mettre à jour l'affichage
        } else {
          alert(data.message || 'Erreur lors du changement de statut');
        }
      })
      .catch(() => {
        alert('Erreur lors du changement de statut');
      });
    }

    function openEditRecModal(id, name, email) {
      document.getElementById('editRecModal').style.display = 'flex';
      document.getElementById('edit_rec_id').value = id;
      document.getElementById('edit_rec_name').value = name;
      document.getElementById('edit_rec_email').value = email;
      document.getElementById('edit_rec_password').value = '';
      document.getElementById('edit-rec-modal-error').textContent = '';
    }
    function closeEditRecModal() {
      document.getElementById('editRecModal').style.display = 'none';
    }
    function submitEditRecForm(e) {
      e.preventDefault();
      var id = document.getElementById('edit_rec_id').value;
      var name = document.getElementById('edit_rec_name').value.trim();
      var email = document.getElementById('edit_rec_email').value.trim();
      var password = document.getElementById('edit_rec_password').value;
      var errorDiv = document.getElementById('edit-rec-modal-error');
      errorDiv.textContent = '';
      if (!name || !email) {
        errorDiv.textContent = 'Nom et email requis';
        return false;
      }
      var formData = new FormData();
      formData.append('rec_id', id);
      formData.append('rec_name', name);
      formData.append('rec_email', email);
      formData.append('rec_password', password);
      fetch('edit_receptionist.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          var tr = document.querySelector(`#receptionists-table tr[data-id='${id}']`);
          if (tr) {
            tr.children[0].textContent = name;
            tr.children[1].textContent = email;
          }
          closeEditRecModal();
        } else {
          errorDiv.textContent = data.message || 'Erreur lors de la modification';
        }
      })
      .catch(() => {
        errorDiv.textContent = 'Erreur lors de la modification';
      });
      return false;
    }
    function deleteRec(id) {
      if (!confirm('Supprimer ce réceptionniste ?')) return;
      var formData = new FormData();
      formData.append('rec_id', id);
      fetch('delete_receptionist.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          var tr = document.querySelector(`#receptionists-table tr[data-id='${id}']`);
          if (tr) tr.remove();
        } else {
          alert(data.message || 'Erreur lors de la suppression');
        }
      })
      .catch(() => {
        alert('Erreur lors de la suppression');
      });
    }

    // ===== ADMINISTRATEURS =====
    function openAdminModal() {
      document.getElementById('adminModal').style.display = 'flex';
      document.getElementById('admin_name').value = '';
      document.getElementById('admin_email').value = '';
      document.getElementById('admin_password').value = '';
      document.getElementById('admin_secret').value = '';
      document.getElementById('admin-modal-error').textContent = '';
    }
    function closeAdminModal() {
      document.getElementById('adminModal').style.display = 'none';
    }
    function submitAdminForm(e) {
      e.preventDefault();
      var name = document.getElementById('admin_name').value.trim();
      var email = document.getElementById('admin_email').value.trim();
      var password = document.getElementById('admin_password').value;
      var secret = document.getElementById('admin_secret').value.trim();
      var errorDiv = document.getElementById('admin-modal-error');
      errorDiv.textContent = '';
      if (!name || !email || !password || !secret) {
        errorDiv.textContent = 'Tous les champs sont requis';
        return false;
      }
      var formData = new FormData();
      formData.append('admin_name', name);
      formData.append('admin_email', email);
      formData.append('admin_password', password);
      formData.append('admin_secret', secret);
      fetch('add_admin.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          var table = document.getElementById('admins-table').querySelector('tbody');
          var tr = document.createElement('tr');
          tr.setAttribute('data-id', data.admin.id);
          tr.innerHTML = `<td>${data.admin.name}</td>
            <td>${data.admin.email}</td>
            <td><span class="status status-active">Actif</span></td>
            <td><button type="button" class="btn btn-sm btn-red" onclick="toggleAdmin(${data.admin.id})">Désactiver</button></td>
            <td>
              <button class="icon-btn" onclick="openEditAdminModal(${data.admin.id}, '${data.admin.name.replace(/'/g, "\\'")}', '${data.admin.email.replace(/'/g, "\\'")}')">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M3 17.25V14.75L13.81 3.94C14.2 3.55 14.83 3.55 15.22 3.94L16.06 4.78C16.45 5.17 16.45 5.8 16.06 6.19L5.25 17H3Z" stroke="#ff9800" stroke-width="2" fill="none"/>
                </svg>
              </button>
              <button class="icon-btn" onclick="deleteAdmin(${data.admin.id})">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <line x1="5" y1="5" x2="15" y2="15" stroke="#e53935" stroke-width="2"/>
                  <line x1="15" y1="5" x2="5" y2="15" stroke="#e53935" stroke-width="2"/>
                </svg>
              </button>
            </td>`;
          table.appendChild(tr);
          closeAdminModal();
        } else {
          errorDiv.textContent = data.message || 'Erreur lors de l\'ajout';
        }
      })
      .catch(() => {
        errorDiv.textContent = 'Erreur lors de l\'ajout';
      });
      return false;
    }

    function toggleAdmin(id) {
      var formData = new FormData();
      formData.append('admin_id', id);
      fetch('toggle_admin.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          location.reload(); // Recharger la page pour mettre à jour l'affichage
        } else {
          alert(data.message || 'Erreur lors du changement de statut');
        }
      })
      .catch(() => {
        alert('Erreur lors du changement de statut');
      });
    }

    function openEditAdminModal(id, name, email) {
      document.getElementById('editAdminModal').style.display = 'flex';
      document.getElementById('edit_admin_id').value = id;
      document.getElementById('edit_admin_name').value = name;
      document.getElementById('edit_admin_email').value = email;
      document.getElementById('edit_admin_password').value = '';
      document.getElementById('edit_admin_secret').value = '';
      document.getElementById('edit-admin-modal-error').textContent = '';
    }
    function closeEditAdminModal() {
      document.getElementById('editAdminModal').style.display = 'none';
    }
    function submitEditAdminForm(e) {
      e.preventDefault();
      var id = document.getElementById('edit_admin_id').value;
      var name = document.getElementById('edit_admin_name').value.trim();
      var email = document.getElementById('edit_admin_email').value.trim();
      var password = document.getElementById('edit_admin_password').value;
      var secret = document.getElementById('edit_admin_secret').value.trim();
      var errorDiv = document.getElementById('edit-admin-modal-error');
      errorDiv.textContent = '';
      if (!name || !email || !secret) {
        errorDiv.textContent = 'Nom, email et code secret requis';
        return false;
      }
      var formData = new FormData();
      formData.append('admin_id', id);
      formData.append('admin_name', name);
      formData.append('admin_email', email);
      formData.append('admin_password', password);
      formData.append('admin_secret', secret);
      fetch('edit_admin.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          var tr = document.querySelector(`#admins-table tr[data-id='${id}']`);
          if (tr) {
            tr.children[0].textContent = name;
            tr.children[1].textContent = email;
          }
          closeEditAdminModal();
        } else {
          errorDiv.textContent = data.message || 'Erreur lors de la modification';
        }
      })
      .catch(() => {
        errorDiv.textContent = 'Erreur lors de la modification';
      });
      return false;
    }
    function deleteAdmin(id) {
      if (!confirm('Supprimer cet administrateur ?')) return;
      var formData = new FormData();
      formData.append('admin_id', id);
      fetch('delete_admin.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          var tr = document.querySelector(`#admins-table tr[data-id='${id}']`);
          if (tr) tr.remove();
        } else {
          alert(data.message || 'Erreur lors de la suppression');
        }
      })
      .catch(() => {
        alert('Erreur lors de la suppression');
      });
    }
    </script>
</body>
</html>