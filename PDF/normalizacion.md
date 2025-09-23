# Normalizacion hacia 3FN - Plataforma ZeroPressure

## 1. Levantamiento de requisitos

Las vistas de usuario definidas en la Tarea 2 exigen administrar solicitudes de funcionalidades y de gestion de errores. Cada solicitud se relaciona con:

- Datos generales (titulo, resumen, ambiente o contexto, descripcion del problema).
- Estado del flujo de trabajo (Nuevo, En revision, En progreso, Resuelto, Rechazado).
- Informacion de categorizacion (topico).
- Usuarios involucrados (solicitante y, de existir, ingeniero asignado) y sus resenas.

El modelo previo de la Tarea 1 consolidaba todo en una sola tabla `Solicitudes` con repeticiones para comentarios y topicos. A partir de ese insumo se completa la normalizacion.

## 2. Tabla no normalizada (0NF)

`SolicitudesBrutas`

| SolicitudID | Tipo | Titulo | Ambiente | Resumen | CriteriosAceptacion | DescripcionError | FechaPublicacion | Topicos | Estado | UsuarioSolicitante | CorreoSolicitante | IngenieroAsignado | Comentarios |
|-------------|------|--------|----------|---------|---------------------|------------------|------------------|---------|--------|--------------------|-------------------|-------------------|-------------|

- `Topicos` almacena una lista separada por comas.
- `Comentarios` contiene pares (fecha, autor, texto) repetidos.
- La columna `IngenieroAsignado` admite vacio o multiples nombres.
- Existe mezcla de atributos propios de funcionalidades y de errores.

## 3. Primera Forma Normal (1FN)

Requisitos: eliminar grupos repetidos y asegurar valores atomicos.

Se separan topicos y comentarios en tablas hijas, y se aislan columnas especificas segun el tipo de solicitud.

Tablas propuestas en 1FN:

1. `Solicitud` (`SolicitudID`, `Tipo`, `Titulo`, `Estado`, `UsuarioSolicitanteID`, `IngenieroAsignadoID`, `FechaCreacion`).
2. `SolicitudFuncionalidadDetalle` (`SolicitudID`, `Ambiente`, `Resumen`, `CriteriosAceptacion`).
3. `SolicitudErrorDetalle` (`SolicitudID`, `Ambiente`, `DescripcionError`, `FechaPublicacion`, `Autor`).
4. `SolicitudTopico` (`SolicitudID`, `TopicoNombre`).
5. `SolicitudComentario` (`SolicitudID`, `Autor`, `FechaComentario`, `Texto`).
6. `Usuario` (`UsuarioID`, `Nombre`, `Correo`, `Rol`, ...).

## 4. Segunda Forma Normal (2FN)

Requisitos: eliminar dependencias parciales en claves compuestas.

- En `SolicitudComentario`, la clave compuesta (`SolicitudID`, `FechaComentario`, `Autor`) hacia depender `Autor` de si mismo. Se crea `ComentarioID` como clave primaria y se reemplaza por una clave artificial.
- En `SolicitudTopico`, dependencias del topico hacia descripciones se mueven a una nueva entidad `Topico`.
- Se crea `EstadoSolicitud` para desprender la descripcion del estado.

Tablas en 2FN:

1. `Solicitud` (`SolicitudID`, `Tipo`, `EstadoID`, `UsuarioSolicitanteID`, `IngenieroAsignadoID`, `FechaCreacion`, `FechaActualizacion`).
2. `DetalleFuncionalidad` (`SolicitudID`, `Titulo`, `Ambiente`, `Resumen`, `CriteriosAceptacion`).
3. `DetalleError` (`SolicitudID`, `Titulo`, `Ambiente`, `DescripcionError`, `FechaPublicacion`).
4. `SolicitudTopico` (`SolicitudID`, `TopicoID`).
5. `Comentario` (`ComentarioID`, `SolicitudID`, `AutorID`, `Texto`, `FechaComentario`).
6. `Topico` (`TopicoID`, `Nombre`, `Descripcion`).
7. `EstadoSolicitud` (`EstadoID`, `Nombre`).
8. `Usuario` (`UsuarioID`, `Nombre`, `Correo`, `RolID`, `HashPassword`, `FechaRegistro`).
9. `Rol` (`RolID`, `Nombre`).

## 5. Tercera Forma Normal (3FN)

Requisitos: eliminar dependencias transitivas.

- Los datos de contacto del ingeniero dependen de `Usuario`, por lo que permanecen alli.
- Se separa la asignacion de topicos por usuario para no repetir pares (`UsuarioID`, `TopicoID`).
- Los comentarios requieren distinguir solicitudes de funcionalidades y de errores; se crean tablas especializadas para preservar integridad referencial.
- Se introducen claves sustitutas enteras en todas las relaciones para facilitar las referencias.

Tablas finales en 3FN:

1. `roles` (`id`, `nombre`).
2. `usuarios` (`id`, `nombre_completo`, `email`, `password_hash`, `rol_id`, `activo`, `fecha_creacion`).
3. `topicos` (`id`, `nombre`, `descripcion`).
4. `usuarios_topicos` (`usuario_id`, `topico_id`).
5. `estados_solicitud` (`id`, `nombre`).
6. `solicitudes_funcionalidad` (`id`, `titulo`, `ambiente`, `resumen`, `criterios_aceptacion`, `topico_id`, `estado_id`, `solicitante_id`, `ingeniero_id`, `fecha_creacion`, `fecha_actualizacion`).
7. `solicitudes_error` (`id`, `titulo`, `ambiente`, `descripcion`, `fecha_publicacion`, `topico_id`, `estado_id`, `solicitante_id`, `ingeniero_id`, `fecha_creacion`, `fecha_actualizacion`).
8. `resenas_funcionalidad` (`id`, `solicitud_id`, `ingeniero_id`, `observaciones`, `fecha_creacion`, `fecha_actualizacion`).
9. `resenas_error` (`id`, `solicitud_id`, `ingeniero_id`, `observaciones`, `fecha_creacion`, `fecha_actualizacion`).
10. `historial_asignaciones` (`id`, `solicitud_tipo`, `solicitud_id`, `ingeniero_id`, `fecha_asignacion`).

Cada tabla tiene dependencias unicamente respecto de su clave primaria. Se mantiene integridad referencial mediante claves foraneas explicadas en el script DDL.

## 6. Supuestos relevantes

- Un usuario pertenece a un unico rol (`Usuario` o `Ingeniero`); el rol determina los permisos en la aplicacion.
- Un ingeniero puede estar asociado a multiples topicos mediante `usuarios_topicos`.
- Las solicitudes deben tener siempre un topico valido y un estado.
- Las resenas solo las crean ingenieros sobre solicitudes asignadas.
- La asignacion automatica elegira un ingeniero con afinidad al topico y con la menor cantidad de solicitudes activas.

## 7. Conclusion

El modelo en 3FN permite cumplir los requerimientos de la Tarea 2 evitando redundancias y facilitando la implementacion de integridad, triggers y procedimientos almacenados solicitados. Las tablas resultantes serviran de base para los scripts SQL que acompanan la entrega.
