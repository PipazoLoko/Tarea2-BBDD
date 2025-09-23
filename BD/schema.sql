DROP DATABASE IF EXISTS zeropressure_t2;
CREATE DATABASE zeropressure_t2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE zeropressure_t2;

-- =============================================================
-- Tablas base
-- =============================================================

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);

CREATE TABLE topicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL UNIQUE,
    descripcion VARCHAR(255)
);

CREATE TABLE usuarios_topicos (
    usuario_id INT NOT NULL,
    topico_id INT NOT NULL,
    PRIMARY KEY (usuario_id, topico_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (topico_id) REFERENCES topicos(id)
);

CREATE TABLE estados_solicitud (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE
);

-- =============================================================
-- Solicitudes y resenas
-- =============================================================

CREATE TABLE solicitudes_funcionalidad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    ambiente VARCHAR(50) NOT NULL,
    resumen TEXT NOT NULL,
    criterios_aceptacion TEXT NOT NULL,
    topico_id INT NOT NULL,
    estado_id INT NOT NULL,
    solicitante_id INT NOT NULL,
    ingeniero_id INT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (topico_id) REFERENCES topicos(id),
    FOREIGN KEY (estado_id) REFERENCES estados_solicitud(id),
    FOREIGN KEY (solicitante_id) REFERENCES usuarios(id),
    FOREIGN KEY (ingeniero_id) REFERENCES usuarios(id),
    CONSTRAINT chk_titulo_func CHECK (CHAR_LENGTH(titulo) >= 10)
);

CREATE TABLE solicitudes_error (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    ambiente VARCHAR(50) NOT NULL,
    descripcion TEXT NOT NULL,
    fecha_publicacion DATE NOT NULL,
    topico_id INT NOT NULL,
    estado_id INT NOT NULL,
    solicitante_id INT NOT NULL,
    ingeniero_id INT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (topico_id) REFERENCES topicos(id),
    FOREIGN KEY (estado_id) REFERENCES estados_solicitud(id),
    FOREIGN KEY (solicitante_id) REFERENCES usuarios(id),
    FOREIGN KEY (ingeniero_id) REFERENCES usuarios(id)
);

CREATE TABLE resenas_funcionalidad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitud_id INT NOT NULL,
    ingeniero_id INT NOT NULL,
    observaciones TEXT NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (solicitud_id) REFERENCES solicitudes_funcionalidad(id) ON DELETE CASCADE,
    FOREIGN KEY (ingeniero_id) REFERENCES usuarios(id),
    UNIQUE KEY uq_resena_func (solicitud_id, ingeniero_id)
);

CREATE TABLE resenas_error (
    id INT AUTO_INCREMENT PRIMARY KEY,
    solicitud_id INT NOT NULL,
    ingeniero_id INT NOT NULL,
    observaciones TEXT NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (solicitud_id) REFERENCES solicitudes_error(id) ON DELETE CASCADE,
    FOREIGN KEY (ingeniero_id) REFERENCES usuarios(id),
    UNIQUE KEY uq_resena_error (solicitud_id, ingeniero_id)
);

CREATE TABLE historial_asignaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('FUNCIONALIDAD','ERROR') NOT NULL,
    solicitud_id INT NOT NULL,
    ingeniero_id INT NOT NULL,
    fecha_asignacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_historial_ingeniero (ingeniero_id)
);

-- =============================================================
-- Funcion, vista, procedimiento, triggers
-- =============================================================

DELIMITER $$

CREATE FUNCTION fn_conteo_pendientes(p_ingeniero INT) RETURNS INT
READS SQL DATA
BEGIN
    DECLARE total INT;
    SELECT COALESCE(SUM(cnt), 0) INTO total
    FROM (
        SELECT COUNT(*) AS cnt
        FROM solicitudes_funcionalidad sf
        JOIN estados_solicitud es ON es.id = sf.estado_id
        WHERE sf.ingeniero_id = p_ingeniero
          AND es.nombre IN ('Nuevo', 'En Revision', 'En Progreso')
        UNION ALL
        SELECT COUNT(*) AS cnt
        FROM solicitudes_error se
        JOIN estados_solicitud es2 ON es2.id = se.estado_id
        WHERE se.ingeniero_id = p_ingeniero
          AND es2.nombre IN ('Nuevo', 'En Revision', 'En Progreso')
    ) pendientes;
    RETURN total;
