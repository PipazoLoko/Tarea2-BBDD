USE zeropressure_t2;

-- Roles
INSERT INTO roles (nombre) VALUES
    ('ADMIN'),
    ('USUARIO'),
    ('INGENIERO');

-- Estados
INSERT INTO estados_solicitud (nombre) VALUES
    ('Nuevo'),
    ('En Revision'),
    ('En Progreso'),
    ('Resuelto'),
    ('Rechazado');

-- Topicos
INSERT INTO topicos (nombre, descripcion) VALUES
    ('Backend', 'Servicios internos, logicas de negocio y APIs.'),
    ('Frontend', 'Interfaz de usuario web y componentes visuales.'),
    ('DevOps', 'Infraestructura, despliegues y monitoreo.'),
    ('Seguridad', 'Controles de autenticacion y auditorias.'),
    ('Integraciones', 'Conectores con plataformas externas.'),
    ('Mobile', 'Aplicaciones moviles iOS y Android.');

-- Usuarios (hashes generados con bcrypt Password123! + sufijo)
INSERT INTO usuarios (nombre_completo, email, password_hash, rol_id) VALUES
    ('Ana Martinez', 'ana@zeropressure.cl', '$2b$12$YDehRrXteQ2sGzIEwwwcDu.ssU4b3mZxyHW3tYHSG6PGo2fYtNtj.', (SELECT id FROM roles WHERE nombre = 'USUARIO')),
    ('Bruno Diaz', 'bruno@zeropressure.cl', '$2b$12$8x7M4dLsmgWHx/BtcKGMp.SCdlDUb1Wo9U3sOjUH0/7k4s1Ji9S92', (SELECT id FROM roles WHERE nombre = 'USUARIO')),
    ('Carla Perez', 'carla@zeropressure.cl', '$2b$12$YHgz0Nh9DIPRmjtCMNTJKeUfaDpbR9/n/Xs.UyMY9jo2cl//SU6OC', (SELECT id FROM roles WHERE nombre = 'INGENIERO')),
    ('Diego Soto', 'diego@zeropressure.cl', '$2b$12$uB2.KrCrT5f175.LH9RBzOOJzpn6WUnRIons0VFakrF6Lh1uiPt/a', (SELECT id FROM roles WHERE nombre = 'INGENIERO')),
    ('Elena Rios', 'elena@zeropressure.cl', '$2b$12$bh15NqhIWkz7shMV3oE9au1A2kuZ8541O3t.ggoE7H.OtAcbPhXZi', (SELECT id FROM roles WHERE nombre = 'INGENIERO')),
    ('Fernando Paz', 'fernando@zeropressure.cl', '$2b$12$s3XpVzDpsFMjCng8bMAsR.qGntWDfe/nR4G/6UOrJlqbdPsXd.uYO', (SELECT id FROM roles WHERE nombre = 'USUARIO')),
    ('Gabriel Mora', 'gabriel@zeropressure.cl', '$2b$12$aiJCOt8uTudCr9Z7KmibRew4hUO4y7FJpjPGwi2C6Uw68GrSCqMBm', (SELECT id FROM roles WHERE nombre = 'INGENIERO')),
    ('Helena Torres', 'helena@zeropressure.cl', '$2b$12$XCdtUg3vOfFRoJ5Ju.l7gOklUUd5y11BLADgWC/QVMdNf7fSogNSa', (SELECT id FROM roles WHERE nombre = 'USUARIO')),
    ('Hector Vidal', 'hector@zeropressure.cl', '$2b$12$3AnKwQx3x7FOgCJXkNGyh.ZnplEWC7yPbO6Mfp2OJAUrHMGoaNeue', (SELECT id FROM roles WHERE nombre = 'INGENIERO')),
    ('Ines Silva', 'ines@zeropressure.cl', '$2b$12$bXeMrMaFXaiMuW6g1HxlHuJUd7WLfwTBSwh0AgUZytnlQj69Is90.', (SELECT id FROM roles WHERE nombre = 'USUARIO'));

