<?php
use PDO;

function findUserByEmail(string $email): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT u.id, u.nombre_completo, u.email, u.password_hash, r.nombre AS rol FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.email = :email AND u.activo = 1 LIMIT 1');
    $stmt->execute(['email' => $email]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function findUserById(int $id): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT u.id, u.nombre_completo, u.email, r.nombre AS rol FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function registerUser(string $nombre, string $email, string $password, string $rol, array $topicos = []): array
{
    $pdo = getPDO();
    $pdo->beginTransaction();

    try {
        $rolStmt = $pdo->prepare('SELECT id FROM roles WHERE nombre = :rol LIMIT 1');
        $rolStmt->execute(['rol' => $rol]);
        $rolRow = $rolStmt->fetch();
        if (!$rolRow) {
            throw new RuntimeException('Rol no valido seleccionado.');
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $insert = $pdo->prepare('INSERT INTO usuarios (nombre_completo, email, password_hash, rol_id) VALUES (:nombre, :email, :hash, :rol_id)');
        $insert->execute([
            'nombre' => $nombre,
            'email' => $email,
            'hash' => $passwordHash,
            'rol_id' => $rolRow['id'],
        ]);

        $userId = (int) $pdo->lastInsertId();

        if ($rol === 'INGENIERO' && $topicos) {
            $topicos = array_slice(array_unique($topicos), 0, 2);
            $link = $pdo->prepare('INSERT INTO usuarios_topicos (usuario_id, topico_id) VALUES (:usuario_id, :topico_id)');
            foreach ($topicos as $topicoId) {
                $link->execute([
                    'usuario_id' => $userId,
                    'topico_id' => (int) $topicoId,
                ]);
            }
        }

        $pdo->commit();

        $user = findUserById($userId);
        if (!$user) {
            throw new RuntimeException('No se pudo recuperar el usuario creado.');
        }
        return $user;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function authenticate(string $email, string $password): ?array
{
    $row = findUserByEmail($email);
    if (!$row) {
        return null;
    }
    if (!password_verify($password, $row['password_hash'])) {
        return null;
    }
    unset($row['password_hash']);
    return $row;
}

function loginUser(array $user): void
{
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'nombre_completo' => $user['nombre_completo'],
        'email' => $user['email'],
        'rol' => $user['rol'],
    ];
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
