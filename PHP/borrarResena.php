<?php
session_start();
require 'config.php';

if (!isset($_SESSION['rut']) || ($_SESSION['tipo'] ?? '') !== 'ingeniero') {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
    exit('Acceso denegado.');
}

$id = (int)($_POST['id_resena'] ?? 0);
if ($id <= 0) {
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'errores.php'));
    exit();
}

$sel = $pdo->prepare("SELECT rut_ingeniero FROM resena WHERE id_resena = :id LIMIT 1");
$sel->execute([':id' => $id]);
$row = $sel->fetch(PDO::FETCH_ASSOC);
if (!$row || $row['rut_ingeniero'] !== $_SESSION['rut']) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
    exit('No puede eliminar esta reseña.');
}

$del = $pdo->prepare("DELETE FROM resena WHERE id_resena = :id");
$del->execute([':id' => $id]);

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'errores.php'));
exit();
?>