-- Especialidades de ingenieros
INSERT INTO usuarios_topicos (usuario_id, topico_id) VALUES
    ((SELECT id FROM usuarios WHERE email = 'carla@zeropressure.cl'), (SELECT id FROM topicos WHERE nombre = 'Backend')),
    ((SELECT id FROM usuarios WHERE email = 'carla@zeropressure.cl'), (SELECT id FROM topicos WHERE nombre = 'Integraciones')),
    ((SELECT id FROM usuarios WHERE email = 'diego@zeropressure.cl'), (SELECT id FROM topicos WHERE nombre = 'Frontend')),
    ((SELECT id FROM usuarios WHERE email = 'diego@zeropressure.cl'), (SELECT id FROM topicos WHERE nombre = 'Seguridad')),
    ((SELECT id FROM usuarios WHERE email = 'elena@zeropressure.cl'), (SELECT id FROM topicos WHERE nombre = 'Backend')),
    ((SELECT id FROM usuarios WHERE email = 'elena@zeropressure.cl'), (SELECT id FROM topicos WHERE nombre = 'DevOps')),
    ((SELECT id FROM usuarios WHERE email = 'gabriel@zeropressure.cl'), (SELECT id FROM topicos WHERE nombre = 'Seguridad')),
    ((SELECT id FROM usuarios WHERE email = 'gabriel@zeropressure.cl'), (SELECT id FROM topicos WHERE nombre = 'Integraciones')),
    ((SELECT id FROM usuarios WHERE email = 'hector@zeropressure.cl'), (SELECT id FROM topicos WHERE nombre = 'DevOps')),
    ((SELECT id FROM usuarios WHERE email = 'hector@zeropressure.cl'), (SELECT id FROM topicos WHERE nombre = 'Mobile'));

-- Solicitudes de funcionalidades (12 registros)
INSERT INTO solicitudes_funcionalidad (titulo, ambiente, resumen, criterios_aceptacion, topico_id, estado_id, solicitante_id)
VALUES
 ('Dashboard de monitoreo en vivo', 'Produccion', 'Los equipos necesitan un dashboard con metricas en tiempo real para detectar cuellos de botella.', 'CA1: Mostrar metricas criticas en 5 segundos; CA2: Exportar reportes PDF; CA3: Alertar por email al romper umbrales.', (SELECT id FROM topicos WHERE nombre = 'Backend'), (SELECT id FROM estados_solicitud WHERE nombre = 'Nuevo'), (SELECT id FROM usuarios WHERE email = 'ana@zeropressure.cl')),
 ('Personalizacion de panel principal', 'QA', 'Los usuarios desean reordenar widgets y seleccionar graficos preferidos en el panel.', 'CA1: Drag and drop de widgets; CA2: Guardar preferencias; CA3: Compatibilidad con navegadores modernos.', (SELECT id FROM topicos WHERE nombre = 'Frontend'), (SELECT id FROM estados_solicitud WHERE nombre = 'En Revision'), (SELECT id FROM usuarios WHERE email = 'bruno@zeropressure.cl')),
 ('API publica para partners', 'Produccion', 'Necesitamos exponer APIs documentadas para partners externos que integren tickets.', 'CA1: Documentacion OpenAPI; CA2: Autenticacion mediante tokens; CA3: Rate limiting.', (SELECT id FROM topicos WHERE nombre = 'Integraciones'), (SELECT id FROM estados_solicitud WHERE nombre = 'Nuevo'), (SELECT id FROM usuarios WHERE email = 'fernando@zeropressure.cl')),
 ('Modo oscuro avanzado', 'QA', 'Solicitan un modo oscuro que respete contraste para accesibilidad AA.', 'CA1: Cumplir WCAG AA; CA2: Recordar preferencia por usuario; CA3: Ajustar graficos automaticos.', (SELECT id FROM topicos WHERE nombre = 'Frontend'), (SELECT id FROM estados_solicitud WHERE nombre = 'En Progreso'), (SELECT id FROM usuarios WHERE email = 'ana@zeropressure.cl')),
 ('Notificaciones push moviles', 'Dev', 'Los usuarios desean push en dispositivos moviles para incidencias criticas.', 'CA1: Compatibilidad iOS y Android; CA2: Configuracion por topico; CA3: Reintentos ante fallo.', (SELECT id FROM topicos WHERE nombre = 'Mobile'), (SELECT id FROM estados_solicitud WHERE nombre = 'Nuevo'), (SELECT id FROM usuarios WHERE email = 'helena@zeropressure.cl')),
 ('Reportes de auditoria', 'Produccion', 'Gerencia necesita reportes detallados de actividad para auditoria interna.', 'CA1: Exportacion CSV; CA2: Filtros por rango de fechas; CA3: Firma digital de reportes.', (SELECT id FROM topicos WHERE nombre = 'Seguridad'), (SELECT id FROM estados_solicitud WHERE nombre = 'En Revision'), (SELECT id FROM usuarios WHERE email = 'bruno@zeropressure.cl')),
 ('Integracion con Jira', 'QA', 'El area de QA requiere sincronizar tickets con Jira automaticamente.', 'CA1: Crear issues desde incidencias; CA2: Sincronizar estados; CA3: Mapear comentarios.', (SELECT id FROM topicos WHERE nombre = 'Integraciones'), (SELECT id FROM estados_solicitud WHERE nombre = 'En Progreso'), (SELECT id FROM usuarios WHERE email = 'ines@zeropressure.cl')),
 ('Personalizacion de SLA por cliente', 'Produccion', 'Clientes premium necesitan SLA diferentes y seguimiento grafico.', 'CA1: Configurar SLA por cliente; CA2: Alertas visuales; CA3: Historico de cumplimiento.', (SELECT id FROM topicos WHERE nombre = 'Backend'), (SELECT id FROM estados_solicitud WHERE nombre = 'Resuelto'), (SELECT id FROM usuarios WHERE email = 'fernando@zeropressure.cl')),
 ('Portal de capacitacion', 'Dev', 'Solicitan un portal con tutoriales y videos para nuevos usuarios.', 'CA1: Soporte de video streaming; CA2: Indice tematico; CA3: Busqueda contextual.', (SELECT id FROM topicos WHERE nombre = 'Frontend'), (SELECT id FROM estados_solicitud WHERE nombre = 'Nuevo'), (SELECT id FROM usuarios WHERE email = 'ana@zeropressure.cl')),
 ('Alertas multi canal', 'Produccion', 'Operaciones quiere alertas por SMS, email y Slack segun criticidad.', 'CA1: Configurar canales; CA2: Trazabilidad de envios; CA3: Reglas por horario.', (SELECT id FROM topicos WHERE nombre = 'DevOps'), (SELECT id FROM estados_solicitud WHERE nombre = 'Nuevo'), (SELECT id FROM usuarios WHERE email = 'bruno@zeropressure.cl')),
 ('Panel de feedback en la app', 'QA', 'Se requiere un panel para recopilar feedback dentro de la plataforma.', 'CA1: Formulario accesible; CA2: Analitica basica; CA3: Exportacion a CSV.', (SELECT id FROM topicos WHERE nombre = 'Frontend'), (SELECT id FROM estados_solicitud WHERE nombre = 'Rechazado'), (SELECT id FROM usuarios WHERE email = 'helena@zeropressure.cl')),
 ('Reasignacion automatica por carga', 'Produccion', 'Solicitan que el sistema redistribuya tickets segun disponibilidad.', 'CA1: Calcular carga en tiempo real; CA2: Loguear reasignaciones; CA3: Alertar a los equipos.', (SELECT id FROM topicos WHERE nombre = 'DevOps'), (SELECT id FROM estados_solicitud WHERE nombre = 'En Revision'), (SELECT id FROM usuarios WHERE email = 'fernando@zeropressure.cl'));

