<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireLogin();

$user = currentUser();
$section = $_GET['section'] ?? 'dashboard';
$title = 'ZeroPressure - Inicio';
$formErrors = [];
$formValues = [];
$successMessage = flash('success');
$errorMessage = flash('error');
$topics = getAllTopics();
$estados = getAllEstados();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'crear-funcionalidad':
                $payload = [
                    'titulo' => getPost('titulo', ''),
                    'ambiente' => getPost('ambiente', ''),
                    'resumen' => getPost('resumen', ''),
                    'criterios' => getPost('criterios', ''),
                    'topico_id' => (int) getPost('topico_id'),
                    'estado_id' => getEstadoIdByNombre('Nuevo'),
                ];
                $errors = validateFuncionalidad($payload);
                if ($errors) {
                    $formErrors['crear-funcionalidad'] = $errors;
                    $formValues['crear-funcionalidad'] = $payload;
                    $section = 'crear';
                    break;
                }
                createFuncionalidad($payload, (int) $user['id']);
                flash('success', 'Solicitud de funcionalidad creada y asignada automaticamente.');
                $destino = isEngineer() ? 'funcionalidades' : 'mis-funcionalidades';
                redirect('index.php?section=' . $destino);
                break;

            case 'crear-error':
                $payload = [
                    'titulo' => getPost('titulo', ''),
                    'ambiente' => getPost('ambiente', ''),
                    'descripcion' => getPost('descripcion', ''),
                    'fecha_publicacion' => getPost('fecha_publicacion', ''),
                    'topico_id' => (int) getPost('topico_id'),
                    'estado_id' => getEstadoIdByNombre('Nuevo'),
                ];
                $errors = validateError($payload);
                if ($errors) {
                    $formErrors['crear-error'] = $errors;
                    $formValues['crear-error'] = $payload;
                    $section = 'crear';
                    break;
                }
                createErrorSolicitud($payload, (int) $user['id']);
                flash('success', 'Solicitud de error creada y asignada automaticamente.');
                $destino = isEngineer() ? 'errores' : 'mis-errores';
                redirect('index.php?section=' . $destino);
                break;

            case 'actualizar-funcionalidad':
                $id = (int) getPost('id');
                $payload = [
                    'titulo' => getPost('titulo', ''),
                    'ambiente' => getPost('ambiente', ''),
                    'resumen' => getPost('resumen', ''),
                    'criterios' => getPost('criterios', ''),
                    'topico_id' => (int) getPost('topico_id'),
                    'estado_id' => (int) getPost('estado_id'),
                ];
                $errors = validateFuncionalidad($payload);
                if ($errors) {
                    $formErrors['editar-funcionalidad'] = $errors;
                    $formValues['editar-funcionalidad'] = $payload + ['id' => $id];
                    $section = 'editar-funcionalidad';
                    $_GET['id'] = (string) $id;
                    break;
                }
                updateFuncionalidad($id, $payload, (int) $user['id']);
                flash('success', 'Solicitud de funcionalidad actualizada.');
                redirect('index.php?section=mis-funcionalidades');
                break;

            case 'actualizar-error':
                $id = (int) getPost('id');
                $payload = [
                    'titulo' => getPost('titulo', ''),
                    'ambiente' => getPost('ambiente', ''),
                    'descripcion' => getPost('descripcion', ''),
                    'fecha_publicacion' => getPost('fecha_publicacion', ''),
                    'topico_id' => (int) getPost('topico_id'),
                    'estado_id' => (int) getPost('estado_id'),
                ];
                $errors = validateError($payload);
                if ($errors) {
                    $formErrors['editar-error'] = $errors;
                    $formValues['editar-error'] = $payload + ['id' => $id];
                    $section = 'editar-error';
                    $_GET['id'] = (string) $id;
                    break;
                }
                updateErrorSolicitud($id, $payload, (int) $user['id']);
                flash('success', 'Solicitud de error actualizada.');
                redirect('index.php?section=mis-errores');
                break;

            case 'eliminar-funcionalidad':
                $id = (int) getPost('id');
                deleteFuncionalidad($id, (int) $user['id']);
                flash('success', 'Solicitud de funcionalidad eliminada.');
                redirect('index.php?section=mis-funcionalidades');
                break;
            case 'eliminar-error':
                $id = (int) getPost('id');
                deleteErrorSolicitud($id, (int) $user['id']);
                flash('success', 'Solicitud de error eliminada.');
                redirect('index.php?section=mis-errores');
                break;

            case 'guardar-resena':
                $tipo = getPost('tipo', '');
                $solicitudId = (int) getPost('solicitud_id');
                $observaciones = getPost('observaciones', '');
                $errors = validateReview($observaciones);
                if ($errors) {
                    $formErrors['resena'] = $errors;
                    $formValues['resena'] = [
                        'tipo' => $tipo,
                        'solicitud_id' => $solicitudId,
                        'observaciones' => $observaciones,
                    ];
                    $section = 'asignadas';
                    break;
                }
                if ($tipo === 'FUNCIONALIDAD') {
                    saveResenaFuncionalidad($solicitudId, (int) $user['id'], $observaciones);
                } elseif ($tipo === 'ERROR') {
                    saveResenaError($solicitudId, (int) $user['id'], $observaciones);
                }
                flash('success', 'Resena guardada correctamente.');
                redirect('index.php?section=asignadas');
                break;

            case 'eliminar-resena':
                $tipo = getPost('tipo', '');
                $solicitudId = (int) getPost('solicitud_id');
                if ($tipo === 'FUNCIONALIDAD') {
                    deleteResenaFuncionalidad($solicitudId, (int) $user['id']);
                } elseif ($tipo === 'ERROR') {
                    deleteResenaError($solicitudId, (int) $user['id']);
                }
                flash('success', 'Resena eliminada.');
                redirect('index.php?section=asignadas');
                break;
        }
    } catch (Throwable $exception) {
        $errorMessage = $exception->getMessage();
    }
}

