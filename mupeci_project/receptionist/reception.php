<?php
session_start();
require_once '../includes/functions.php';
require_once '../includes/auth.php';

checkAuth('receptionist');
initializeData();

$message = '';
$error = '';

// Traitement des actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_visitor') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $idNumber = trim($_POST['id_number'] ?? '');
        $departmentName = $_POST['department'] ?? '';
        $purpose = $_POST['purpose'] ?? 'visite';
        $pdo = getDB();
        // Validation
        if (empty($name) || empty($phone) || empty($idNumber) || empty($departmentName)) {
            $error = 'Veuillez remplir tous les champs obligatoires';
        } elseif (!validateCameroonPhone($phone)) {
            $error = 'Format de téléphone invalide (+237XXXXXXXXX)';
        } elseif (!empty($email) && !validateEmail($email)) {
            $error = 'Format d\'email invalide';
        } elseif (!preg_match('/^\d{10}$/', $idNumber) && !preg_match('/^[A-Za-z]{2}[A-Za-z0-9]{18}$/', $idNumber)) {
            $error = 'Format du numéro de pièce invalide. CNI = 10 chiffres, Récépissé = 2 lettres + 18 caractères.';
        } else {
            // Récupérer l'id du département à partir de son nom
            $stmt = $pdo->prepare('SELECT id FROM departments WHERE name = ? LIMIT 1');
            $stmt->execute([$departmentName]);
            $dept = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$dept) {
                $error = 'Département invalide';
            } else {
                $departmentId = $dept['id'];
                $stmt = $pdo->prepare('INSERT INTO visitors (name, phone, email, id_number, department_id, purpose, status, checked_in_at, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)');
                $createdBy = $_SESSION['user']['id'] ?? null;
                $stmt->execute([$name, $phone, $email, $idNumber, $departmentId, $purpose, 'waiting', $createdBy]);
                $message = 'Visiteur enregistré avec succès';
            }
        }
    } elseif ($action === 'start_visit') {
        $visitorId = (int)$_POST['visitor_id'];
        $pdo = getDB();
        $stmt = $pdo->prepare('UPDATE visitors SET status = ?, started_at = NOW() WHERE id = ?');
        $stmt->execute(['in-progress', $visitorId]);
        $message = 'Visite démarrée';
    } elseif ($action === 'complete_visit') {
        $visitorId = (int)$_POST['visitor_id'];
        $pdo = getDB();
        $stmt = $pdo->prepare('UPDATE visitors SET status = ?, completed_at = NOW() WHERE id = ?');
        $stmt->execute(['completed', $visitorId]);
        $message = 'Visite terminée';
    } elseif ($action === 'delete_visitor') {
        $visitorId = (int)$_POST['visitor_id'];
        $pdo = getDB();
        $stmt = $pdo->prepare('DELETE FROM visitors WHERE id = ?');
        $stmt->execute([$visitorId]);
        $message = 'Visiteur supprimé avec succès';
    } elseif ($action === 'edit_visitor') {
        $visitorId = (int)$_POST['visitor_id'];
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $idNumber = trim($_POST['id_number'] ?? '');
        $departmentName = $_POST['department'] ?? '';
        $purpose = $_POST['purpose'] ?? 'visite';
        $pdo = getDB();
        // Récupérer l'id du département à partir de son nom
        $stmt = $pdo->prepare('SELECT id FROM departments WHERE name = ? LIMIT 1');
        $stmt->execute([$departmentName]);
        $dept = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$dept) {
            $error = 'Département invalide';
        } else {
            $departmentId = $dept['id'];
            $stmt = $pdo->prepare('UPDATE visitors SET name=?, phone=?, email=?, id_number=?, department_id=?, purpose=? WHERE id=?');
            $stmt->execute([$name, $phone, $email, $idNumber, $departmentId, $purpose, $visitorId]);
            $message = 'Visiteur modifié avec succès';
        }
    }
}

