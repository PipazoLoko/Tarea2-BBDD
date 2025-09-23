<?php
require_once __DIR__ . '/../config/bootstrap.php';

if (currentUser()) {
    redirect('index.php');
}

$topics = getAllTopics();
$errors = [];
$nombre = '';
$email = '';
$rol = 'USUARIO';
$topicosSeleccionados = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = getPost('nombre', '');
    $email = getPost('email', '');
    $password = getPost('password', '');
    $confirm = getPost('confirm', '');
    $rol = getPost('rol', 'USUARIO');
    $topicosSeleccionados = array_map('intval', $_POST['topicos'] ?? []);

    if ($rol === 'INGENIERO' && count($topicosSeleccionados) > 2) {
        $errors['topicos'] = 'Seleccione hasta 2 topicos como maximo.';
    }

    $errors = array_merge($errors, validateRegistration($nombre, $email, $password, $confirm, $rol, $topicosSeleccionados));

    if (!$errors && findUserByEmail($email)) {
        $errors['email'] = 'El correo ya esta registrado.';
    }

    if (!$errors) {
        try {
            $user = registerUser($nombre, $email, $password, $rol, $topicosSeleccionados);
            loginUser($user);
            flash('success', 'Registro completado correctamente.');
            redirect('index.php');
        } catch (Throwable $exception) {
            $errors['general'] = 'No se pudo registrar el usuario: ' . $exception->getMessage();
        }
    }
}

$title = 'Registro de usuarios';
include __DIR__ . '/../templates/header.php';
?>
<section>
    <h2>Registro</h2>
    <p>Completa tus datos para solicitar funcionalidades o resolver incidencias.</p>
    <?php if (!empty($errors['general'])): ?>
        <div class="alert error"><?= sanitize($errors['general']); ?></div>
    <?php endif; ?>
    <form method="post" novalidate>
        <label for="nombre">Nombre completo</label>
        <input type="text" id="nombre" name="nombre" value="<?= sanitize($nombre); ?>" required>
        <?php if (!empty($errors['nombre'])): ?><div class="alert error"><?= sanitize($errors['nombre']); ?></div><?php endif; ?>

        <label for="email">Correo institucional</label>
        <input type="email" id="email" name="email" value="<?= sanitize($email); ?>" required>
        <?php if (!empty($errors['email'])): ?><div class="alert error"><?= sanitize($errors['email']); ?></div><?php endif; ?>

        <label for="password">Contrasena</label>
        <input type="password" id="password" name="password" required>
        <?php if (!empty($errors['password'])): ?><div class="alert error"><?= sanitize($errors['password']); ?></div><?php endif; ?>

        <label for="confirm">Confirmar contrasena</label>
        <input type="password" id="confirm" name="confirm" required>
        <?php if (!empty($errors['confirm'])): ?><div class="alert error"><?= sanitize($errors['confirm']); ?></div><?php endif; ?>

        <label for="rol">Rol</label>
        <select name="rol" id="rol">
            <option value="USUARIO" <?= $rol === 'USUARIO' ? 'selected' : ''; ?>>Usuario solicitante</option>
            <option value="INGENIERO" <?= $rol === 'INGENIERO' ? 'selected' : ''; ?>>Ingeniero</option>
        </select>
        <?php if (!empty($errors['rol'])): ?><div class="alert error"><?= sanitize($errors['rol']); ?></div><?php endif; ?>

        <div id="topicos-wrapper" style="margin-top:12px;">
            <label for="topicos">Topicos (solo ingenieros, maximo 2)</label>
            <select name="topicos[]" id="topicos" multiple size="<?= count($topics); ?>">
                <?php foreach ($topics as $topic): ?>
                    <option value="<?= (int) $topic['id']; ?>" <?= in_array((int) $topic['id'], $topicosSeleccionados, true) ? 'selected' : ''; ?>><?= sanitize($topic['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['topicos'])): ?><div class="alert error"><?= sanitize($errors['topicos']); ?></div><?php endif; ?>
        </div>

        <button type="submit">Registrarme</button>
    </form>
    <p>?Ya tienes cuenta? <a href="login.php">Inicia sesion</a>.</p>
</section>
<?php include __DIR__ . '/../templates/footer.php'; ?>
