# Tarea 2 - ZeroPressure (PHP + MySQL)

Equipo:
- Felipe Carrasco Patelli - 202473522-5
- Felipe Luengo Bascuan - 202473509-8

## Requisitos previos
- PHP 8.1 o superior con extensiones `pdo_mysql` y `openssl` habilitadas.
- Servidor MySQL 8.0+ con usuario con permisos de creacion de base de datos.
- Servidor web que apunte a la carpeta `PHP/public` (por ejemplo PHP built-in server o Apache).

## Estructura de entrega
```
Tarea2/
  BD/
    schema.sql   -- creacion completa de la base de datos zeropressure_t2
    seed.sql     -- datos iniciales, estados, usuarios, topicos y resenas
  PHP/
    config/      -- configuracion de conexion y bootstrap
    public/      -- punto de entrada de la aplicacion (index.php, login, register)
    src/         -- helpers, acceso a datos y validaciones
    templates/   -- layout compartido
    assets/css/  -- estilos basicos
  PDF/
    normalizacion.md
    normalizacion.pdf
```

## Configuracion
1. Importar esquemas y datos en MySQL:
   ```bash
   mysql -u <usuario> -p < BD/schema.sql
   mysql -u <usuario> -p < BD/seed.sql
   ```
2. Ajustar credenciales en `PHP/config/config.php` (host, usuario y contrasena).
3. Iniciar un servidor embebido desde la carpeta `PHP/public`:
   ```bash
   php -S localhost:8080
   ```
4. Acceder a `http://localhost:8080/login.php`.

## Credenciales de prueba
Las contrasenas siguen el patron `Password123!<n>` segun el orden de insercion:

| Rol        | Nombre              | Correo                     | Contrasena       |
|------------|---------------------|----------------------------|------------------|
| Usuario    | Ana Martinez        | ana@zeropressure.cl        | Password123!1    |
| Usuario    | Bruno Diaz          | bruno@zeropressure.cl      | Password123!2    |
| Ingeniero  | Carla Perez         | carla@zeropressure.cl      | Password123!3    |
| Ingeniero  | Diego Soto          | diego@zeropressure.cl      | Password123!4    |
| Ingeniero  | Elena Rios          | elena@zeropressure.cl      | Password123!5    |
| Usuario    | Fernando Paz        | fernando@zeropressure.cl   | Password123!6    |
| Ingeniero  | Gabriel Mora        | gabriel@zeropressure.cl    | Password123!7    |
| Usuario    | Helena Torres       | helena@zeropressure.cl     | Password123!8    |
| Ingeniero  | Hector Vidal        | hector@zeropressure.cl     | Password123!9    |
| Usuario    | Ines Silva          | ines@zeropressure.cl       | Password123!10   |

## Casos de uso cubiertos
- Registro y login con validaciones de formato y contrasena.
- Navegacion principal con barra de busqueda global y filtros avanzados.
- CRUD completo para solicitudes de funcionalidad y de gestion de errores (con bloqueo cuando el estado es "En Progreso").
- Resumen de actividad y vistas segmentadas por rol (ingenieros versus usuarios solicitantes).
- Asignacion automatica via procedimientos almacenados y triggers basados en topicos.
- Resenas editables por el ingeniero asignado en funcionalidades y errores.
- Vista SQL (`vw_solicitudes_ingeniero`), funcion (`fn_conteo_pendientes`), trigger de bloqueo y procedimiento (`sp_asignar_ingeniero`).

## Supuestos
- Todo ingeniero debe seleccionar hasta dos topicos al registrarse; de lo contrario no podra recibir asignaciones.
- Los estados esperados son: `Nuevo`, `En Revision`, `En Progreso`, `Resuelto`, `Rechazado`.
- Las fechas se manejan en zona horaria del servidor MySQL.
- Los roles disponibles son unicamente `USUARIO` (solicitante) e `INGENIERO`.

## Plan de pruebas manuales
1. Crear una solicitud de funcionalidad con Ana y verificar en "Mis funcionalidades" que aparece en estado `Nuevo` y asignada a un ingeniero segun topico.
2. Iniciar sesion como el ingeniero asignado (ver columna) y comprobar la presencia en "Asignadas". Anadir resena y confirmar persistencia.
3. Cambiar estado de la solicitud a `En Progreso` como solicitante y comprobar que posteriores intentos de edicion/eliminacion levantan mensaje de error.
4. Ejecutar la busqueda rapida desde la barra por un fragmento del titulo creado y confirmar aparicion en ambos listados.
5. Usar la busqueda avanzada filtrando por topico y estado para validar los datos de prueba.
6. Crear un reporte de error y repetir la secuencia anterior, verificando tambien la asignacion automatica y resenas.

## Notas adicionales
- El dataset inicial contiene 12 funcionalidades y 12 errores para cubrir la pauta de revision.
- El procedimiento `sp_asignar_ingeniero` puede ejecutarse manualmente (`CALL sp_asignar_ingeniero('FUNCIONALIDAD', <id>);`) si se desea re-asignar solicitudes creadas fuera de la aplicacion.
- Para limpiar datos de prueba basta ejecutar nuevamente `schema.sql` seguido de `seed.sql`.
