# Checklist de requisitos Tarea 2

- [x] Modelo normalizado a 3FN documentado (`PDF/normalizacion.md`, `PDF/normalizacion.pdf`).
- [x] Base de datos MySQL con claves primarias/foraneas, funcion, vista, procedimiento y triggers (`BD/schema.sql`).
- [x] Dataset con al menos 12 solicitudes de cada tipo y usuarios con roles/credenciales (`BD/seed.sql`).
- [x] Scripts reproducibles para crear y poblar la BD (`BD/schema.sql`, `BD/seed.sql`).
- [x] Aplicacion PHP estructurada con conexion centralizada y proteccion contra inyecciones (uso de PDO y prepared statements en `PHP/src`).
- [x] Registro y login con validaciones y hash seguro (`PHP/public/register.php`, `PHP/public/login.php`, `PHP/src/auth.php`).
- [x] CRUD solicitudes de funcionalidad y errores con restricciones de estado y reseñas de ingeniero (`PHP/src/solicitudes.php`, `PHP/public/index.php`).
- [x] Navegacion por roles, barra de busqueda y busqueda avanzada (`PHP/public/index.php`, `PHP/templates/nav.php`).
- [x] Asignacion automatica via procedimiento almacenado y triggers (`BD/schema.sql`, `PHP/src/solicitudes.php`).
- [x] Documentacion para despliegue, supuestos y plan de pruebas (`README.md`).
