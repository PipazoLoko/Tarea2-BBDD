<?php
function sanitize(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function isEngineer(): bool
{
    $user = currentUser();
    return $user !== null && ($user['rol'] ?? null) === 'INGENIERO';
}

function isRequester(): bool
{
    $user = currentUser();
    return $user !== null && ($user['rol'] ?? null) === 'USUARIO';
}

function requireLogin(): void
{
    if (!currentUser()) {
        redirect('login.php');
    }
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function getQuery(string $key, ?string $default = null): ?string
{
    return isset($_GET[$key]) ? trim((string) $_GET[$key]) : $default;
}

function getPost(string $key, ?string $default = null): ?string
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}
