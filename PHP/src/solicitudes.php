<?php
use PDO;

function getAllTopics(): array
{
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT id, nombre FROM topicos ORDER BY nombre');
    return $stmt->fetchAll();
}

function getAllEstados(): array
{
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT id, nombre FROM estados_solicitud ORDER BY id');
    return $stmt->fetchAll();
}

function getEstadoIdByNombre(string $nombre): ?int
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT id FROM estados_solicitud WHERE nombre = :nombre LIMIT 1');
    $stmt->execute(['nombre' => $nombre]);
    $row = $stmt->fetch();
    return $row ? (int) $row['id'] : null;
}

function fetchFuncionalidades(?int $ingenieroId = null, ?int $solicitanteId = null): array
{
    $pdo = getPDO();
    $sql = 'SELECT sf.id, sf.titulo, sf.ambiente, sf.resumen, sf.criterios_aceptacion, sf.fecha_creacion, sf.fecha_actualizacion,
                   t.nombre AS topico, es.nombre AS estado, u.nombre_completo AS solicitante,
                   ing.nombre_completo AS ingeniero
            FROM solicitudes_funcionalidad sf
            JOIN topicos t ON t.id = sf.topico_id
            JOIN estados_solicitud es ON es.id = sf.estado_id
            JOIN usuarios u ON u.id = sf.solicitante_id
            LEFT JOIN usuarios ing ON ing.id = sf.ingeniero_id';
    $conditions = [];
    $params = [];

    if ($ingenieroId !== null) {
        $conditions[] = 'sf.ingeniero_id = :ingeniero_id';
        $params['ingeniero_id'] = $ingenieroId;
    }

    if ($solicitanteId !== null) {
        $conditions[] = 'sf.solicitante_id = :solicitante_id';
        $params['solicitante_id'] = $solicitanteId;
    }

    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY sf.fecha_creacion DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetchErrores(?int $ingenieroId = null, ?int $solicitanteId = null): array
{
    $pdo = getPDO();
    $sql = 'SELECT se.id, se.titulo, se.ambiente, se.descripcion, se.fecha_publicacion,
                   se.fecha_creacion, se.fecha_actualizacion,
                   t.nombre AS topico, es.nombre AS estado, u.nombre_completo AS solicitante,
                   ing.nombre_completo AS ingeniero
            FROM solicitudes_error se
            JOIN topicos t ON t.id = se.topico_id
            JOIN estados_solicitud es ON es.id = se.estado_id
            JOIN usuarios u ON u.id = se.solicitante_id
            LEFT JOIN usuarios ing ON ing.id = se.ingeniero_id';
    $conditions = [];
    $params = [];

    if ($ingenieroId !== null) {
        $conditions[] = 'se.ingeniero_id = :ingeniero_id';
        $params['ingeniero_id'] = $ingenieroId;
    }

    if ($solicitanteId !== null) {
        $conditions[] = 'se.solicitante_id = :solicitante_id';
        $params['solicitante_id'] = $solicitanteId;
    }

    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY se.fecha_creacion DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getFuncionalidad(int $id): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT sf.*, t.nombre AS topico, es.nombre AS estado, u.nombre_completo AS solicitante_nombre,
                                   ing.nombre_completo AS ingeniero_nombre
                            FROM solicitudes_funcionalidad sf
                            JOIN topicos t ON t.id = sf.topico_id
                            JOIN estados_solicitud es ON es.id = sf.estado_id
                            JOIN usuarios u ON u.id = sf.solicitante_id
                            LEFT JOIN usuarios ing ON ing.id = sf.ingeniero_id
                            WHERE sf.id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getErrorSolicitud(int $id): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT se.*, t.nombre AS topico, es.nombre AS estado, u.nombre_completo AS solicitante_nombre,
                                   ing.nombre_completo AS ingeniero_nombre
                            FROM solicitudes_error se
                            JOIN topicos t ON t.id = se.topico_id
                            JOIN estados_solicitud es ON es.id = se.estado_id
                            JOIN usuarios u ON u.id = se.solicitante_id
                            LEFT JOIN usuarios ing ON ing.id = se.ingeniero_id
                            WHERE se.id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function createFuncionalidad(array $data, int $userId): int
{
    $pdo = getPDO();
    $estadoId = (int) ($data['estado_id'] ?? getEstadoIdByNombre('Nuevo'));
    if (!$estadoId) {
        throw new RuntimeException('Estado "Nuevo" no configurado en la base de datos.');
    }
    $stmt = $pdo->prepare('INSERT INTO solicitudes_funcionalidad (titulo, ambiente, resumen, criterios_aceptacion, topico_id, estado_id, solicitante_id)
                            VALUES (:titulo, :ambiente, :resumen, :criterios, :topico_id, :estado_id, :solicitante_id)');
    $stmt->execute([
        'titulo' => $data['titulo'],
        'ambiente' => $data['ambiente'],
        'resumen' => $data['resumen'],
        'criterios' => $data['criterios'],
        'topico_id' => (int) $data['topico_id'],
        'estado_id' => $estadoId,
        'solicitante_id' => $userId,
    ]);
    return (int) $pdo->lastInsertId();
}

function updateFuncionalidad(int $id, array $data, int $userId): bool
{
    $pdo = getPDO();
    $info = getFuncionalidad($id);
    if (!$info || (int) $info['solicitante_id'] !== $userId) {
        throw new RuntimeException('No tiene permisos para editar esta solicitud.');
    }
    if ($info['estado'] === 'En Progreso') {
        throw new RuntimeException('Las solicitudes en progreso no pueden ser modificadas.');
    }

    $stmt = $pdo->prepare('UPDATE solicitudes_funcionalidad
                              SET titulo = :titulo,
                                  ambiente = :ambiente,
                                  resumen = :resumen,
                                  criterios_aceptacion = :criterios,
                                  topico_id = :topico_id,
                                  estado_id = :estado_id
                            WHERE id = :id');
    return $stmt->execute([
        'titulo' => $data['titulo'],
        'ambiente' => $data['ambiente'],
        'resumen' => $data['resumen'],
        'criterios' => $data['criterios'],
        'topico_id' => (int) $data['topico_id'],
        'estado_id' => (int) $data['estado_id'],
        'id' => $id,
    ]);
}

function deleteFuncionalidad(int $id, int $userId): bool
{
    $info = getFuncionalidad($id);
    if (!$info || (int) $info['solicitante_id'] !== $userId) {
        throw new RuntimeException('No tiene permisos para eliminar esta solicitud.');
    }
    if ($info['estado'] === 'En Progreso') {
        throw new RuntimeException('Las solicitudes en progreso no pueden ser eliminadas.');
    }
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM solicitudes_funcionalidad WHERE id = :id');
    return $stmt->execute(['id' => $id]);
}

function createErrorSolicitud(array $data, int $userId): int
{
    $pdo = getPDO();
    $estadoId = (int) ($data['estado_id'] ?? getEstadoIdByNombre('Nuevo'));
    if (!$estadoId) {
        throw new RuntimeException('Estado "Nuevo" no configurado en la base de datos.');
    }
    $stmt = $pdo->prepare('INSERT INTO solicitudes_error (titulo, ambiente, descripcion, fecha_publicacion, topico_id, estado_id, solicitante_id)
                            VALUES (:titulo, :ambiente, :descripcion, :fecha_publicacion, :topico_id, :estado_id, :solicitante_id)');
    $stmt->execute([
        'titulo' => $data['titulo'],
        'ambiente' => $data['ambiente'],
        'descripcion' => $data['descripcion'],
        'fecha_publicacion' => $data['fecha_publicacion'],
        'topico_id' => (int) $data['topico_id'],
        'estado_id' => $estadoId,
        'solicitante_id' => $userId,
    ]);
    return (int) $pdo->lastInsertId();
}

function updateErrorSolicitud(int $id, array $data, int $userId): bool
{
    $info = getErrorSolicitud($id);
    if (!$info || (int) $info['solicitante_id'] !== $userId) {
        throw new RuntimeException('No tiene permisos para editar esta solicitud.');
    }
    if ($info['estado'] === 'En Progreso') {
        throw new RuntimeException('Las solicitudes en progreso no pueden ser modificadas.');
    }
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE solicitudes_error
                              SET titulo = :titulo,
                                  ambiente = :ambiente,
                                  descripcion = :descripcion,
                                  fecha_publicacion = :fecha_publicacion,
                                  topico_id = :topico_id,
                                  estado_id = :estado_id
                            WHERE id = :id');
    return $stmt->execute([
        'titulo' => $data['titulo'],
        'ambiente' => $data['ambiente'],
        'descripcion' => $data['descripcion'],
        'fecha_publicacion' => $data['fecha_publicacion'],
        'topico_id' => (int) $data['topico_id'],
        'estado_id' => (int) $data['estado_id'],
        'id' => $id,
    ]);
}

function deleteErrorSolicitud(int $id, int $userId): bool
{
    $info = getErrorSolicitud($id);
    if (!$info || (int) $info['solicitante_id'] !== $userId) {
        throw new RuntimeException('No tiene permisos para eliminar esta solicitud.');
    }
    if ($info['estado'] === 'En Progreso') {
        throw new RuntimeException('Las solicitudes en progreso no pueden ser eliminadas.');
    }
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM solicitudes_error WHERE id = :id');
    return $stmt->execute(['id' => $id]);
}

function fetchAsignadasParaIngeniero(int $ingenieroId): array
{
    return [
        'funcionalidades' => fetchFuncionalidades($ingenieroId, null),
        'errores' => fetchErrores($ingenieroId, null),
    ];
}

function globalSearch(string $term): array
{
    $pdo = getPDO();
    $like = '%' . $term . '%';

    $funcStmt = $pdo->prepare('SELECT sf.id, sf.titulo, es.nombre AS estado, t.nombre AS topico, "FUNCIONALIDAD" AS tipo
                               FROM solicitudes_funcionalidad sf
                               JOIN estados_solicitud es ON es.id = sf.estado_id
                               JOIN topicos t ON t.id = sf.topico_id
                               WHERE sf.titulo LIKE :term');
    $funcStmt->execute(['term' => $like]);

    $errStmt = $pdo->prepare('SELECT se.id, se.titulo, es.nombre AS estado, t.nombre AS topico, "ERROR" AS tipo
                               FROM solicitudes_error se
                               JOIN estados_solicitud es ON es.id = se.estado_id
                               JOIN topicos t ON t.id = se.topico_id
                               WHERE se.titulo LIKE :term');
    $errStmt->execute(['term' => $like]);

    return [
        'funcionalidades' => $funcStmt->fetchAll(),
        'errores' => $errStmt->fetchAll(),
    ];
}

function advancedSearch(array $criteria): array
{
    $pdo = getPDO();
    $results = [];
    $tipo = $criteria['tipo'] ?? null;

    if ($tipo === null || $tipo === 'FUNCIONALIDAD') {
        $sql = "SELECT sf.id, 'FUNCIONALIDAD' AS tipo, sf.titulo, sf.ambiente, sf.fecha_creacion,
                       t.nombre AS topico, es.nombre AS estado, u.nombre_completo AS solicitante
                FROM solicitudes_funcionalidad sf
                JOIN topicos t ON t.id = sf.topico_id
                JOIN estados_solicitud es ON es.id = sf.estado_id
                JOIN usuarios u ON u.id = sf.solicitante_id
                WHERE 1 = 1";
        $params = [];
        if (!empty($criteria['topico_id'])) {
            $sql .= ' AND sf.topico_id = :f_topico';
            $params['f_topico'] = (int) $criteria['topico_id'];
        }
        if (!empty($criteria['estado_id'])) {
            $sql .= ' AND sf.estado_id = :f_estado';
            $params['f_estado'] = (int) $criteria['estado_id'];
        }
        if (!empty($criteria['ambiente'])) {
            $sql .= ' AND sf.ambiente = :f_ambiente';
            $params['f_ambiente'] = $criteria['ambiente'];
        }
        if (!empty($criteria['solicitante'])) {
            $sql .= ' AND u.nombre_completo LIKE :f_solicitante';
            $params['f_solicitante'] = '%' . $criteria['solicitante'] . '%';
        }
        if (!empty($criteria['fecha_desde'])) {
            $sql .= ' AND sf.fecha_creacion >= :f_fecha_desde';
            $params['f_fecha_desde'] = $criteria['fecha_desde'] . ' 00:00:00';
        }
        if (!empty($criteria['fecha_hasta'])) {
            $sql .= ' AND sf.fecha_creacion <= :f_fecha_hasta';
            $params['f_fecha_hasta'] = $criteria['fecha_hasta'] . ' 23:59:59';
        }
        $sql .= ' ORDER BY sf.fecha_creacion DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = array_merge($results, $stmt->fetchAll());
    }

    if ($tipo === null || $tipo === 'ERROR') {
        $sql = "SELECT se.id, 'ERROR' AS tipo, se.titulo, se.ambiente, se.fecha_creacion,
                       t.nombre AS topico, es.nombre AS estado, u.nombre_completo AS solicitante
                FROM solicitudes_error se
                JOIN topicos t ON t.id = se.topico_id
                JOIN estados_solicitud es ON es.id = se.estado_id
                JOIN usuarios u ON u.id = se.solicitante_id
                WHERE 1 = 1";
        $params = [];
        if (!empty($criteria['topico_id'])) {
            $sql .= ' AND se.topico_id = :e_topico';
            $params['e_topico'] = (int) $criteria['topico_id'];
        }
        if (!empty($criteria['estado_id'])) {
            $sql .= ' AND se.estado_id = :e_estado';
            $params['e_estado'] = (int) $criteria['estado_id'];
        }
        if (!empty($criteria['ambiente'])) {
            $sql .= ' AND se.ambiente = :e_ambiente';
            $params['e_ambiente'] = $criteria['ambiente'];
        }
        if (!empty($criteria['solicitante'])) {
            $sql .= ' AND u.nombre_completo LIKE :e_solicitante';
            $params['e_solicitante'] = '%' . $criteria['solicitante'] . '%';
        }
        if (!empty($criteria['fecha_desde'])) {
            $sql .= ' AND se.fecha_creacion >= :e_fecha_desde';
            $params['e_fecha_desde'] = $criteria['fecha_desde'] . ' 00:00:00';
        }
        if (!empty($criteria['fecha_hasta'])) {
            $sql .= ' AND se.fecha_creacion <= :e_fecha_hasta';
            $params['e_fecha_hasta'] = $criteria['fecha_hasta'] . ' 23:59:59';
        }
        $sql .= ' ORDER BY se.fecha_creacion DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = array_merge($results, $stmt->fetchAll());
    }

    usort($results, function ($a, $b) {
        return strcmp($b['fecha_creacion'], $a['fecha_creacion']);
    });

    return $results;
}

function getResenasFuncionalidad(int $solicitudId): array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT rf.id, rf.observaciones, rf.fecha_creacion, rf.fecha_actualizacion,
                                  u.nombre_completo AS ingeniero
                           FROM resenas_funcionalidad rf
                           JOIN usuarios u ON u.id = rf.ingeniero_id
                           WHERE rf.solicitud_id = :id
                           ORDER BY rf.fecha_creacion DESC');
    $stmt->execute(['id' => $solicitudId]);
    return $stmt->fetchAll();
}

function getResenasError(int $solicitudId): array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT re.id, re.observaciones, re.fecha_creacion, re.fecha_actualizacion,
                                  u.nombre_completo AS ingeniero
                           FROM resenas_error re
                           JOIN usuarios u ON u.id = re.ingeniero_id
                           WHERE re.solicitud_id = :id
                           ORDER BY re.fecha_creacion DESC');
    $stmt->execute(['id' => $solicitudId]);
    return $stmt->fetchAll();
}

function getResenaFuncionalidadPorIngeniero(int $solicitudId, int $ingenieroId): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM resenas_funcionalidad WHERE solicitud_id = :solicitud AND ingeniero_id = :ingeniero LIMIT 1');
    $stmt->execute(['solicitud' => $solicitudId, 'ingeniero' => $ingenieroId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getResenaErrorPorIngeniero(int $solicitudId, int $ingenieroId): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM resenas_error WHERE solicitud_id = :solicitud AND ingeniero_id = :ingeniero LIMIT 1');
    $stmt->execute(['solicitud' => $solicitudId, 'ingeniero' => $ingenieroId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function saveResenaFuncionalidad(int $solicitudId, int $ingenieroId, string $observaciones): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT 1 FROM solicitudes_funcionalidad WHERE id = :id AND ingeniero_id = :ingeniero');
    $stmt->execute(['id' => $solicitudId, 'ingeniero' => $ingenieroId]);
    if (!$stmt->fetch()) {
        throw new RuntimeException('Solo el ingeniero asignado puede comentar esta solicitud.');
    }

    $insert = $pdo->prepare('INSERT INTO resenas_funcionalidad (solicitud_id, ingeniero_id, observaciones)
                              VALUES (:solicitud, :ingeniero, :obs)
                              ON DUPLICATE KEY UPDATE observaciones = VALUES(observaciones), fecha_actualizacion = CURRENT_TIMESTAMP');
    $insert->execute([
        'solicitud' => $solicitudId,
        'ingeniero' => $ingenieroId,
        'obs' => $observaciones,
    ]);
}

function saveResenaError(int $solicitudId, int $ingenieroId, string $observaciones): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT 1 FROM solicitudes_error WHERE id = :id AND ingeniero_id = :ingeniero');
    $stmt->execute(['id' => $solicitudId, 'ingeniero' => $ingenieroId]);
    if (!$stmt->fetch()) {
        throw new RuntimeException('Solo el ingeniero asignado puede comentar esta incidencia.');
    }

    $insert = $pdo->prepare('INSERT INTO resenas_error (solicitud_id, ingeniero_id, observaciones)
                              VALUES (:solicitud, :ingeniero, :obs)
                              ON DUPLICATE KEY UPDATE observaciones = VALUES(observaciones), fecha_actualizacion = CURRENT_TIMESTAMP');
    $insert->execute([
        'solicitud' => $solicitudId,
        'ingeniero' => $ingenieroId,
        'obs' => $observaciones,
    ]);
}

function deleteResenaFuncionalidad(int $solicitudId, int $ingenieroId): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM resenas_funcionalidad WHERE solicitud_id = :solicitud AND ingeniero_id = :ingeniero');
    $stmt->execute(['solicitud' => $solicitudId, 'ingeniero' => $ingenieroId]);
}

function deleteResenaError(int $solicitudId, int $ingenieroId): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM resenas_error WHERE solicitud_id = :solicitud AND ingeniero_id = :ingeniero');
    $stmt->execute(['solicitud' => $solicitudId, 'ingeniero' => $ingenieroId]);
}

function fetchDashboardResumen(int $userId, string $rol): array
{
    $pdo = getPDO();
    if ($rol === 'INGENIERO') {
        $stmt = $pdo->prepare('SELECT COUNT(*) AS total, es.nombre AS estado, tipo
                               FROM vw_solicitudes_ingeniero v
                               JOIN estados_solicitud es ON es.nombre = v.estado
                               WHERE v.ingeniero_id = :id
                               GROUP BY es.nombre, tipo');
        $stmt->execute(['id' => $userId]);
        return $stmt->fetchAll();
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) AS total, es.nombre AS estado, tipo
                           FROM (
                                SELECT sf.id, es.nombre, "FUNCIONALIDAD" AS tipo
                                FROM solicitudes_funcionalidad sf
                                JOIN estados_solicitud es ON es.id = sf.estado_id
                                WHERE sf.solicitante_id = :id
                                UNION ALL
                                SELECT se.id, es2.nombre, "ERROR" AS tipo
                                FROM solicitudes_error se
                                JOIN estados_solicitud es2 ON es2.id = se.estado_id
                                WHERE se.solicitante_id = :id
                           ) datos
                           GROUP BY es.nombre, tipo');
    $stmt->execute(['id' => $userId]);
    return $stmt->fetchAll();
}