END $$

CREATE PROCEDURE sp_asignar_ingeniero(IN p_tipo ENUM('FUNCIONALIDAD','ERROR'), IN p_solicitud_id INT)
BEGIN
    DECLARE v_topico INT;
    DECLARE v_ingeniero INT;

    asignacion: BEGIN
        IF p_tipo = 'FUNCIONALIDAD' THEN
            SELECT topico_id INTO v_topico
            FROM solicitudes_funcionalidad
            WHERE id = p_solicitud_id;
        ELSE
            SELECT topico_id INTO v_topico
            FROM solicitudes_error
            WHERE id = p_solicitud_id;
        END IF;

        IF v_topico IS NULL THEN
            LEAVE asignacion;
        END IF;

        SELECT u.id INTO v_ingeniero
        FROM usuarios_topicos ut
        JOIN usuarios u ON u.id = ut.usuario_id
        JOIN roles r ON r.id = u.rol_id
        WHERE ut.topico_id = v_topico
          AND r.nombre = 'INGENIERO'
          AND u.activo = 1
        ORDER BY fn_conteo_pendientes(u.id) ASC, u.fecha_creacion ASC
        LIMIT 1;

        IF v_ingeniero IS NULL THEN
            LEAVE asignacion;
        END IF;

        IF p_tipo = 'FUNCIONALIDAD' THEN
            UPDATE solicitudes_funcionalidad
               SET ingeniero_id = v_ingeniero
             WHERE id = p_solicitud_id;
        ELSE
            UPDATE solicitudes_error
               SET ingeniero_id = v_ingeniero
             WHERE id = p_solicitud_id;
        END IF;

        INSERT INTO historial_asignaciones(tipo, solicitud_id, ingeniero_id)
        VALUES (p_tipo, p_solicitud_id, v_ingeniero);
    END;
END $$

DELIMITER ;

CREATE VIEW vw_solicitudes_ingeniero AS
SELECT 'FUNCIONALIDAD' AS tipo,
       sf.id,
       sf.titulo,
       es.nombre AS estado,
       sf.ingeniero_id,
       sf.fecha_creacion,
       sf.fecha_actualizacion
FROM solicitudes_funcionalidad sf
JOIN estados_solicitud es ON es.id = sf.estado_id
UNION ALL
SELECT 'ERROR' AS tipo,
       se.id,
       se.titulo,
       es2.nombre AS estado,
       se.ingeniero_id,
       se.fecha_creacion,
       se.fecha_actualizacion
FROM solicitudes_error se
JOIN estados_solicitud es2 ON es2.id = se.estado_id;

DELIMITER $$

CREATE TRIGGER trg_asignar_funcionalidad
AFTER INSERT ON solicitudes_funcionalidad
FOR EACH ROW
BEGIN
    CALL sp_asignar_ingeniero('FUNCIONALIDAD', NEW.id);
END $$

CREATE TRIGGER trg_asignar_error
AFTER INSERT ON solicitudes_error
FOR EACH ROW
BEGIN
    CALL sp_asignar_ingeniero('ERROR', NEW.id);
END $$

CREATE TRIGGER trg_func_no_delete_en_progreso
BEFORE DELETE ON solicitudes_funcionalidad
FOR EACH ROW
BEGIN
    IF OLD.estado_id = (SELECT id FROM estados_solicitud WHERE nombre = 'En Progreso') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede eliminar una funcionalidad en progreso.';
    END IF;
END $$

CREATE TRIGGER trg_error_no_delete_en_progreso
BEFORE DELETE ON solicitudes_error
FOR EACH ROW
BEGIN
    IF OLD.estado_id = (SELECT id FROM estados_solicitud WHERE nombre = 'En Progreso') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede eliminar un error en progreso.';
    END IF;
END $$

DELIMITER ;

-- Indices de apoyo
CREATE INDEX idx_func_estado ON solicitudes_funcionalidad (estado_id);
CREATE INDEX idx_error_estado ON solicitudes_error (estado_id);
CREATE INDEX idx_func_ingeniero ON solicitudes_funcionalidad (ingeniero_id);
CREATE INDEX idx_error_ingeniero ON solicitudes_error (ingeniero_id);

