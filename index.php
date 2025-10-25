<?php
// register.php
require_once 'db.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        exit('CSRF inválido');
    }

    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (!$email || strlen($password) < 10 || $password !== $password2) {
        $error = 'Datos erróneos. Contraseña mín 10 caracteres y repetir igual.';
    } else {
        // Verificar si usuario existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $error = 'Ya existe usuario con ese email.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $totp_secret = generate_totp_secret(20);
            $insert = $pdo->prepare("INSERT INTO users (email, password_hash, totp_secret) VALUES (:email, :hash, :secret)");
            $insert->execute([
                'email' => $email,
                'hash' => $hash,
                'secret' => $totp_secret
            ]);
            $userId = (int)$pdo->lastInsertId();
            $_SESSION['pending_2fa_user'] = $userId;
            header('Location: register_success.php');
            exit;
        }
    }
}
$token = csrf_token();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Registro seguro</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">Seguridad en Aplicaciones UMG</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
  <div class="card shadow-sm p-4 mx-auto" style="max-width: 480px;">
    <h3 class="text-center mb-4">Crear cuenta</h3>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger text-center" role="alert">
        <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off" novalidate>
      <input type="hidden" name="csrf" value="<?= e($token) ?>">

      <div class="mb-3">
        <label class="form-label">Correo electrónico</label>
        <input type="email" name="email" class="form-control" placeholder="usuario@correo.com" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Contraseña (mínimo 10 caracteres)</label>
        <input type="password" name="password" class="form-control" minlength="10" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Repetir contraseña</label>
        <input type="password" name="password2" class="form-control" minlength="10" required>
      </div>

      <button type="submit" class="btn btn-primary w-100 py-2">Registrarme</button>
    </form>

    <div class="text-center mt-3">
      <small>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión</a></small>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
