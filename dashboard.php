<?php
// dashboard.php
require_once 'functions.php';
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Panel principal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">Seguridad en Aplicaciones UMG</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link active" href="dashboard.php">Panel</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Cerrar sesión</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
  <div class="card shadow-sm mx-auto text-center p-5" style="max-width: 600px;">
    <div class="mb-3">
      <h2 class="fw-bold">Bienvenido 👋</h2>
      <h5 class="text-primary"><?= e($_SESSION['user_email']) ?></h5>
    </div>

    <p class="text-muted">Has accedido correctamente mediante doble autenticación (2FA).</p>
    <p class="mb-4">Tu sesión es segura y fue verificada en esta fecha y hora:</p>
    <p><span class="badge bg-success"><?= date('d/m/Y H:i:s') ?></span></p>

    <a href="logout.php" class="btn btn-outline-danger mt-4 px-4 py-2">Cerrar sesión</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
