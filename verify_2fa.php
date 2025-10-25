<?php
// verify_2fa.php
require_once 'db.php';
require_once 'functions.php';

if (empty($_SESSION['pre_2fa_user'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['pre_2fa_user'];

// Caducidad del pre-2fa (5 minutos)
if (time() - ($_SESSION['pre_2fa_time'] ?? 0) > 300) {
    unset($_SESSION['pre_2fa_user'], $_SESSION['pre_2fa_time']);
    exit('Tiempo agotado, vuelve a iniciar sesión.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) exit('CSRF inválido');
    $code = preg_replace('/\D/', '', $_POST['code'] ?? '');

    if (!preg_match('/^\d{6}$/', $code)) {
        $error = 'Código inválido';
    } else {
        $stmt = $pdo->prepare("SELECT id, email, totp_secret FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        if (!$user) exit('Usuario no encontrado.');

        if (!verify_totp($user['totp_secret'], $code, 3, 30, 6)) {
            $error = 'Código 2FA incorrecto';
            $ins = $pdo->prepare("INSERT INTO login_attempts (user_id, ip, success, user_agent)
                                  VALUES (:uid, :ip, 0, :ua)");
            $ins->execute([
                'uid' => $userId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
            ]);
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            unset($_SESSION['pre_2fa_user'], $_SESSION['pre_2fa_time']);

            $upd = $pdo->prepare("UPDATE users SET last_login = NOW(), last_ip = :ip WHERE id = :id");
            $upd->execute([
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'id' => $user['id']
            ]);

            $ins = $pdo->prepare("INSERT INTO login_attempts (user_id, ip, success, user_agent)
                                  VALUES (:uid, :ip, 1, :ua)");
            $ins->execute([
                'uid' => $userId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
            ]);

            header('Location: dashboard.php');
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
  <title>Verificación 2FA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">MiApp Segura</a>
  </div>
</nav>

<div class="container">
  <div class="card shadow-sm p-4 mx-auto" style="max-width:420px;">
    <h3 class="text-center mb-3">Verificación 2FA</h3>
    <p class="text-muted text-center">Introduce el código de 6 dígitos generado por tu app de autenticación.</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger text-center"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf" value="<?= e($token) ?>">
      <div class="mb-3">
        <label class="form-label d-block text-center">Código (6 dígitos)</label>
        <input name="code" required pattern="\d{6}" maxlength="6"
               class="form-control text-center fs-4 fw-bold"
               placeholder="123456">
      </div>
      <button type="submit" class="btn btn-success w-100 py-2">Verificar</button>
    </form>

    <div class="text-center mt-3">
      <a href="login.php" class="text-decoration-none">&larr; Volver al inicio de sesión</a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
