<?php
// login.php
require_once 'db.php';
require_once 'functions.php';

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) exit('CSRF inválido');

    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email) $error = 'Email inválido';

    if (empty($error)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Credenciales inválidas';
            $ins = $pdo->prepare("INSERT INTO login_attempts (user_id, ip, success, user_agent) VALUES (NULL, :ip, 0, :ua)");
            $ins->execute(['ip'=>$ip, 'ua'=>substr($_SERVER['HTTP_USER_AGENT'] ?? '',0,255)]);
        } else {
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $error = 'Cuenta bloqueada temporalmente por múltiples intentos fallidos.';
            } else {
                if (password_verify($password, $user['password_hash'])) {
                    $upd = $pdo->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = :id");
                    $upd->execute(['id' => $user['id']]);

                    session_regenerate_id(true);
                    $_SESSION['pre_2fa_user'] = $user['id'];
                    $_SESSION['pre_2fa_time'] = time();

                    $ins = $pdo->prepare("INSERT INTO login_attempts (user_id, ip, success, user_agent)
                                           VALUES (:uid, :ip, 1, :ua)");
                    $ins->execute(['uid'=>$user['id'],'ip'=>$ip,'ua'=>substr($_SERVER['HTTP_USER_AGENT'] ?? '',0,255)]);

                    header('Location: verify_2fa.php');
                    exit;
                } else {
                    $failed = (int)$user['failed_login_attempts'] + 1;
                    $locked_until = null;
                    if ($failed >= 5) {
                        $locked_until = date('Y-m-d H:i:s', time() + 15*60);
                        $failed = 0;
                    }
                    $upd = $pdo->prepare("UPDATE users SET failed_login_attempts = :f, locked_until = :lu WHERE id = :id");
                    $upd->execute(['f'=>$failed,'lu'=>$locked_until,'id'=>$user['id']]);

                    $ins = $pdo->prepare("INSERT INTO login_attempts (user_id, ip, success, user_agent)
                                           VALUES (:uid, :ip, 0, :ua)");
                    $ins->execute(['uid'=>$user['id'],'ip'=>$ip,'ua'=>substr($_SERVER['HTTP_USER_AGENT'] ?? '',0,255)]);
                    $error = 'Credenciales inválidas';
                }
            }
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
  <title>Inicio de sesión</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">Seguridad en Aplicaciones UMG</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="register.php">Registro</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
  <div class="card shadow-sm p-4 mx-auto" style="max-width:480px;">
    <h3 class="text-center mb-4">Iniciar sesión</h3>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger text-center" role="alert">
        <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= e($token) ?>">

      <div class="mb-3">
        <label class="form-label">Correo electrónico</label>
        <input type="email" name="email" class="form-control" placeholder="usuario@correo.com" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <input type="password" name="password" class="form-control" placeholder="********" required>
      </div>

      <button type="submit" class="btn btn-primary w-100 py-2">Entrar</button>
    </form>

    <div class="text-center mt-3">
      <small>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></small>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
