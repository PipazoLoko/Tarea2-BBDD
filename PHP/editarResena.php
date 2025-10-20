<?php
session_start();
require 'config.php';

if (!isset($_SESSION['rut']) || ($_SESSION['tipo'] ?? '') !== 'ingeniero') {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
    exit('Acceso denegado.');
}

$id = (int)($_POST['id_resena'] ?? 0);
$observacion = trim($_POST['observacion'] ?? '');

if ($id <= 0 || $observacion === '') {
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'errores.php'));
    exit();
}

// verificar propiedad de la reseña por el ingeniero
$sel = $pdo->prepare("SELECT rut_ingeniero FROM resena WHERE id_resena = :id LIMIT 1");
$sel->execute([':id' => $id]);
$row = $sel->fetch(PDO::FETCH_ASSOC);
if (!$row || $row['rut_ingeniero'] !== $_SESSION['rut']) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
    exit('No puede editar esta reseña.');
}

$upd = $pdo->prepare("UPDATE resena SET observacion = :obs, fecha = CURRENT_TIMESTAMP WHERE id_resena = :id");
$upd->execute([':obs' => $observacion, ':id' => $id]);

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'errores.php'));
exit();
?>