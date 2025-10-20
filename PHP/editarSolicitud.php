<?php
session_start();
require 'config.php';
if (!isset($_SESSION['rut'])) { header("Location: login.php"); exit(); }

$id = (int)($_POST['id_solicitud'] ?? 0);
$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$topico = trim($_POST['topico'] ?? null);
$ambiente = trim($_POST['ambiente'] ?? null);
$estado = trim($_POST['estado'] ?? 'Abierto');

$sel = $pdo->prepare("SELECT autor, estado FROM solicitud WHERE id_solicitud = :id");
$sel->execute([':id'=>$id]);
$sol = $sel->fetch(PDO::FETCH_ASSOC);
if (!$sol) { header("Location: index.php?error=notfound"); exit(); }
if ($sol['autor'] !== $_SESSION['rut']) { header("Location: index.php?error=forbidden"); exit(); }
if ($sol['estado'] === 'En Progreso') { header("Location: index.php?error=inprogress"); exit(); }

$upd = $pdo->prepare("UPDATE solicitud SET titulo = :titulo, descripcion = :descripcion, topico = :topico, ambiente = :ambiente, estado = :estado WHERE id_solicitud = :id");
$upd->execute([
    ':titulo'=>$titulo, ':descripcion'=>$descripcion,
    ':topico'=>$topico ?: null, ':ambiente'=>$ambiente ?: null, ':estado'=>$estado, ':id'=>$id
]);

header("Location: index.php");
exit();
?>