<?php
session_start();
require 'config.php';
if (!isset($_SESSION['rut'])) { header("Location: login.php"); exit(); }

$id = (int)($_POST['id_solicitud'] ?? 0);

$sel = $pdo->prepare("SELECT autor, estado FROM solicitud WHERE id_solicitud = :id");
$sel->execute([':id'=>$id]);
$sol = $sel->fetch(PDO::FETCH_ASSOC);
if (!$sol) { header("Location: index.php?error=notfound"); exit(); }
if ($sol['autor'] !== $_SESSION['rut']) { header("Location: index.php?error=forbidden"); exit(); }
if ($sol['estado'] === 'En Progreso') { header("Location: index.php?error=inprogress"); exit(); }

$del = $pdo->prepare("DELETE FROM solicitud WHERE id_solicitud = :id");
$del->execute([':id'=>$id]);

header("Location: index.php");
exit();
?>