-- Solicitudes de errores (12 registros)
INSERT INTO solicitudes_error (titulo, ambiente, descripcion, fecha_publicacion, topico_id, estado_id, solicitante_id)
VALUES
 ('Error 500 al exportar PDF', 'Produccion', 'Al exportar auditorias a PDF se arroja error 500 despues de 30 segundos.', '2025-08-20', (SELECT id FROM topicos WHERE nombre = 'Backend'), (SELECT id FROM estados_solicitud WHERE nombre = 'En Progreso'), (SELECT id FROM usuarios WHERE email = 'bruno@zeropressure.cl')),
 ('Incidencia con modo oscuro en graficos', 'QA', 'Los graficos de barras quedan ilegibles al activar modo oscuro.', '2025-08-18', (SELECT id FROM topicos WHERE nombre = 'Frontend'), (SELECT id FROM estados_solicitud WHERE nombre = 'En Revision'), (SELECT id FROM usuarios WHERE email = 'ana@zeropressure.cl')),
 ('Falla en notificaciones push iOS', 'Produccion', 'Usuarios iOS no reciben notificaciones cuando la app esta cerrada.', '2025-08-22', (SELECT id FROM topicos WHERE nombre = 'Mobile'), (SELECT id FROM estados_solicitud WHERE nombre = 'Nuevo'), (SELECT id FROM usuarios WHERE email = 'helena@zeropressure.cl')),
 ('Token expira antes de tiempo', 'Dev', 'El token de API expira a los 5 minutos en vez de 60.', '2025-08-19', (SELECT id FROM topicos WHERE nombre = 'Seguridad'), (SELECT id FROM estados_solicitud WHERE nombre = 'Nuevo'), (SELECT id FROM usuarios WHERE email = 'fernando@zeropressure.cl')),
 ('Integracion Jira duplica tickets', 'QA', 'Cada sincronizacion con Jira crea duplicados en ambos sistemas.', '2025-08-17', (SELECT id FROM topicos WHERE nombre = 'Integraciones'), (SELECT id FROM estados_solicitud WHERE nombre = 'En Progreso'), (SELECT id FROM usuarios WHERE email = 'ines@zeropressure.cl')),
 ('Carga lenta del dashboard', 'Produccion', 'El dashboard principal tarda mas de 20 segundos en cargar.', '2025-08-15', (SELECT id FROM topicos WHERE nombre = 'Backend'), (SELECT id FROM estados_solicitud WHERE nombre = 'En Revision'), (SELECT id FROM usuarios WHERE email = 'fernando@zeropressure.cl')),
 ('Fallo de login con cuentas SSO', 'Produccion', 'Usuarios con SSO corporativo reciben error de autenticacion.', '2025-08-16', (SELECT id FROM topicos WHERE nombre = 'Seguridad'), (SELECT id FROM estados_solicitud WHERE nombre = 'Nuevo'), (SELECT id FROM usuarios WHERE email = 'ana@zeropressure.cl')),
 ('Widgets no guardan configuracion', 'QA', 'Los widgets del panel no persisten cambios despues de recargar.', '2025-08-14', (SELECT id FROM topicos WHERE nombre = 'Frontend'), (SELECT id FROM estados_solicitud WHERE nombre = 'Rechazado'), (SELECT id FROM usuarios WHERE email = 'bruno@zeropressure.cl')),
 ('Alertas duplicadas en Slack', 'Produccion', 'Para un mismo incidente se generan dos mensajes en Slack.', '2025-08-21', (SELECT id FROM topicos WHERE nombre = 'DevOps'), (SELECT id FROM estados_solicitud WHERE nombre = 'En Progreso'), (SELECT id FROM usuarios WHERE email = 'fernando@zeropressure.cl')),
 ('Sesion expira al crear solicitud', 'QA', 'La sesion se cierra cuando el usuario crea una solicitud extensa.', '2025-08-18', (SELECT id FROM topicos WHERE nombre = 'Backend'), (SELECT id FROM estados_solicitud WHERE nombre = 'Nuevo'), (SELECT id FROM usuarios WHERE email = 'helena@zeropressure.cl')),
 ('Error 404 en portal de capacitacion', 'Dev', 'Al acceder a tutoriales se obtiene 404 en recursos estaticos.', '2025-08-19', (SELECT id FROM topicos WHERE nombre = 'Frontend'), (SELECT id FROM estados_solicitud WHERE nombre = 'Resuelto'), (SELECT id FROM usuarios WHERE email = 'ana@zeropressure.cl')),
 ('Jobs de recoleccion fallan', 'Produccion', 'Los jobs ETL nocturnos fallan en paso de transformacion.', '2025-08-20', (SELECT id FROM topicos WHERE nombre = 'DevOps'), (SELECT id FROM estados_solicitud WHERE nombre = 'En Revision'), (SELECT id FROM usuarios WHERE email = 'fernando@zeropressure.cl'));

-- Actualizacion de estados para simular ciclo completo
UPDATE solicitudes_funcionalidad
   SET estado_id = (SELECT id FROM estados_solicitud WHERE nombre = 'Resuelto')
 WHERE titulo = 'Personalizacion de panel principal';

UPDATE solicitudes_error
   SET estado_id = (SELECT id FROM estados_solicitud WHERE nombre = 'Resuelto')
 WHERE titulo = 'Integracion Jira duplica tickets';

-- Resenas de ejemplo
INSERT INTO resenas_funcionalidad (solicitud_id, ingeniero_id, observaciones)
SELECT sf.id, sf.ingeniero_id, 'Se valora la claridad de los criterios, pruebas unitarias cubren los escenarios.'
FROM solicitudes_funcionalidad sf
WHERE sf.titulo = 'Dashboard de monitoreo en vivo';

INSERT INTO resenas_error (solicitud_id, ingeniero_id, observaciones)
SELECT se.id, se.ingeniero_id, 'Se aplico fix temporal, pendiente monitorear 48 horas.'
FROM solicitudes_error se
WHERE se.titulo = 'Error 500 al exportar PDF';

