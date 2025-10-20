<?php
session_start();
require 'config.php';
if (!isset($_SESSION['rut'])) { header("Location: login.php"); exit(); }

$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$tipo = trim($_POST['tipo'] ?? 'Funcionalidad');
$topico = trim($_POST['topico'] ?? null);
$ambiente = trim($_POST['ambiente'] ?? null);
$autor = $_SESSION['rut'];

if (mb_strlen($titulo) < 20) {
    header("Location: index.php?error=title_too_short");
    exit();
}
if (mb_strlen($descripcion) > 200) {
    header("Location: index.php?error=desc_too_long");
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO solicitud (titulo, tipo, descripcion, ambiente, topico, autor, estado) VALUES (:titulo,:tipo,:descripcion,:ambiente,:topico,:autor,'Abierto')");
    $stmt->execute([
        ':titulo' => $titulo,
        ':tipo' => $tipo,
        ':descripcion' => $descripcion,
        ':ambiente' => $ambiente ?: null,
        ':topico' => $topico ?: null,
        ':autor' => $autor
    ]);
    header("Location: index.php?success=created");
    exit();
} catch (PDOException $e) {
    header("Location: index.php?error=db");
    exit();
}
?>