$activeDepartments = getActiveDepartments();
$waitingVisitors = getVisitorsByStatus('waiting');
$inProgressVisitors = getVisitorsByStatus('in-progress');
$completedVisitors = array_filter(getTodayVisitors(), function($v) { return $v['status'] === 'completed'; });
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réception - MUPECI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="app-header">
            <div class="header-content">
                <h1>MUPECI - Réception</h1>
                <div class="user-info">
                    <span>Bonjour, <?php echo $_SESSION['user']['name']; ?></span>
                    <a href="../receptionist/dashboard.php" class="btn btn-sm btn-green">Tableau de Bord</a>
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
            <!-- Choix du type de visiteur -->
            <div id="visitor-choice" class="section">
                <h2>Enregistrement des Visiteurs</h2>
                <button type="button" class="btn btn-primary" onclick="showNewVisitorForm()">Nouveau visiteur</button>
                <button type="button" class="btn btn-secondary" onclick="showOldVisitorForm()">Ancien visiteur</button>
            </div>

            <!-- Formulaire pour un nouveau visiteur (identique à l'existant, masqué par défaut) -->
            <div id="new-visitor-form" class="section" style="display:none;">
                <form method="POST" class="visitor-form">
                    <input type="hidden" name="action" value="add_visitor">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Nom Complet *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Téléphone *</label>
                            <input type="tel" id="phone" name="phone" placeholder="237XXXXXXXXX" required>
                            <span id="phone_error" style="color:red"></span>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email">
                            <span id="email_error" style="color:red"></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="id_number">Numéro Pièce d'Identité *</label>
                            <input type="text" id="id_number" name="id_number" required>
                            <span id="id_error" style="color:red"></span>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="department">Département *</label>
                            <select id="department" name="department" required>
                                <option value="">Sélectionner un département</option>
                                <?php foreach ($activeDepartments as $dept): ?>
                                    <option value="<?php echo $dept['name']; ?>"><?php echo $dept['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="purpose">Objet de la visite</label>
                            <select id="purpose" name="purpose">
                                <option value="visite">Visite</option>
                                <option value="rendez-vous">Rendez-vous</option>
                                <option value="autres">Autres</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Enregistrer le Visiteur</button>
                </form>
            </div>

            <!-- Formulaire pour un ancien visiteur (masqué par défaut) -->
            <div id="old-visitor-form" class="section" style="display:none;">
                <button type="button" class="btn btn-sm btn-secondary" style="margin-bottom:10px;" onclick="backToVisitorChoice()">← Retour</button>
                <form id="old-visitor-search-form" onsubmit="return searchOldVisitor(event)">
                    <div class="form-group">
                        <label for="old_id_number">Numéro Pièce d'Identité</label>
                        <input type="text" id="old_id_number" name="old_id_number" required>
                        <button type="submit" class="btn btn-primary">Rechercher</button>
                    </div>
                    <div id="old-visitor-error" style="color:red;"></div>
                </form>
                <form method="POST" class="visitor-form" id="old-visitor-data-form" style="display:none;">
                    <input type="hidden" name="action" value="add_visitor">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="old_name">Nom Complet *</label>
                            <input type="text" id="old_name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="old_phone">Téléphone *</label>
                            <input type="tel" id="old_phone" name="phone" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="old_email">Email</label>
                            <input type="email" id="old_email" name="email">
                        </div>
                        <div class="form-group">
                            <label for="old_id_number">Numéro Pièce d'Identité *</label>
                            <input type="text" id="old_id_number_field" name="id_number" required readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="old_department">Département *</label>
                            <select id="old_department" name="department" required>
                                <option value="">Sélectionner un département</option>
                                <?php foreach ($activeDepartments as $dept): ?>
                                    <option value="<?php echo $dept['name']; ?>"><?php echo $dept['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="old_purpose">Objet de la visite</label>
                            <select id="old_purpose" name="purpose">
                                <option value="visite">Visite</option>
                                <option value="rendez-vous">Rendez-vous</option>
                                <option value="autres">Autres</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Enregistrer le Visiteur</button>
                </form>
            </div>

            <div class="section">
                <h2>File d'Attente Active</h2>
                <input type="text" id="queueSearch" placeholder="Rechercher dans la file d'attente..." style="margin-bottom:10px; width:100%; padding:8px; border-radius:6px; border:1px solid #ccc;">
                <div class="queue-tables">
                    <?php if (count($waitingVisitors) > 0 || count($inProgressVisitors) > 0): ?>
                        <table class="visitors-table">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Département</th>
                                    <th>Objet</th>
                                    <th>Heure d'arrivée</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_merge($waitingVisitors, $inProgressVisitors) as $visitor): ?>
                                    <tr>
                                        <td><?php echo $visitor['name']; ?></td>
                                        <?php
                                        // Trouver le nom du département à partir de l'id
                                        $deptName = '';
                                        foreach ($activeDepartments as $dept) {
                                            if ($dept['id'] == $visitor['department_id']) {
                                                $deptName = $dept['name'];
                                                break;
                                            }
                                        }
                                        ?>
                                        <td><?php echo $deptName; ?></td>
                                        <td><?php echo ucfirst($visitor['purpose']); ?></td>
                                        <td><?php echo date('H:i', strtotime($visitor['checked_in_at'])); ?></td>
                                        <td>
                                            <span class="status status-<?php echo $visitor['status']; ?>">
                                                <?php echo $visitor['status'] === 'waiting' ? 'En attente' : 'En cours'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($visitor['status'] === 'waiting'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="start_visit">
                                                    <input type="hidden" name="visitor_id" value="<?php echo $visitor['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-primary">Démarrer</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="complete_visit">
                                                    <input type="hidden" name="visitor_id" value="<?php echo $visitor['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success">Terminer</button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer ce visiteur ?');">
                                                <input type="hidden" name="action" value="delete_visitor">
                                                <input type="hidden" name="visitor_id" value="<?php echo $visitor['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-secondary" style="background:#e11d48; color:white;">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-data">Aucun visiteur en attente</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section">
                <h2>Historique du Jour</h2>
                <input type="text" id="historySearch" placeholder="Rechercher dans l'historique..." style="margin-bottom:10px; width:100%; padding:8px; border-radius:6px; border:1px solid #ccc;">
                <?php if (count($completedVisitors) > 0): ?>
                    <table class="visitors-table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Département</th>
                                <th>Objet</th>
                                <th>Arrivée</th>
                                <th>Fin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completedVisitors as $visitor): ?>
                                <tr>
                                    <td><?php echo $visitor['name']; ?></td>
                                    <?php
                                    $deptName = '';
                                    foreach ($activeDepartments as $dept) {
                                        if ($dept['id'] == $visitor['department_id']) {
                                            $deptName = $dept['name'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <td><?php echo $deptName; ?></td>
                                    <td><?php echo ucfirst($visitor['purpose']); ?></td>
                                    <td><?php echo date('H:i', strtotime($visitor['checked_in_at'])); ?></td>
                                    <td><?php echo date('H:i', strtotime($visitor['completed_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">Aucune visite terminée aujourd'hui</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    const idInput = document.getElementById('id_number');
    const errorSpan = document.getElementById('id_error');
    const form = document.querySelector('.visitor-form');

    function checkIdNumber(value) {
      const cniRegex = /^\d{10}$/;
      const recepisseRegex = /^[A-Za-z]{2}[A-Za-z0-9]{18}$/;
      if (value === "") {
        errorSpan.textContent = "";
        idInput.style.borderColor = "";
        return false;
      }
      if (cniRegex.test(value) || recepisseRegex.test(value)) {
        errorSpan.textContent = "";
        idInput.style.borderColor = "green";
        return true;
      } else {
        errorSpan.textContent = "Format invalide. CNI = 10 chiffres, Récépissé = 2 lettres + 18 caractères.";
        idInput.style.borderColor = "red";
        return false;
      }
    }

    idInput.addEventListener('input', function() {
      checkIdNumber(idInput.value.trim());
    });

    form.addEventListener('submit', function(e) {
      if (!checkIdNumber(idInput.value.trim())) {
        e.preventDefault();
        idInput.focus();
      }
    });

    const emailInput = document.getElementById('email');
    const emailErrorSpan = document.getElementById('email_error');

    function checkEmail(value) {
      if (value === "") {
        emailErrorSpan.textContent = "";
        emailInput.style.borderColor = "";
        return true; // champ optionnel
      }
      // Regex email simple
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (emailRegex.test(value)) {
        emailErrorSpan.textContent = "";
        emailInput.style.borderColor = "green";
        return true;
      } else {
        emailErrorSpan.textContent = "Format d'email invalide.";
        emailInput.style.borderColor = "red";
        return false;
      }
    }

    emailInput.addEventListener('input', function() {
      checkEmail(emailInput.value.trim());
    });

    // Ajout à la validation du formulaire
    form.addEventListener('submit', function(e) {
      if (!checkEmail(emailInput.value.trim())) {
        e.preventDefault();
        emailInput.focus();
      }
    });

    const phoneInput = document.getElementById('phone');
    const phoneErrorSpan = document.getElementById('phone_error');

    function checkPhone(value) {
      // Format : +237 suivi de 9 chiffres
      const phoneRegex = /^\+237\d{9}$/;
      if (value === "") {
        phoneErrorSpan.textContent = "";
        phoneInput.style.borderColor = "";
        return false;
      }
      if (phoneRegex.test(value)) {
        phoneErrorSpan.textContent = "";
        phoneInput.style.borderColor = "green";
        return true;
      } else {
        phoneErrorSpan.textContent = "Format de téléphone invalide. Exemple : +237612345678";
        phoneInput.style.borderColor = "red";
        return false;
      }
    }

    phoneInput.addEventListener('input', function() {
      checkPhone(phoneInput.value.trim());
    });

    // Ajout à la validation du formulaire
    form.addEventListener('submit', function(e) {
      if (!checkPhone(phoneInput.value.trim())) {
        e.preventDefault();
        phoneInput.focus();
      }
    });

    // Recherche dynamique file d'attente
    const queueSearch = document.getElementById('queueSearch');
    if (queueSearch) {
      queueSearch.addEventListener('input', function() {
        const value = queueSearch.value.toLowerCase();
        const table = document.querySelector('.queue-tables table');
        if (!table) return;
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(value) ? '' : 'none';
        });
      });
    }
    // Recherche dynamique historique du jour
    const historySearch = document.getElementById('historySearch');
    if (historySearch) {
      historySearch.addEventListener('input', function() {
        const value = historySearch.value.toLowerCase();
        const table = historySearch.nextElementSibling;
        if (!table || table.tagName !== 'TABLE') return;
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(value) ? '' : 'none';
        });
      });
    }

    function showNewVisitorForm() {
        document.getElementById('visitor-choice').style.display = 'none';
        document.getElementById('new-visitor-form').style.display = 'block';
        document.getElementById('old-visitor-form').style.display = 'none';
    }
    function showOldVisitorForm() {
        document.getElementById('visitor-choice').style.display = 'none';
        document.getElementById('new-visitor-form').style.display = 'none';
        document.getElementById('old-visitor-form').style.display = 'block';
    }
    function searchOldVisitor(e) {
        e.preventDefault();
        var searchInput = document.getElementById('old_id_number'); // champ de recherche
        var idNumber = searchInput.value.trim();
        var errorDiv = document.getElementById('old-visitor-error');
        var dataForm = document.getElementById('old-visitor-data-form');
        errorDiv.textContent = '';
        dataForm.style.display = 'none';
        fetch('../database/get_visitor_by_id.php?id_number=' + encodeURIComponent(idNumber))
            .then(response => response.json())
            .then(data => {
                if (data && data.success) {
                    document.getElementById('old_name').value = data.visitor.name;
                    document.getElementById('old_phone').value = data.visitor.phone;
                    document.getElementById('old_email').value = data.visitor.email;
                    document.getElementById('old_id_number_field').value = data.visitor.id_number; // champ readonly
                    document.getElementById('old_department').value = data.visitor.department_name;
                    document.getElementById('old_purpose').value = data.visitor.purpose;
                    dataForm.style.display = 'block';
                } else {
                    errorDiv.textContent = "Aucun visiteur trouvé avec ce numéro. Veuillez vérifier le format ou enregistrer ce visiteur comme nouveau.";
                }
            })
            .catch(() => {
                errorDiv.textContent = 'Erreur lors de la recherche.';
            });
        return false;
    }

    const oldIdInput = document.getElementById('old_id_number');
    const oldIdError = document.getElementById('old-visitor-error');
    if (oldIdInput) {
      oldIdInput.addEventListener('input', function() {
        const value = oldIdInput.value.trim();
        const cniRegex = /^\d{10}$/;
        const recepisseRegex = /^[A-Za-z]{2}[A-Za-z0-9]{18}$/;
        if (value === "") {
          oldIdError.textContent = "";
          oldIdInput.style.borderColor = "";
        } else if (cniRegex.test(value) || recepisseRegex.test(value)) {
          oldIdError.textContent = "";
          oldIdInput.style.borderColor = "green";
        } else {
          oldIdError.textContent = "Format invalide. CNI = 10 chiffres, Récépissé = 2 lettres + 18 caractères.";
          oldIdInput.style.borderColor = "red";
        }
      });
    }

    document.getElementById('old-visitor-search-form').addEventListener('submit', function(e) {
      const value = oldIdInput.value.trim();
      const cniRegex = /^\d{10}$/;
      const recepisseRegex = /^[A-Za-z]{2}[A-Za-z0-9]{18}$/;
      if (!cniRegex.test(value) && !recepisseRegex.test(value)) {
        e.preventDefault();
        oldIdError.textContent = "Format invalide. CNI = 10 chiffres, Récépissé = 2 lettres + 18 caractères.";
        oldIdInput.focus();
        return false;
      }
    });

    function backToVisitorChoice() {
        document.getElementById('visitor-choice').style.display = 'block';
        document.getElementById('new-visitor-form').style.display = 'none';
        document.getElementById('old-visitor-form').style.display = 'none';
    }
    </script>
</body>
</html>
