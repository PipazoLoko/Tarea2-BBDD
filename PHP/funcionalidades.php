<?php
session_start();
require 'config.php';

if (!isset($_SESSION['rut'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'ingeniero') {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
    echo 'Acceso denegado.';
    exit();
}

$q = trim($_GET['q'] ?? '');
$estado = trim($_GET['estado'] ?? '');
$topico = trim($_GET['topico'] ?? '');
$ambiente = trim($_GET['ambiente'] ?? '');
$start_date = trim($_GET['start_date'] ?? '');
$end_date = trim($_GET['end_date'] ?? '');

$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$where = " WHERE s.tipo = :tipo_filter";
$params = [];
$params['tipo_filter'] = 'Funcionalidad';

if ($q !== '') {
    $where .= " AND (s.titulo LIKE :q OR s.descripcion LIKE :q)";
    $params['q'] = "%$q%";
}

if ($estado !== '') {
    $where .= " AND s.estado = :estado";
    $params['estado'] = $estado;
}

if ($topico !== '') {
    $where .= " AND s.topico = :topico";
    $params['topico'] = $topico;
}

if ($ambiente !== '') {
    $where .= " AND s.ambiente = :ambiente";
    $params['ambiente'] = $ambiente;
}

if ($start_date !== '' && $end_date !== '') {
    $where .= " AND s.fecha_publicacion BETWEEN :start_date AND :end_date";
    $params['start_date'] = $start_date;
    $params['end_date'] = $end_date;
} elseif ($start_date !== '') {
    $where .= " AND s.fecha_publicacion >= :start_date";
    $params['start_date'] = $start_date;
} elseif ($end_date !== '') {
    $where .= " AND s.fecha_publicacion <= :end_date";
    $params['end_date'] = $end_date;
}

try {
    $topicosStmt = $pdo->prepare("SELECT DISTINCT topico FROM solicitud WHERE tipo = :tipo_filter AND topico IS NOT NULL AND topico <> '' ORDER BY topico");
    $topicosStmt->execute(['tipo_filter' => 'Funcionalidad']);
    $topicos = $topicosStmt->fetchAll(PDO::FETCH_COLUMN);

    $ambientesStmt = $pdo->prepare("SELECT DISTINCT ambiente FROM solicitud WHERE tipo = :tipo_filter AND ambiente IS NOT NULL AND ambiente <> '' ORDER BY ambiente");
    $ambientesStmt->execute(['tipo_filter' => 'Funcionalidad']);
    $ambientes = $ambientesStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $topicos = [];
    $ambientes = [];
}

$countSql = "SELECT COUNT(*) FROM solicitud s" . $where;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($total / $perPage);

$sql = "SELECT s.id_solicitud,
        s.titulo,
        s.ambiente,
        s.descripcion,
        s.topico,
        s.estado,
        s.autor,
        u.nombre AS autor_nombre,
        GROUP_CONCAT(c.descripcion SEPARATOR '|||') AS criterios
        FROM solicitud s
        LEFT JOIN usuario u ON s.autor = u.rut
        LEFT JOIN criterio c ON s.id_solicitud = c.id_solicitud
        " . $where . "
        GROUP BY s.id_solicitud
        ORDER BY s.fecha_publicacion DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $val) {
    $stmt->bindValue(':' . $key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resenas_by_sol = [];
$assigned_ids = [];

$ids = array_column($solicitudes, 'id_solicitud');
if (count($ids) > 0) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $revSql = "SELECT r.id_resena, r.id_solicitud, r.observacion, r.fecha, r.rut_ingeniero, u.nombre AS ing_nombre
    FROM resena r
    LEFT JOIN usuario u ON u.rut = r.rut_ingeniero
    WHERE r.id_solicitud IN ($placeholders)
    ORDER BY r.fecha DESC";
    $revStmt = $pdo->prepare($revSql);
    $revStmt->execute($ids);
    while ($row = $revStmt->fetch(PDO::FETCH_ASSOC)) {
        $resenas_by_sol[$row['id_solicitud']][] = $row;
    }

    if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'ingeniero') {
        $params = $ids;
        $params[] = $_SESSION['rut'];
        $assignSql = "SELECT id_solicitud FROM ingeniero_funcionalidad WHERE id_solicitud IN ($placeholders) AND rut = ?";
        $assignStmt = $pdo->prepare($assignSql);
        $assignStmt->execute($params);
        while ($r = $assignStmt->fetch(PDO::FETCH_ASSOC)) {
            $assigned_ids[(int)$r['id_solicitud']] = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funcionalidades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <li class="nav-item"><a class="nav-link active" href="funcionalidades.php">Funcionalidades</a></li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'ingeniero'): ?>
                        <li class="nav-item"><a class="nav-link" href="errores.php">Errores</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="usuario.php">Usuario</a></li>
                </ul>

                <form class="d-flex" method="GET" action="funcionalidades.php">
                    <input class="form-control me-2" type="search" name="q" placeholder="Buscar..." value="<?php echo htmlspecialchars($q); ?>">
                    <select name="estado" class="form-select me-2" style="max-width:150px;">
                        <option value="">Todos</option>
                        <option value="Abierta" <?php if($estado==='Abierta') echo 'selected'; ?>>Abierta</option>
                        <option value="En Progreso" <?php if($estado==='En Progreso') echo 'selected'; ?>>En Progreso</option>
                        <option value="Resuelto" <?php if($estado==='Resuelto') echo 'selected'; ?>>Resuelto</option>
                        <option value="Cerrado" <?php if($estado==='Cerrado') echo 'selected'; ?>>Cerrado</option>
                    </select>
                    <button type="button" class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#filtrosModal">
                        Filtros
                    </button>
                    <button class="btn btn-outline-success" type="submit">Buscar</button>
                </form>

                <form action="logout.php" method="POST" class="d-flex ms-2">
                    <button class="btn btn-outline-secondary" type="submit">Cerrar sesión</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="modal fade" id="filtrosModal" tabindex="-1" aria-labelledby="filtrosModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="GET" action="funcionalidades.php">
            <div class="modal-header">
                <h5 class="modal-title" id="filtrosModalLabel">Filtros avanzados</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="q" value="<?php echo htmlspecialchars($q); ?>">
                <input type="hidden" name="estado" value="<?php echo htmlspecialchars($estado); ?>">

                <div class="mb-3">
                    <label for="start_date" class="form-label">Fecha desde</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>">
                </div>

                <div class="mb-3">
                    <label for="end_date" class="form-label">Fecha hasta</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>

                <div class="mb-3">
                <label for="topico" class="form-label">Tópico</label>
                <select id="topico" name="topico" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($topicos as $t): ?>
                        <option value="<?php echo htmlspecialchars($t); ?>" <?php if($topico === $t) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($t); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                </div>

                <div class="mb-3">
                <label for="ambiente" class="form-label">Ambiente</label>
                <select id="ambiente" name="ambiente" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($ambientes as $a): ?>
                        <option value="<?php echo htmlspecialchars($a); ?>" <?php if($ambiente === $a) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($a); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="submit" class="btn btn-primary">Aplicar filtros</button>
            </div>
            </form>
        </div>
    </div>

<div class="container my-4">
    <h1>Funcionalidades</h1>

    <?php if (count($solicitudes) === 0): ?>
        <div class="alert alert-info">No se encontraron funcionalidades.</div>
    <?php else: ?>
        <ul class="list-group">
            <?php foreach ($solicitudes as $s): ?>
                <li class="list-group-item">
                    <h5 class="mb-1"><?php echo htmlspecialchars($s['titulo']); ?></h5>

                    <div class="small text-muted mb-2">
                        <?php echo 'Ambiente: ' . htmlspecialchars($s['ambiente'] ?? 'N/A'); ?> —
                        <?php echo 'Tópico: ' . htmlspecialchars($s['topico'] ?? 'N/A'); ?> —
                        <?php echo 'Estado: ' . htmlspecialchars($s['estado'] ?? 'N/A'); ?>
                    </div>

                    <p><?php echo nl2br(htmlspecialchars(mb_strimwidth($s['descripcion'] ?? '', 0, 250, '...'))); ?></p>

                    <div>
                        <strong>Criterios de aceptación:</strong>
                        <?php
                            if (!empty($s['criterios'])) {
                                $lista = explode('|||', $s['criterios']);
                                echo '<ul class="mb-1">';
                                foreach ($lista as $c) {
                                    echo '<li>' . htmlspecialchars($c) . '</li>';
                                }
                                echo '</ul>';
                            } else {
                                echo '<div class="text-muted">Sin criterios añadidos</div>';
                            }
                        ?>
                    </div>

                    <div class="text-muted small">
                        Solicitante: <?php echo htmlspecialchars($s['autor_nombre'] ?? $s['autor'] ?? 'Desconocido'); ?>
                    </div>
                    <div class="mt-3">
                            <strong>Reseñas</strong>
                            <?php
                                $rs = $resenas_by_sol[$s['id_solicitud']] ?? [];
                                if (count($rs) === 0) {
                                    echo '<div class="text-muted">Aún no hay reseñas.</div>';
                                } else {
                                    echo '<ul class="list-unstyled small">';
                                    foreach ($rs as $r) {
                                        echo '<li class="mb-2 border rounded p-2">';
                                        echo '<div class="fw-bold">' . htmlspecialchars($r['ing_nombre'] ?? $r['rut_ingeniero']) . ' <span class="text-muted small">— ' . htmlspecialchars($r['fecha']) . '</span></div>';
                                        echo '<div>' . nl2br(htmlspecialchars($r['observacion'])) . '</div>';
                                        if (isset($_SESSION['rut']) && $_SESSION['rut'] === $r['rut_ingeniero']) {
                                            echo '<div class="mt-2">';
                                            echo '<button class="btn btn-sm btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#editResenaModal" data-id="' . $r['id_resena'] . '" data-obs="' . htmlspecialchars($r['observacion'], ENT_QUOTES) . '">Editar</button>';
                                            echo '<button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteResenaModal" data-id="' . $r['id_resena'] . '">Eliminar</button>';
                                            echo '</div>';
                                        }
                                        echo '</li>';
                                    }
                                    echo '</ul>';
                                }

                                $isAssigned = isset($assigned_ids[(int)$s['id_solicitud']]);
                                if ($isAssigned) {
                                    echo '<div class="mt-2"><button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createResenaModal" data-id="' . $s['id_solicitud'] . '">Agregar reseña</button></div>';
                                }
                            ?>
                        </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="modal fade" id="createResenaModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" method="POST" action="crearResena.php">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar reseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_solicitud" id="create_res_id">
                    <div class="mb-3">
                    <label class="form-label">Observación</label>
                    <textarea name="observacion" id="create_res_obs" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
                </form>
            </div>
            </div>

            <div class="modal fade" id="editResenaModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" method="POST" action="editarResena.php">
                <div class="modal-header">
                    <h5 class="modal-title">Editar reseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_resena" id="edit_res_id">
                    <div class="mb-3">
                    <label class="form-label">Observación</label>
                    <textarea name="observacion" id="edit_res_obs" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
                </form>
            </div>
            </div>

            <div class="modal fade" id="deleteResenaModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" method="POST" action="borrarResena.php">
                <div class="modal-header">
                    <h5 class="modal-title">Eliminar reseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_resena" id="delete_res_id">
                    <p>¿Seguro que desea eliminar esta reseña?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </div>
                </form>
            </div>
        </div>

        <nav aria-label="Paginación funcionalidades" class="mt-3">
            <ul class="pagination">
                <?php
                $baseUrl = 'funcionalidades.php?';
                if ($q !== '') $baseUrl .= 'q=' . urlencode($q) . '&';
                if ($estado !== '') $baseUrl .= 'estado=' . urlencode($estado) . '&';
                if ($start_date !== '') $baseUrl .= 'start_date=' . urlencode($start_date) . '&';
                if ($end_date !== '') $baseUrl .= 'end_date=' . urlencode($end_date) . '&';
                if ($topico !== '') $baseUrl .= 'topico=' . urlencode($topico) . '&';
                if ($ambiente !== '') $baseUrl .= 'ambiente=' . urlencode($ambiente) . '&';
                $prevClass = ($page <= 1) ? 'disabled' : '';
                $nextClass = ($page >= $totalPages) ? 'disabled' : '';
                ?>
                <li class="page-item <?php echo $prevClass; ?>">
                    <a class="page-link" href="<?php echo $page > 1 ? $baseUrl . 'page=' . ($page - 1) : '#'; ?>">Anterior</a>
                </li>

                <?php
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                for ($p = $start; $p <= $end; $p++): ?>
                    <li class="page-item <?php echo ($p === $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo $baseUrl . 'page=' . $p; ?>"><?php echo $p; ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?php echo $nextClass; ?>">
                    <a class="page-link" href="<?php echo $page < $totalPages ? $baseUrl . 'page=' . ($page + 1) : '#'; ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function(){
    var createModal = document.getElementById('createResenaModal');
    createModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('create_res_id').value = button.getAttribute('data-id') || '';
        document.getElementById('create_res_obs').value = '';
    });

    var editModal = document.getElementById('editResenaModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('edit_res_id').value = button.getAttribute('data-id') || '';
        document.getElementById('edit_res_obs').value = button.getAttribute('data-obs') || '';
    });

    var deleteModal = document.getElementById('deleteResenaModal');
    deleteModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        document.getElementById('delete_res_id').value = button.getAttribute('data-id') || '';
    });
    });
</script>
</body>
</html>
?>