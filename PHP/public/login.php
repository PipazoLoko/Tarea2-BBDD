<?php
require_once __DIR__ . '/../config/bootstrap.php';

if (currentUser()) {
    redirect('index.php');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = getPost('email', '');
    $password = getPost('password', '');
    $errors = validateLogin($email, $password);

    if (!$errors) {
        $user = authenticate($email, $password);
        if ($user) {
            loginUser($user);
            flash('success', 'Inicio de sesion exitoso.');
            redirect('index.php');
        } else {
            $errors['general'] = 'Correo o contrasena no son validos.';
        }
    }
}

$title = 'Ingresar a ZeroPressure';
include __DIR__ . '/../templates/header.php';
?>
<section>
    <?php if ($msg = flash('success')): ?>
        <div class="alert success"><?= sanitize($msg); ?></div>
    <?php endif; ?>
    <h2>Ingresar</h2>
    <?php if (!empty($errors['general'])): ?>
        <div class="alert error"><?= sanitize($errors['general']); ?></div>
    <?php endif; ?>
    <form method="post" novalidate>
        <label for="email">Correo</label>
        <input type="email" name="email" id="email" value="<?= sanitize($email); ?>" required>
        <?php if (!empty($errors['email'])): ?><div class="alert error"><?= sanitize($errors['email']); ?></div><?php endif; ?>

        <label for="password">Contrasena</label>
        <input type="password" name="password" id="password" required>
        <?php if (!empty($errors['password'])): ?><div class="alert error"><?= sanitize($errors['password']); ?></div><?php endif; ?>

        <button type="submit">Entrar</button>
    </form>
    <p>?No tienes cuenta? <a href="register.php">Registrate aqui</a>.</p>
</section>
<?php include __DIR__ . '/../templates/footer.php'; ?>
