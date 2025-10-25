<?php
require_once 'db.php';
require_once 'functions.php';
require_once 'phpqrcode/qrlib.php'; // asegúrate que esta ruta sea correcta

if (empty($_SESSION['pending_2fa_user'])) {
    exit('No hay usuario pendiente.');
}

$userId = (int)$_SESSION['pending_2fa_user'];
$stmt = $pdo->prepare("SELECT email, totp_secret FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();
if (!$user) exit('Usuario no encontrado.');

$otpauth = get_otpauth_url($user['totp_secret'], $user['email'], 'MiApp');

// Generar QR en memoria (sin guardar archivo)
ob_start();
QRcode::png($otpauth, null, QR_ECLEVEL_M, 5);
$imageData = base64_encode(ob_get_clean());
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Configura tu autenticador - MiApp</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">MiApp Segura</a>
  </div>
</nav>

<div class="container">
  <div class="card shadow-sm mx-auto p-4" style="max-width:480px;">
    <div class="card-body text-center">
      <h3 class="fw-bold mb-3 text-primary">Configura tu autenticador</h3>
      <p class="text-muted">Escanea este código QR con <strong>Google Authenticator</strong> o <strong>Authy</strong>.</p>

      <div class="my-4">
        <img src="data:image/png;base64,<?= $imageData ?>" alt="QR 2FA" class="img-fluid border rounded shadow-sm">
      </div>

      <p class="text-muted small">
        Si no puedes escanear el código, ingresa manualmente este secreto en tu app autenticadora:
      </p>
      <p class="fs-5 fw-bold text-danger"><?= e($user['totp_secret']) ?></p>

      <div class="alert alert-info text-start mt-4" role="alert">
        <strong>Importante:</strong> Después de configurar el autenticador, inicia sesión y se te pedirá
        ingresar el código de 6 dígitos generado por la app para verificar tu identidad.
      </div>

      <a href="login.php" class="btn btn-success w-100 mt-3 py-2">Ir a iniciar sesión</a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
