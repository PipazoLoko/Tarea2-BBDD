<?php
session_start();
require 'config.php';

if (!isset($_SESSION['rut']) || ($_SESSION['tipo'] ?? '') !== 'ingeniero') {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
    exit('Acceso denegado.');
}

$id = (int)($_POST['id_solicitud'] ?? 0);
$observacion = trim($_POST['observacion'] ?? '');

if ($id <= 0 || $observacion === '') {
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'errores.php'));
    exit();
}

// verificar que el ingeniero está asignado a la solicitud
$chk = $pdo->prepare("SELECT 1 FROM ingeniero_funcionalidad WHERE id_solicitud = :id AND rut = :rut LIMIT 1");
$chk->execute([':id' => $id, ':rut' => $_SESSION['rut']]);
if (!$chk->fetch()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
    exit('No asignado a esta solicitud.');
}

// insertar reseña
$ins = $pdo->prepare("INSERT INTO resena (id_solicitud, rut_ingeniero, observacion) VALUES (:id, :rut, :obs)");
$ins->execute([':id' => $id, ':rut' => $_SESSION['rut'], ':obs' => $observacion]);

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'errores.php'));
exit();
?>