<?php
require_once 'db.php';
require_once 'functions.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

// --------------------------------------------------
// Consultas seguras
// --------------------------------------------------

// Últimos 10 intentos del usuario
$stmt = $pdo->prepare("SELECT * FROM login_attempts WHERE user_id = :uid OR ip = :ip ORDER BY attempt_time DESC LIMIT 10");
$stmt->execute(['uid' => $userId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0']);
$intentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total intentos
$totalIntentos = count($intentos);

// Éxitos y fallos
$exitos = count(array_filter($intentos, fn($r) => $r['success']));
$fallos = $totalIntentos - $exitos;

// Último login exitoso
$stmt = $pdo->prepare("SELECT attempt_time, ip FROM login_attempts WHERE user_id = :uid AND success = 1 ORDER BY attempt_time DESC LIMIT 1");
$stmt->execute(['uid' => $userId]);
$ultimoLogin = $stmt->fetch();

// Último fallo
$stmt = $pdo->prepare("SELECT attempt_time, ip FROM login_attempts WHERE user_id = :uid AND success = 0 ORDER BY attempt_time DESC LIMIT 1");
$stmt->execute(['uid' => $userId]);
$ultimoFallo = $stmt->fetch();

// --------------------------------------------------
// Datos del usuario
// --------------------------------------------------
$stmt = $pdo->prepare("SELECT email, last_login, last_ip FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel de Seguridad</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">MiApp Segura</a>
    <ul class="navbar-nav ms-auto">
      <li class="nav-item"><a class="nav-link" href="dashboard.php">Inicio</a></li>
      <li class="nav-item"><a class="nav-link active" href="#">Seguridad</a></li>
      <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Salir</a></li>
    </ul>
  </div>
</nav>

<div class="container py-4">
  <h3 class="fw-bold text-center mb-4">Panel de Seguridad Personal</h3>

  <div class="row g-3">
    <!-- Info del usuario -->
    <div class="col-md-4">
      <div class="card shadow-sm h-100 border-primary">
        <div class="card-body">
          <h5 class="card-title text-primary">Datos del Usuario</h5>
          <p class="mb-1"><strong>Email:</strong> <?= e($user['email']) ?></p>
          <p class="mb-1"><strong>Último acceso:</strong> <?= e($user['last_login'] ?? 'N/A') ?></p>
          <p class="mb-1"><strong>IP registrada:</strong> <?= e($user['last_ip'] ?? 'N/A') ?></p>
          <p class="mb-0"><strong>IP actual:</strong> <?= e($_SERVER['REMOTE_ADDR'] ?? 'N/A') ?></p>
        </div>
      </div>
    </div>

    <!-- Estadísticas -->
    <div class="col-md-8">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="card-title text-success">Resumen de Actividad</h5>
          <div class="row text-center">
            <div class="col">
              <h2 class="text-success"><?= $exitos ?></h2>
              <p class="text-muted mb-0">Inicios exitosos</p>
            </div>
            <div class="col">
              <h2 class="text-danger"><?= $fallos ?></h2>
              <p class="text-muted mb-0">Intentos fallidos</p>
            </div>
            <div class="col">
              <h2 class="text-primary"><?= $totalIntentos ?></h2>
              <p class="text-muted mb-0">Total registrados</p>
            </div>
          </div>
          <canvas id="chartLogins" height="90" class="mt-3"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabla de los últimos intentos -->
  <div class="card shadow-sm mt-4">
    <div class="card-body">
      <h5 class="card-title mb-3">Últimos intentos de inicio de sesión</h5>
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead class="table-dark">
            <tr>
              <th>ID</th>
              <th>IP</th>
              <th>Fecha/Hora</th>
              <th>Resultado</th>
              <th>Navegador</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($intentos as $row): ?>
            <tr>
              <td><?= e($row['id']) ?></td>
              <td><?= e($row['ip']) ?></td>
              <td><?= e($row['attempt_time']) ?></td>
              <td>
                <?php if ($row['success']): ?>
                  <span class="badge bg-success">Éxito</span>
                <?php else: ?>
                  <span class="badge bg-danger">Fallido</span>
                <?php endif; ?>
              </td>
              <td class="text-truncate" style="max-width:200px;"><?= e($row['user_agent']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ctx = document.getElementById('chartLogins');
new Chart(ctx, {
  type: 'doughnut',
  data: {
    labels: ['Éxitos', 'Fallos'],
    datasets: [{
      data: [<?= $exitos ?>, <?= $fallos ?>],
      backgroundColor: ['#198754', '#dc3545']
    }]
  },
  options: {
    plugins: { legend: { position: 'bottom' } }
  }
});
</script>
</body>
</html>
