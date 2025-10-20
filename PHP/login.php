<?php
session_start();
require 'config.php';

$errorMessage = '';
$oldEmail = '';

if (isset($_SESSION['userName'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $errorMessage = "Por favor, complete todos los campos.";
        $oldEmail = htmlspecialchars($email);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Email no válido.";
        $oldEmail = htmlspecialchars($email);
    }

    $stmt = $pdo->prepare("SELECT nombre, rut, contrasena FROM usuario WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Intento de login para el email: " . $email . " - encontrado: " . ($user ? 'sí' : 'no'));

    if ($user && password_verify($password, $user['contrasena'])) {
        $_SESSION['userName'] = $user['nombre'];
        $_SESSION['rut'] = $user['rut'];

        $stmt = $pdo->prepare("SELECT * FROM ingeniero WHERE rut = :rut");
        $stmt->execute(['rut' => $user['rut']]);
        $ingeniero = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ingeniero) {
            $_SESSION['tipo'] = 'ingeniero';
        } else {
            $_SESSION['tipo'] = 'usuario';
        }

        header("Location: index.php");
        exit();
    } else {
        $errorMessage = "Email o contraseña incorrectos";
        $oldEmail = htmlspecialchars($email);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login ZeroPressure</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title text-center">Iniciar Sesión</h2>
                        <?php if ($errorMessage): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                        <?php endif; ?>
                        <form action="login.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label" for="email">Email:</label>
                                <input class="form-control" type="email" name="email" value="<?php echo $oldEmail; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="password">Contraseña:</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-outline-secondary">Ingresar</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <a href="register.php" class="btn btn-outline-secondary">Registrarse</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>