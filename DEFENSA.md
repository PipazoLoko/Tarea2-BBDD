# Guion sugerido defensa Tarea 2

## Preparacion previa
1. Levantar MySQL y ejecutar `schema.sql` + `seed.sql`.
2. Iniciar servidor `php -S localhost:8080` dentro de `PHP/public`.
3. Confirmar acceso a `http://localhost:8080/login.php`.

## Flujo propuesto (15-20 min)
1. **Contexto**: Explicar 3FN con apoyo del PDF y destacar entidades clave.
2. **Login/Registro**: Mostrar login con `ana@zeropressure.cl` y registrar un nuevo usuario si preguntan supuestos.
3. **Dashboard**: Resaltar cards por estados y tipos.
4. **CRUD Funcionalidad**:
   - Crear funcionalidad nueva (mostrar asignacion automatica consultando tabla `historial_asignaciones`).
   - Editar y luego intentar modificar cuando el estado pasa a `En Progreso` (capturar error).
   - Eliminar funcionalidad permitida.
5. **CRUD Error**: Repetir flujo incluyendo campo fecha publicacion.
6. **Reseñas**: Ingresar como ingeniero asignado y agregar/editar/eliminar reseña.
7. **Busqueda**:
   - Barra global por fragmento de titulo.
   - Busqueda avanzada filtrando por topico, estado, fechas.
8. **Artefactos SQL**:
   - Ejecutar `SELECT * FROM vw_solicitudes_ingeniero`.
   - Mostrar `SELECT fn_conteo_pendientes(<id>)` para ingeniero cargado.
   - Ejecutar `CALL sp_asignar_ingeniero('FUNCIONALIDAD', <id>)` para re-asignacion manual.
   - Explicar triggers de bloqueo `En Progreso`.
9. **Preguntas**: Tener abiertos `schema.sql` y `solicitudes.php` para navegar a referencias rapidas.

## Evidencias sugeridas
- Tabs abiertas: `schema.sql`, `seed.sql`, `solicitudes.php`, `index.php`.
- Terminal con comandos ejecutados.

## Riesgos / Plan B
- Si el PHP built-in falla, usar XAMPP o WAMP previamente configurado.
- Tener respaldo del zip en unidad externa/drive.
