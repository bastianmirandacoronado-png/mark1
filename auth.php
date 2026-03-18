<?php
/**
 * auth.php — Incluir al inicio de cada página protegida.
 * Si la sesión no es válida redirige al login.
 */
require_once __DIR__ . '/config.php';
session_start();

$ok = !empty($_SESSION['mark1_user'])
   && !empty($_SESSION['mark1_ts'])
   && (time() - $_SESSION['mark1_ts']) < SESSION_TIMEOUT;

if (!$ok) {
    // Renueva el timestamp si sigue activo
    $redirect = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
    header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/login.php?r=' . $redirect);
    exit;
}

// Renueva timestamp con cada petición
$_SESSION['mark1_ts'] = time();
define('AUTH_USER', $_SESSION['mark1_user']);
