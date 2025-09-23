<?php
function validateRegistration(string $nombre, string $email, string $password, string $confirm, string $rol, array $topicos): array
{
    $errors = [];

    if (strlen($nombre) < 3) {
        $errors['nombre'] = 'El nombre debe tener al menos 3 caracteres.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'El correo ingresado no es valido.';
    }

    if ($password !== $confirm) {
        $errors['confirm'] = 'Las contrasenas no coinciden.';
    }

    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors['password'] = 'La contrasena debe tener 8 caracteres, incluir mayuscula, minuscula y numero.';
    }

    if (!in_array($rol, ['USUARIO', 'INGENIERO'], true)) {
        $errors['rol'] = 'Debe seleccionar un rol valido.';
    }

    if ($rol === 'INGENIERO' && count($topicos) === 0) {
        $errors['topicos'] = 'Seleccione al menos un topico para su especialidad (maximo 2).';
    }

    return $errors;
}

function validateLogin(string $email, string $password): array
{
    $errors = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Ingrese un correo valido.';
    }
    if ($password === '') {
        $errors['password'] = 'Ingrese su contrasena.';
    }
    return $errors;
}

function validateFuncionalidad(array $data): array
{
    $errors = [];
    if (strlen($data['titulo'] ?? '') < 10) {
        $errors['titulo'] = 'El titulo debe tener al menos 10 caracteres.';
    }
    if ($data['ambiente'] === '') {
        $errors['ambiente'] = 'Debe indicar el ambiente.';
    }
    if (strlen($data['resumen'] ?? '') < 20) {
        $errors['resumen'] = 'El resumen debe describir claramente la necesidad (minimo 20 caracteres).';
    }
    if (strlen($data['criterios'] ?? '') < 10) {
        $errors['criterios'] = 'Los criterios de aceptacion son obligatorios.';
    }
    if (empty($data['topico_id'])) {
        $errors['topico_id'] = 'Seleccione un topico.';
    }
    return $errors;
}

function validateError(array $data): array
{
    $errors = [];
    if (strlen($data['titulo'] ?? '') < 10) {
        $errors['titulo'] = 'El titulo debe tener al menos 10 caracteres.';
    }
    if ($data['ambiente'] === '') {
        $errors['ambiente'] = 'Debe indicar el ambiente.';
    }
    if (strlen($data['descripcion'] ?? '') < 20) {
        $errors['descripcion'] = 'Describa el error con mas detalle (minimo 20 caracteres).';
    }
    if (empty($data['topico_id'])) {
        $errors['topico_id'] = 'Seleccione un topico.';
    }
    if (empty($data['fecha_publicacion']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['fecha_publicacion'])) {
        $errors['fecha_publicacion'] = 'Fecha de publicacion invalida. Formato esperado: AAAA-MM-DD.';
    }
    return $errors;
}

function validateReview(string $observaciones): array
{
    $errors = [];
    if (strlen(trim($observaciones)) < 5) {
        $errors['observaciones'] = 'La resena debe contener al menos 5 caracteres.';
    }
    return $errors;
}

function validateAdvancedSearch(array $criteria): array
{
    $errors = [];
    if (!empty($criteria['fecha_desde']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $criteria['fecha_desde'])) {
        $errors['fecha_desde'] = 'Fecha desde invalida. Use AAAA-MM-DD.';
    }
    if (!empty($criteria['fecha_hasta']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $criteria['fecha_hasta'])) {
        $errors['fecha_hasta'] = 'Fecha hasta invalida. Use AAAA-MM-DD.';
    }
    return $errors;
}
