<?php
session_start();
require 'config.php';

if (!isset($_SESSION['rut'])) {
    header("Location: login.php");
    exit();
}

$me = $_SESSION['rut'];
$myType = $_SESSION['tipo'] ?? 'usuario';
$section = $_GET['section'] ?? '';
$selectedEng = $_GET['eng'] ?? '';

try {
    $engStmt = $pdo->prepare("SELECT DISTINCT u.rut, COALESCE(u.nombre, u.rut) AS nombre
                                FROM usuario u
                                JOIN ingeniero ig ON ig.rut = u.rut
                                ORDER BY nombre");
    $engStmt->execute();
    $ingenieros = $engStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $ingenieros = [];
}

$solicitudes = [];

if ($section === 'mis_funcionalidades') {
    if ($myType !== 'usuario') {
        http_response_code(403); echo 'Acceso denegado.'; exit();
    }
    $stmt = $pdo->prepare("SELECT id_solicitud, titulo, descripcion, fecha_publicacion, topico, estado
                            FROM solicitud
                            WHERE tipo = 'Funcionalidad' AND autor = :rut
                            ORDER BY fecha_publicacion DESC
                            LIMIT 200");
    $stmt->execute([':rut' => $me]);
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($section === 'mis_errores') {
    if ($myType !== 'usuario') {
        http_response_code(403); echo 'Acceso denegado.'; exit();
    }
    $stmt = $pdo->prepare("SELECT id_solicitud, titulo, descripcion, fecha_publicacion, topico, estado
                            FROM solicitud
                            WHERE tipo = 'Error' AND autor = :rut
                            ORDER BY fecha_publicacion DESC
                            LIMIT 200");
    $stmt->execute([':rut' => $me]);
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($section === 'asignadas') {
    if ($myType !== 'ingeniero') {
        http_response_code(403); echo 'Acceso denegado.'; exit();
    }
    $engRut = $selectedEng ?: $me;
    $stmt = $pdo->prepare("SELECT s.id_solicitud, s.titulo, s.descripcion, s.fecha_publicacion, s.topico, s.estado
                            FROM solicitud s
                            JOIN ingeniero_funcionalidad ifu ON ifu.id_solicitud = s.id_solicitud
                            WHERE ifu.rut = :eng
                            ORDER BY s.fecha_publicacion DESC
                            LIMIT 500");
    $stmt->execute([':eng' => $engRut]);
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<title>Mi cuenta</title>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand me-3" href="index.php">ZeroPressure</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Solicitudes</a></li>
                    <?php if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'ingeniero'): ?>
                        <li class="nav-item"><a class="nav-link" href="funcionalidades.php">Funcionalidades</a></li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'ingeniero'): ?>
                        <li class="nav-item"><a class="nav-link" href="errores.php">Errores</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link active" href="usuario.php">Usuario</a></li>
                </ul>

                <form action="logout.php" method="POST" class="d-flex ms-2">
                    <button class="btn btn-outline-secondary" type="submit">Cerrar sesión</button>
                </form>
            </div>
        </div>
    </nav>

<div class="container my-4">

    <h1>Mi cuenta</h1>

    <div class="mb-3">
        <?php if ($myType === 'usuario'): ?>
            <a class="btn btn-primary me-2" href="?section=mis_funcionalidades">Mis funcionalidades</a>
            <a class="btn btn-danger me-2" href="?section=mis_errores">Mis errores</a>
        <?php endif; ?>

        <?php if ($myType === 'ingeniero'): ?>
            <form class="d-inline-flex align-items-center" method="GET" action="">
                <input type="hidden" name="section" value="asignadas">
                <label class="me-2 mb-0">Ingeniero:</label>
                <select name="eng" class="form-select form-select-sm me-2" style="width:auto;">
                    <?php foreach ($ingenieros as $e): ?>
                        <option value="<?php echo htmlspecialchars($e['rut']); ?>" <?php if (($selectedEng ?: $me) === $e['rut']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($e['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-secondary btn-sm" type="submit">Ver asignadas</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($section === ''): ?>
        <div class="alert alert-secondary">Seleccione una sección arriba para ver solicitudes.</div>
    <?php else: ?>
        <h4 class="mb-3">
            <?php
                if ($section === 'mis_funcionalidades') echo 'Mis funcionalidades';
                if ($section === 'mis_errores') echo 'Mis errores';
                if ($section === 'asignadas') echo 'Solicitudes asignadas a ' . htmlspecialchars($selectedEng ?: $me);
            ?>
        </h4>

        <?php if (count($solicitudes) === 0): ?>
            <div class="alert alert-info">No hay solicitudes para mostrar.</div>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($solicitudes as $s): ?>
                    <li class="list-group-item">
                        <div class="fw-bold"><?php echo htmlspecialchars($s['titulo']); ?></div>
                        <div class="small text-muted mb-1">
                            <?php echo 'Publicado: ' . htmlspecialchars($s['fecha_publicacion'] ?? ''); ?> —
                            <?php echo 'Tópico: ' . htmlspecialchars($s['topico'] ?? 'N/A'); ?> —
                            <?php echo 'Estado: ' . htmlspecialchars($s['estado'] ?? 'N/A'); ?>
                        </div>
                        <div><?php echo nl2br(htmlspecialchars(mb_strimwidth($s['descripcion'] ?? '', 0, 300, '...'))); ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>