<?php
$title = $title ?? 'ZeroPressure - Plataforma de Solicitudes';
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($title); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header>
    <div style="display:flex; justify-content: space-between; align-items: center;">
        <div>
            <strong>ZeroPressure</strong>
            <span style="margin-left:12px; font-weight:300;">Gestion de funcionalidades y errores</span>
        </div>
        <?php if ($user): ?>
            <div>Hola, <?= sanitize($user['nombre_completo']); ?> (<?= sanitize($user['rol']); ?>)</div>
        <?php endif; ?>
    </div>
    <?php if ($user): ?>
        <?php include __DIR__ . '/nav.php'; ?>
    <?php endif; ?>
</header>
<main>