$data = [];

switch ($section) {
    case 'dashboard':
        $title = 'Resumen general';
        $data['resumen'] = fetchDashboardResumen((int) $user['id'], $user['rol']);
        break;

    case 'funcionalidades':
        if (!isEngineer()) {
            flash('error', 'Se requiere rol de Ingeniero.');
            redirect('index.php');
        }
        $title = 'Solicitudes de funcionalidad';
        $data['funcionalidades'] = fetchFuncionalidades();
        break;

    case 'errores':
        if (!isEngineer()) {
            flash('error', 'Se requiere rol de Ingeniero.');
            redirect('index.php');
        }
        $title = 'Solicitudes de errores';
        $data['errores'] = fetchErrores();
        break;

    case 'asignadas':
        if (!isEngineer()) {
            flash('error', 'Se requiere rol de Ingeniero.');
            redirect('index.php');
        }
        $title = 'Solicitudes asignadas';
        $data['asignadas'] = fetchAsignadasParaIngeniero((int) $user['id']);
        break;

    case 'mis-funcionalidades':
        $title = 'Mis solicitudes de funcionalidad';
        $data['funcionalidades'] = fetchFuncionalidades(null, (int) $user['id']);
        break;

    case 'mis-errores':
        $title = 'Mis solicitudes de errores';
        $data['errores'] = fetchErrores(null, (int) $user['id']);
        break;

    case 'editar-funcionalidad':
        $title = 'Editar solicitud de funcionalidad';
        $id = (int) ($_GET['id'] ?? 0);
        $registro = getFuncionalidad($id);
        if (!$registro || (int) $registro['solicitante_id'] !== (int) $user['id']) {
            flash('error', 'No existe la solicitud requerida.');
            redirect('index.php?section=mis-funcionalidades');
        }
        if (!isset($formValues['editar-funcionalidad'])) {
            $formValues['editar-funcionalidad'] = [
                'id' => $registro['id'],
                'titulo' => $registro['titulo'],
                'ambiente' => $registro['ambiente'],
                'resumen' => $registro['resumen'],
                'criterios' => $registro['criterios_aceptacion'],
                'topico_id' => $registro['topico_id'],
                'estado_id' => $registro['estado_id'],
            ];
        }
        break;
    case 'editar-error':
        $title = 'Editar solicitud de error';
        $id = (int) ($_GET['id'] ?? 0);
        $registro = getErrorSolicitud($id);
        if (!$registro || (int) $registro['solicitante_id'] !== (int) $user['id']) {
            flash('error', 'No existe la solicitud requerida.');
            redirect('index.php?section=mis-errores');
        }
        if (!isset($formValues['editar-error'])) {
            $formValues['editar-error'] = [
                'id' => $registro['id'],
                'titulo' => $registro['titulo'],
                'ambiente' => $registro['ambiente'],
                'descripcion' => $registro['descripcion'],
                'fecha_publicacion' => $registro['fecha_publicacion'],
                'topico_id' => $registro['topico_id'],
                'estado_id' => $registro['estado_id'],
            ];
        }
        break;

    case 'buscar':
        $title = 'Resultados de busqueda';
        $term = trim($_GET['q'] ?? '');
        if ($term !== '') {
            $data['busqueda'] = globalSearch($term);
        } else {
            $errorMessage = 'Ingrese un texto para buscar.';
        }
        break;

    case 'busqueda':
        $title = 'Busqueda avanzada';
        $criteria = [
            'tipo' => $_GET['tipo'] ?? null,
            'topico_id' => !empty($_GET['topico_id']) ? (int) $_GET['topico_id'] : null,
            'estado_id' => !empty($_GET['estado_id']) ? (int) $_GET['estado_id'] : null,
            'ambiente' => $_GET['ambiente'] ?? null,
            'solicitante' => $_GET['solicitante'] ?? null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
        ];
        $formValues['busqueda'] = $criteria;
        $errors = validateAdvancedSearch($criteria);
        if ($errors) {
            $formErrors['busqueda'] = $errors;
        } elseif (array_filter($criteria)) {
            $data['resultados'] = advancedSearch($criteria);
        }
        break;

    case 'crear':
        $title = 'Crear solicitudes';
        break;

    default:
        $section = 'dashboard';
        $title = 'Resumen general';
        $data['resumen'] = fetchDashboardResumen((int) $user['id'], $user['rol']);
        break;
}

