<?php
require 'config.php';

if (isset($_SESSION['userName'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $rut = $_POST['rut'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    if (!preg_match('/^\d{7,8}-\d{1}$/', $rut)) {
        die("RUT no válido");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Email no válido");
    }
    if ($firstName == "" || $lastName == "" || $email == "" || $rut == "" || $_POST['password'] == "") {
        die("Todos los campos son obligatorios");
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE rut = :rut or email = :email");
    $stmt->execute(['rut' => $rut, 'email' => $email]);
    if ($stmt->fetchColumn() > 0) {
        die("RUT o email ya registrado");
    }

    $sql = "INSERT INTO usuario (nombre, email, rut, contrasena) VALUES (:nombre, :email, :rut, :contrasena)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['nombre' => $firstName . ' ' . $lastName, 'email' => $email, 'rut' => $rut, 'contrasena' => $password]);

    if ($stmt->rowCount() > 0) {
        header("Location: index.php");
        exit();
    } else {
        die("Error al registrar usuario");
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container login d-flex flex-column text-center">
        <h2>Registro</h2><br>
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
        <form action="register.php" method="POST">
            <label for="firstName" class="form-label">Nombre:</label><br>
            <input type="text" class="form-control" name="firstName" required><br>
            <label for="lastName" class="form-label">Apellido:</label><br>
            <input type="text" class="form-control" name="lastName" required><br>
            <label for="email" class="form-label">Email:</label><br>
            <input type="email" class="form-control" name="email" required><br>
            <label for="rut" class="form-label">Rut:</label><br>
            <input type="text" class="form-control" name="rut" required><br>
            <label for="password" class="form-label">Contraseña:</label><br>
            <input type="password" class="form-control" name="password" required><br><br>
            <button type="submit" class="btn btn-outline-secondary btn-large">Registrarse</button><br>
        </form>
        <br>
        <a href="index.php"><button type="button" class="btn btn-outline-secondary btn-large">Iniciar sesión</button></a>
    </div>
</body>
</html>