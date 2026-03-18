<?php
require_once __DIR__ . '/config.php';
session_start();

$error   = '';
$redirect = $_GET['r'] ?? 'index.php';

// Ya logueado → redirige
if (!empty($_SESSION['mark1_user']) && !empty($_SESSION['mark1_ts'])
    && (time() - $_SESSION['mark1_ts']) < SESSION_TIMEOUT) {
    header('Location: ' . $redirect);
    exit;
}

// Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['usuario'] ?? '');
    $pass = trim($_POST['clave']   ?? '');
    $users = AUTH_USERS;

    if ($user && isset($users[$user]) && $users[$user] === $pass) {
        $_SESSION['mark1_user'] = $user;
        $_SESSION['mark1_ts']   = time();
        header('Location: ' . $redirect);
        exit;
    }
    $error = 'Usuario o contraseña incorrectos.';
}
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Acceso — DSSO Mark 1</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Roboto+Slab:wght@700&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{
    min-height:100vh;
    background:linear-gradient(135deg,#0F2D6B 0%,#1A4BA0 50%,#0F69C4 100%);
    display:flex;align-items:center;justify-content:center;
    font-family:'Inter',sans-serif;
  }
  .card{
    background:#fff;border-radius:16px;
    padding:44px 40px;width:380px;max-width:95vw;
    box-shadow:0 24px 64px rgba(0,0,0,0.25);
  }
  .logo-row{display:flex;align-items:center;gap:14px;margin-bottom:28px;}
  .logo-row img{height:52px;object-fit:contain;}
  .logo-row .titles{line-height:1.2}
  .logo-row .org{font-family:'Roboto Slab',serif;font-size:0.88rem;font-weight:700;color:#0F2D6B;}
  .logo-row .sub{font-size:0.7rem;color:#6C757D;margin-top:2px;}
  h2{font-family:'Roboto Slab',serif;font-size:1.25rem;color:#1E293B;margin-bottom:6px;}
  p.hint{font-size:0.78rem;color:#6C757D;margin-bottom:24px;}
  label{display:block;font-size:0.72rem;font-weight:600;color:#374151;margin-bottom:5px;margin-top:14px;}
  input{
    width:100%;border:1px solid #D1D5DB;border-radius:8px;
    padding:10px 13px;font-size:0.9rem;color:#1E293B;
    transition:border .15s;
  }
  input:focus{outline:none;border-color:#0F69C4;box-shadow:0 0 0 3px rgba(15,105,196,.15);}
  .btn{
    margin-top:22px;width:100%;
    background:linear-gradient(135deg,#0F2D6B,#1A4BA0);
    color:#fff;border:none;border-radius:8px;
    padding:12px;font-size:0.92rem;font-weight:600;
    cursor:pointer;transition:opacity .15s;
  }
  .btn:hover{opacity:.9;}
  .error{
    margin-top:14px;padding:10px 14px;
    background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;
    color:#B91C1C;font-size:0.8rem;
  }
  .footer{margin-top:28px;text-align:center;font-size:0.68rem;color:#94A3B8;}
</style>
</head>
<body>
<div class="card">
  <div class="logo-row">
    <img src="public/img/logo_sso.png" alt="SSO" onerror="this.style.display='none'">
    <div class="titles">
      <div class="org">Ministerio de Salud · DSSO</div>
      <div class="sub">División de Gestión de Compras</div>
    </div>
  </div>

  <h2>Acceso al Sistema</h2>
  <p class="hint">Plataforma de Gestión de Compras — Mark 1</p>

  <form method="POST">
    <label for="usuario">Usuario</label>
    <input type="text" id="usuario" name="usuario" autocomplete="username"
           placeholder="Ingresa tu usuario" autofocus required>

    <label for="clave">Contraseña</label>
    <input type="password" id="clave" name="clave" autocomplete="current-password"
           placeholder="••••••••" required>

    <button type="submit" class="btn">Ingresar →</button>

    <?php if ($error): ?>
      <div class="error">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
  </form>

  <div class="footer">Servicio de Salud Osorno · Acceso restringido a personal autorizado</div>
</div>
</body>
</html>