include __DIR__ . '/../templates/header.php';

if ($successMessage): ?>
    <div class="alert success"><?= sanitize($successMessage); ?></div>
<?php endif; ?>
<?php if ($errorMessage): ?>
    <div class="alert error"><?= sanitize($errorMessage); ?></div>
<?php endif; ?>

<?php if ($section === 'dashboard'): ?>
    <section>
        <h2>Resumen de actividad</h2>
        <div class="flex">
            <?php foreach ($data['resumen'] as $fila): ?>
                <div class="card">
                    <strong><?= sanitize($fila['tipo']); ?> - <?= sanitize($fila['estado']); ?></strong>
                    <p>Total: <?= (int) $fila['total']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php elseif ($section === 'funcionalidades'): ?>
    <section>
        <h2>Solicitudes de funcionalidad</h2>
        <table class="table-list">
            <thead>
                <tr>
                    <th>Titulo</th>
                    <th>Ambiente</th>
                    <th>Resumen</th>
                    <th>Criterios</th>
                    <th>Topico</th>
                    <th>Solicitante</th>
                    <th>Estado</th>
                    <th>Ingeniero asignado</th>
                    <th>Creada</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($data['funcionalidades'] as $item): ?>
                <tr>
                    <td><?= sanitize($item['titulo']); ?></td>
                    <td><?= sanitize($item['ambiente']); ?></td>
                    <td><?= sanitize($item['resumen']); ?></td>
                    <td><?= sanitize($item['criterios_aceptacion']); ?></td>
                    <td><?= sanitize($item['topico']); ?></td>
                    <td><?= sanitize($item['solicitante']); ?></td>
                    <td><span class="badge"><?= sanitize($item['estado']); ?></span></td>
                    <td><?= sanitize($item['ingeniero'] ?? 'Pendiente'); ?></td>
                    <td><?= sanitize($item['fecha_creacion']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php elseif ($section === 'errores'): ?>
    <section>
        <h2>Solicitudes de errores</h2>
        <table class="table-list">
            <thead>
                <tr>
                    <th>Titulo</th>
                    <th>Ambiente</th>
                    <th>Descripcion</th>
                    <th>Fecha publicacion</th>
                    <th>Topico</th>
                    <th>Solicitante</th>
                    <th>Estado</th>
                    <th>Ingeniero asignado</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($data['errores'] as $item): ?>
                <tr>
                    <td><?= sanitize($item['titulo']); ?></td>
                    <td><?= sanitize($item['ambiente']); ?></td>
                    <td><?= sanitize($item['descripcion']); ?></td>
                    <td><?= sanitize($item['fecha_publicacion']); ?></td>
                    <td><?= sanitize($item['topico']); ?></td>
                    <td><?= sanitize($item['solicitante']); ?></td>
                    <td><span class="badge"><?= sanitize($item['estado']); ?></span></td>
                    <td><?= sanitize($item['ingeniero'] ?? 'Pendiente'); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php elseif ($section === 'asignadas'): ?>
    <section>
        <h2>Funcionalidades asignadas</h2>
        <?php foreach ($data['asignadas']['funcionalidades'] as $item):
            $resena = getResenaFuncionalidadPorIngeniero((int) $item['id'], (int) $user['id']);
            $texto = $resena['observaciones'] ?? ($formValues['resena']['observaciones'] ?? '');
        ?>
            <article class="card" style="flex:1 1 100%;">
                <h3><?= sanitize($item['titulo']); ?> <span class="badge"><?= sanitize($item['estado']); ?></span></h3>
                <p><strong>Resumen:</strong> <?= sanitize($item['resumen']); ?></p>
                <p><strong>Criterios:</strong> <?= sanitize($item['criterios_aceptacion']); ?></p>
                <form method="post" style="margin-bottom:12px;">
                    <input type="hidden" name="action" value="guardar-resena">
                    <input type="hidden" name="tipo" value="FUNCIONALIDAD">
                    <input type="hidden" name="solicitud_id" value="<?= (int) $item['id']; ?>">
                    <label>Resena</label>
                    <textarea name="observaciones" rows="3"><?= sanitize($texto); ?></textarea>
                    <?php if (!empty($formErrors['resena']['observaciones']) && ($formValues['resena']['solicitud_id'] ?? 0) == $item['id'] && ($formValues['resena']['tipo'] ?? '') === 'FUNCIONALIDAD'): ?>
                        <div class="alert error"><?= sanitize($formErrors['resena']['observaciones']); ?></div>
                    <?php endif; ?>
                    <button type="submit">Guardar resena</button>
                </form>
                <?php if ($resena): ?>
                    <form method="post" onsubmit="return confirm('?Eliminar resena?');">
                        <input type="hidden" name="action" value="eliminar-resena">
                        <input type="hidden" name="tipo" value="FUNCIONALIDAD">
                        <input type="hidden" name="solicitud_id" value="<?= (int) $item['id']; ?>">
                        <button type="submit" class="secondary">Eliminar resena</button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>
    <section>
        <h2>Errores asignados</h2>
        <?php foreach ($data['asignadas']['errores'] as $item):
            $resena = getResenaErrorPorIngeniero((int) $item['id'], (int) $user['id']);
            $texto = $resena['observaciones'] ?? ($formValues['resena']['observaciones'] ?? '');
        ?>
            <article class="card" style="flex:1 1 100%;">
                <h3><?= sanitize($item['titulo']); ?> <span class="badge"><?= sanitize($item['estado']); ?></span></h3>
                <p><strong>Descripcion:</strong> <?= sanitize($item['descripcion']); ?></p>
                <form method="post" style="margin-bottom:12px;">
                    <input type="hidden" name="action" value="guardar-resena">
                    <input type="hidden" name="tipo" value="ERROR">
                    <input type="hidden" name="solicitud_id" value="<?= (int) $item['id']; ?>">
                    <label>Resena</label>
                    <textarea name="observaciones" rows="3"><?= sanitize($texto); ?></textarea>
                    <?php if (!empty($formErrors['resena']['observaciones']) && ($formValues['resena']['solicitud_id'] ?? 0) == $item['id'] && ($formValues['resena']['tipo'] ?? '') === 'ERROR'): ?>
                        <div class="alert error"><?= sanitize($formErrors['resena']['observaciones']); ?></div>
                    <?php endif; ?>
                    <button type="submit">Guardar resena</button>
                </form>
                <?php if ($resena): ?>
                    <form method="post" onsubmit="return confirm('?Eliminar resena?');">
                        <input type="hidden" name="action" value="eliminar-resena">
                        <input type="hidden" name="tipo" value="ERROR">
                        <input type="hidden" name="solicitud_id" value="<?= (int) $item['id']; ?>">
                        <button type="submit" class="secondary">Eliminar resena</button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>
<?php elseif ($section === 'mis-funcionalidades'): ?>
    <section>
        <h2>Mis funcionalidades</h2>
        <table class="table-list">
            <thead>
                <tr>
                    <th>Titulo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($data['funcionalidades'] as $item): ?>
                <tr>
                    <td><?= sanitize($item['titulo']); ?></td>
                    <td><span class="badge"><?= sanitize($item['estado']); ?></span></td>
                    <td>
                        <a href="index.php?section=editar-funcionalidad&id=<?= (int) $item['id']; ?>">Editar</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('?Eliminar solicitud?');">
                            <input type="hidden" name="action" value="eliminar-funcionalidad">
                            <input type="hidden" name="id" value="<?= (int) $item['id']; ?>">
                            <button type="submit" class="secondary">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php elseif ($section === 'mis-errores'): ?>
    <section>
        <h2>Mis errores reportados</h2>
        <table class="table-list">
            <thead>
                <tr>
                    <th>Titulo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($data['errores'] as $item): ?>
                <tr>
                    <td><?= sanitize($item['titulo']); ?></td>
                    <td><span class="badge"><?= sanitize($item['estado']); ?></span></td>
                    <td>
                        <a href="index.php?section=editar-error&id=<?= (int) $item['id']; ?>">Editar</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('?Eliminar incidencia?');">
                            <input type="hidden" name="action" value="eliminar-error">
                            <input type="hidden" name="id" value="<?= (int) $item['id']; ?>">
                            <button type="submit" class="secondary">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php elseif ($section === 'editar-funcionalidad'): ?>
    <section>
        <h2>Editar funcionalidad</h2>
        <?php $values = $formValues['editar-funcionalidad']; ?>
        <form method="post">
            <input type="hidden" name="action" value="actualizar-funcionalidad">
            <input type="hidden" name="id" value="<?= (int) $values['id']; ?>">
            <label>Titulo</label>
            <input type="text" name="titulo" value="<?= sanitize($values['titulo']); ?>">
            <?php if (!empty($formErrors['editar-funcionalidad']['titulo'])): ?><div class="alert error"><?= sanitize($formErrors['editar-funcionalidad']['titulo']); ?></div><?php endif; ?>

            <label>Ambiente</label>
            <input type="text" name="ambiente" value="<?= sanitize($values['ambiente']); ?>">
            <?php if (!empty($formErrors['editar-funcionalidad']['ambiente'])): ?><div class="alert error"><?= sanitize($formErrors['editar-funcionalidad']['ambiente']); ?></div><?php endif; ?>

            <label>Resumen</label>
            <textarea name="resumen" rows="3"><?= sanitize($values['resumen']); ?></textarea>
            <?php if (!empty($formErrors['editar-funcionalidad']['resumen'])): ?><div class="alert error"><?= sanitize($formErrors['editar-funcionalidad']['resumen']); ?></div><?php endif; ?>

            <label>Criterios de aceptacion</label>
            <textarea name="criterios" rows="3"><?= sanitize($values['criterios']); ?></textarea>
            <?php if (!empty($formErrors['editar-funcionalidad']['criterios'])): ?><div class="alert error"><?= sanitize($formErrors['editar-funcionalidad']['criterios']); ?></div><?php endif; ?>

            <label>Topico</label>
            <select name="topico_id">
                <?php foreach ($topics as $topic): ?>
                    <option value="<?= (int) $topic['id']; ?>" <?= (int) $values['topico_id'] === (int) $topic['id'] ? 'selected' : ''; ?>><?= sanitize($topic['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($formErrors['editar-funcionalidad']['topico_id'])): ?><div class="alert error"><?= sanitize($formErrors['editar-funcionalidad']['topico_id']); ?></div><?php endif; ?>

            <label>Estado</label>
            <select name="estado_id">
                <?php foreach ($estados as $estado): ?>
                    <option value="<?= (int) $estado['id']; ?>" <?= (int) $values['estado_id'] === (int) $estado['id'] ? 'selected' : ''; ?>><?= sanitize($estado['nombre']); ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Actualizar</button>
        </form>
    </section>
<?php elseif ($section === 'editar-error'): ?>
    <section>
        <h2>Editar error</h2>
        <?php $values = $formValues['editar-error']; ?>
        <form method="post">
            <input type="hidden" name="action" value="actualizar-error">
            <input type="hidden" name="id" value="<?= (int) $values['id']; ?>">
            <label>Titulo</label>
            <input type="text" name="titulo" value="<?= sanitize($values['titulo']); ?>">
            <?php if (!empty($formErrors['editar-error']['titulo'])): ?><div class="alert error"><?= sanitize($formErrors['editar-error']['titulo']); ?></div><?php endif; ?>

            <label>Ambiente</label>
            <input type="text" name="ambiente" value="<?= sanitize($values['ambiente']); ?>">
            <?php if (!empty($formErrors['editar-error']['ambiente'])): ?><div class="alert error"><?= sanitize($formErrors['editar-error']['ambiente']); ?></div><?php endif; ?>

            <label>Descripcion</label>
            <textarea name="descripcion" rows="3"><?= sanitize($values['descripcion']); ?></textarea>
            <?php if (!empty($formErrors['editar-error']['descripcion'])): ?><div class="alert error"><?= sanitize($formErrors['editar-error']['descripcion']); ?></div><?php endif; ?>

            <label>Fecha de publicacion</label>
            <input type="date" name="fecha_publicacion" value="<?= sanitize($values['fecha_publicacion']); ?>">
            <?php if (!empty($formErrors['editar-error']['fecha_publicacion'])): ?><div class="alert error"><?= sanitize($formErrors['editar-error']['fecha_publicacion']); ?></div><?php endif; ?>

            <label>Topico</label>
            <select name="topico_id">
                <?php foreach ($topics as $topic): ?>
                    <option value="<?= (int) $topic['id']; ?>" <?= (int) $values['topico_id'] === (int) $topic['id'] ? 'selected' : ''; ?>><?= sanitize($topic['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($formErrors['editar-error']['topico_id'])): ?><div class="alert error"><?= sanitize($formErrors['editar-error']['topico_id']); ?></div><?php endif; ?>

            <label>Estado</label>
            <select name="estado_id">
                <?php foreach ($estados as $estado): ?>
                    <option value="<?= (int) $estado['id']; ?>" <?= (int) $values['estado_id'] === (int) $estado['id'] ? 'selected' : ''; ?>><?= sanitize($estado['nombre']); ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Actualizar</button>
        </form>
    </section>
<?php elseif ($section === 'crear'): ?>
    <section>
        <h2>Crear solicitud de funcionalidad</h2>
        <?php $values = $formValues['crear-funcionalidad'] ?? ['titulo' => '', 'ambiente' => '', 'resumen' => '', 'criterios' => '', 'topico_id' => null]; ?>
        <form method="post">
            <input type="hidden" name="action" value="crear-funcionalidad">
            <label>Titulo</label>
            <input type="text" name="titulo" value="<?= sanitize($values['titulo']); ?>">
            <?php if (!empty($formErrors['crear-funcionalidad']['titulo'])): ?><div class="alert error"><?= sanitize($formErrors['crear-funcionalidad']['titulo']); ?></div><?php endif; ?>

            <label>Ambiente</label>
            <input type="text" name="ambiente" value="<?= sanitize($values['ambiente']); ?>">
            <?php if (!empty($formErrors['crear-funcionalidad']['ambiente'])): ?><div class="alert error"><?= sanitize($formErrors['crear-funcionalidad']['ambiente']); ?></div><?php endif; ?>

            <label>Resumen</label>
            <textarea name="resumen" rows="3"><?= sanitize($values['resumen']); ?></textarea>
            <?php if (!empty($formErrors['crear-funcionalidad']['resumen'])): ?><div class="alert error"><?= sanitize($formErrors['crear-funcionalidad']['resumen']); ?></div><?php endif; ?>

            <label>Criterios de aceptacion</label>
            <textarea name="criterios" rows="3"><?= sanitize($values['criterios']); ?></textarea>
            <?php if (!empty($formErrors['crear-funcionalidad']['criterios'])): ?><div class="alert error"><?= sanitize($formErrors['crear-funcionalidad']['criterios']); ?></div><?php endif; ?>

            <label>Topico</label>
            <select name="topico_id">
                <option value="">Seleccione</option>
                <?php foreach ($topics as $topic): ?>
                    <option value="<?= (int) $topic['id']; ?>" <?= (int) ($values['topico_id'] ?? 0) === (int) $topic['id'] ? 'selected' : ''; ?>><?= sanitize($topic['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($formErrors['crear-funcionalidad']['topico_id'])): ?><div class="alert error"><?= sanitize($formErrors['crear-funcionalidad']['topico_id']); ?></div><?php endif; ?>

            <button type="submit">Crear funcionalidad</button>
        </form>
    </section>
    <section>
        <h2>Crear solicitud de error</h2>
        <?php $values = $formValues['crear-error'] ?? ['titulo' => '', 'ambiente' => '', 'descripcion' => '', 'fecha_publicacion' => date('Y-m-d'), 'topico_id' => null]; ?>
        <form method="post">
            <input type="hidden" name="action" value="crear-error">
            <label>Titulo</label>
            <input type="text" name="titulo" value="<?= sanitize($values['titulo']); ?>">
            <?php if (!empty($formErrors['crear-error']['titulo'])): ?><div class="alert error"><?= sanitize($formErrors['crear-error']['titulo']); ?></div><?php endif; ?>

            <label>Ambiente</label>
            <input type="text" name="ambiente" value="<?= sanitize($values['ambiente']); ?>">
            <?php if (!empty($formErrors['crear-error']['ambiente'])): ?><div class="alert error"><?= sanitize($formErrors['crear-error']['ambiente']); ?></div><?php endif; ?>

            <label>Descripcion</label>
            <textarea name="descripcion" rows="3"><?= sanitize($values['descripcion']); ?></textarea>
            <?php if (!empty($formErrors['crear-error']['descripcion'])): ?><div class="alert error"><?= sanitize($formErrors['crear-error']['descripcion']); ?></div><?php endif; ?>

            <label>Fecha de publicacion</label>
            <input type="date" name="fecha_publicacion" value="<?= sanitize($values['fecha_publicacion']); ?>">
            <?php if (!empty($formErrors['crear-error']['fecha_publicacion'])): ?><div class="alert error"><?= sanitize($formErrors['crear-error']['fecha_publicacion']); ?></div><?php endif; ?>

            <label>Topico</label>
            <select name="topico_id">
                <option value="">Seleccione</option>
                <?php foreach ($topics as $topic): ?>
                    <option value="<?= (int) $topic['id']; ?>" <?= (int) ($values['topico_id'] ?? 0) === (int) $topic['id'] ? 'selected' : ''; ?>><?= sanitize($topic['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($formErrors['crear-error']['topico_id'])): ?><div class="alert error"><?= sanitize($formErrors['crear-error']['topico_id']); ?></div><?php endif; ?>

            <button type="submit">Crear error</button>
        </form>
    </section>
<?php elseif ($section === 'buscar'): ?>
    <section>
        <h2>Resultados para "<?= sanitize($_GET['q'] ?? ''); ?>"</h2>
        <h3>Funcionalidades</h3>
        <ul>
            <?php foreach ($data['busqueda']['funcionalidades'] ?? [] as $item): ?>
                <li><?= sanitize($item['titulo']); ?> (<?= sanitize($item['topico']); ?> - <?= sanitize($item['estado']); ?>)</li>
            <?php endforeach; ?>
            <?php if (empty($data['busqueda']['funcionalidades'])): ?><li>No se encontraron funcionalidades.</li><?php endif; ?>
        </ul>
        <h3>Errores</h3>
        <ul>
            <?php foreach ($data['busqueda']['errores'] ?? [] as $item): ?>
                <li><?= sanitize($item['titulo']); ?> (<?= sanitize($item['topico']); ?> - <?= sanitize($item['estado']); ?>)</li>
            <?php endforeach; ?>
            <?php if (empty($data['busqueda']['errores'])): ?><li>No se encontraron errores.</li><?php endif; ?>
        </ul>
    </section>
<?php elseif ($section === 'busqueda'): ?>
    <section>
        <h2>Busqueda avanzada</h2>
        <?php $values = $formValues['busqueda']; ?>
        <form method="get">
            <input type="hidden" name="section" value="busqueda">
            <label>Tipo</label>
            <select name="tipo">
                <option value="">Todos</option>
                <option value="FUNCIONALIDAD" <?= ($values['tipo'] ?? '') === 'FUNCIONALIDAD' ? 'selected' : ''; ?>>Funcionalidad</option>
                <option value="ERROR" <?= ($values['tipo'] ?? '') === 'ERROR' ? 'selected' : ''; ?>>Error</option>
            </select>
            <label>Topico</label>
            <select name="topico_id">
                <option value="">Todos</option>
                <?php foreach ($topics as $topic): ?>
                    <option value="<?= (int) $topic['id']; ?>" <?= (int) ($values['topico_id'] ?? 0) === (int) $topic['id'] ? 'selected' : ''; ?>><?= sanitize($topic['nombre']); ?></option>
                <?php endforeach; ?>
            </select>

            <label>Estado</label>
            <select name="estado_id">
                <option value="">Todos</option>
                <?php foreach ($estados as $estado): ?>
                    <option value="<?= (int) $estado['id']; ?>" <?= (int) ($values['estado_id'] ?? 0) === (int) $estado['id'] ? 'selected' : ''; ?>><?= sanitize($estado['nombre']); ?></option>
                <?php endforeach; ?>
            </select>

            <label>Ambiente</label>
            <input type="text" name="ambiente" value="<?= sanitize($values['ambiente'] ?? ''); ?>">

            <label>Solicitante</label>
            <input type="text" name="solicitante" value="<?= sanitize($values['solicitante'] ?? ''); ?>">

            <label>Fecha desde</label>
            <input type="date" name="fecha_desde" value="<?= sanitize($values['fecha_desde'] ?? ''); ?>">
            <?php if (!empty($formErrors['busqueda']['fecha_desde'])): ?><div class="alert error"><?= sanitize($formErrors['busqueda']['fecha_desde']); ?></div><?php endif; ?>

            <label>Fecha hasta</label>
            <input type="date" name="fecha_hasta" value="<?= sanitize($values['fecha_hasta'] ?? ''); ?>">
            <?php if (!empty($formErrors['busqueda']['fecha_hasta'])): ?><div class="alert error"><?= sanitize($formErrors['busqueda']['fecha_hasta']); ?></div><?php endif; ?>

            <button type="submit">Filtrar</button>
        </form>
    </section>
    <?php if (!empty($data['resultados'])): ?>
        <section>
            <h2>Resultados</h2>
            <table class="table-list">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Titulo</th>
                        <th>Topico</th>
                        <th>Estado</th>
                        <th>Ambiente</th>
                        <th>Solicitante</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($data['resultados'] as $item): ?>
                    <tr>
                        <td><?= sanitize($item['tipo']); ?></td>
                        <td><?= sanitize($item['titulo']); ?></td>
                        <td><?= sanitize($item['topico']); ?></td>
                        <td><?= sanitize($item['estado']); ?></td>
                        <td><?= sanitize($item['ambiente']); ?></td>
                        <td><?= sanitize($item['solicitante']); ?></td>
                        <td><?= sanitize($item['fecha_creacion']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../templates/footer.php'; ?>
