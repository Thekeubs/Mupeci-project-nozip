<?php
session_start();
require_once '../includes/functions.php';
require_once '../includes/auth.php';

checkAuth('receptionist');
initializeData();

$activeDepartments = getActiveDepartments();

$waitingCount = count(getVisitorsByStatus('waiting'));
$completedToday = count(array_filter(getTodayVisitors(), function($v) { return $v['status'] === 'completed'; }));
$activeDepartmentsCount = count(getActiveDepartments());
$appointmentsToday = count(array_filter(getTodayVisitors(), function($v) { return $v['purpose'] === 'rendez-vous'; }));

$departmentStats = getDepartmentStats();
$hourlyStats = getHourlyStats();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - MUPECI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <header class="app-header">
            <div class="header-content">
                <h1>MUPECI - Tableau de Bord</h1>
                <div class="user-info">
                    <span>Bonjour, <?php echo $_SESSION['user']['name']; ?></span>
                    <a href="reception.php" class="btn btn-sm">Réception</a>
                    <a href="../logout.php" class="btn btn-sm btn-secondary">Déconnexion</a>
                </div>
            </div>
        </header>

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
                        <p>Reçus Aujourd'hui</p>
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
                    <div class="stat-icon">📅</div>
                    <div class="stat-content">
                        <h3><?php echo $appointmentsToday; ?></h3>
                        <p>Rendez-vous</p>
                    </div>
                </div>
            </div>

            <div class="charts-grid">
                <div class="chart-container">
                    <h3>Classement des Départements</h3>
                    <div class="department-ranking">
                        <?php foreach (array_slice($departmentStats, 0, 5) as $index => $dept): ?>
                            <div class="ranking-item">
                                <div class="rank-badge rank-<?php echo $index + 1; ?>">
                                    <?php echo $index + 1; ?>
                                </div>
                                <div class="dept-info">
                                    <span class="dept-name"><?php echo $dept['name']; ?></span>
                                    <span class="dept-count"><?php echo $dept['count']; ?> visiteurs</span>
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

            <div class="section">
                <h2>Historique des Visites Terminées</h2>
                <?php 
                $completedVisitors = array_filter(getTodayVisitors(), function($v) { return $v['status'] === 'completed'; });
                if (count($completedVisitors) > 0): 
                ?>
                    <table class="visitors-table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Département</th>
                                <th>Objet</th>
                                <th>Arrivée</th>
                                <th>Fin</th>
                                <th>Durée</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completedVisitors as $visitor): ?>
                                <?php 
                                $start = strtotime($visitor['checked_in_at']);
                                $end = strtotime($visitor['completed_at']);
                                $duration = ($end && $start) ? round(($end - $start) / 60) : 0;
                                // Trouver le nom du département à partir de l'id
                                $deptName = '';
                                foreach ($activeDepartments as $dept) {
                                    if ($dept['id'] == $visitor['department_id']) {
                                        $deptName = $dept['name'];
                                        break;
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?php echo $visitor['name']; ?></td>
                                    <td><?php echo $deptName; ?></td>
                                    <td><?php echo ucfirst($visitor['purpose']); ?></td>
                                    <td><?php echo date('H:i', $start); ?></td>
                                    <td><?php echo date('H:i', $end); ?></td>
                                    <td><?php echo $duration; ?> min</td>
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
</body>
</html>
