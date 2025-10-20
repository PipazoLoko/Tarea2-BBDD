DROP DATABASE IF EXISTS tarea2;
CREATE DATABASE tarea2 CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE tarea2;

CREATE TABLE usuario (
  rut            VARCHAR(12)  NOT NULL,
  nombre         VARCHAR(120) NOT NULL,
  email          VARCHAR(180) NOT NULL,
  contrasena     VARCHAR(64)  NOT NULL,
  CONSTRAINT pk_usuario PRIMARY KEY (rut),
  CONSTRAINT uq_usuario_email UNIQUE (email)
);

CREATE TABLE ingeniero (
  rut            VARCHAR(12)  NOT NULL,
  CONSTRAINT pk_ingeniero PRIMARY KEY (rut),
  CONSTRAINT fk_ingeniero_usuario
    FOREIGN KEY (rut) REFERENCES usuario(rut)
    ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE topico (
  topico         VARCHAR(60) NOT NULL,
  CONSTRAINT pk_topico PRIMARY KEY (topico)
);

INSERT INTO topico (topico) VALUES
  ('Backend'),
  ('Seguridad'),
  ('UI/UX');

CREATE TABLE ambiente (
  id_ambiente   VARCHAR(10) NOT NULL,
  CONSTRAINT pk_ambiente PRIMARY KEY (id_ambiente)
);

INSERT INTO ambiente (id_ambiente) VALUES ('Web'), ('Movil');

CREATE TABLE estado (
  estado         VARCHAR(30) NOT NULL,
  CONSTRAINT pk_estado PRIMARY KEY (estado)
);

INSERT INTO estado (estado) VALUES
  ('Abierto'),
  ('En Progreso'),
  ('Resuelto'),
  ('Cerrado');

CREATE TABLE tipo (
  tipo           VARCHAR(30) NOT NULL,
  CONSTRAINT pk_tipo PRIMARY KEY (tipo)
);

INSERT INTO tipo (tipo) VALUES
  ('Funcionalidad'),
  ('Error');

CREATE TABLE ingeniero_especialidad (
  rut            VARCHAR(12)  NOT NULL,
  topico         VARCHAR(60)  NOT NULL,
  CONSTRAINT pk_ing_esp PRIMARY KEY (rut, topico),
  CONSTRAINT fk_ing_esp_ing
    FOREIGN KEY (rut)   REFERENCES ingeniero(rut)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ing_esp_top
    FOREIGN KEY (topico) REFERENCES topico(topico)
    ON DELETE RESTRICT ON UPDATE CASCADE
);

DELIMITER $$
CREATE TRIGGER bi_ing_esp_max2
BEFORE INSERT ON ingeniero_especialidad
FOR EACH ROW
BEGIN
  IF (SELECT COUNT(*) FROM ingeniero_especialidad
      WHERE rut = NEW.rut) >= 2 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Un ingeniero no puede tener más de 2 especialidades';
  END IF;
END$$
DELIMITER ;

CREATE TABLE solicitud (
  id_solicitud       BIGINT NOT NULL AUTO_INCREMENT,
  titulo             VARCHAR(255) NOT NULL,
  tipo               VARCHAR(30)  NOT NULL,
  descripcion        VARCHAR(200) NOT NULL,
  ambiente           VARCHAR(10) NULL,
  topico             VARCHAR(60)  NOT NULL,
  autor              VARCHAR(12)  NOT NULL,
  estado             VARCHAR(30)  NOT NULL,
  fecha_publicacion  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  n_ingenieros       TINYINT NOT NULL DEFAULT 0,
  CONSTRAINT pk_solicitud PRIMARY KEY (id_solicitud),
  CONSTRAINT uq_solicitud_titulo UNIQUE (titulo),
  CONSTRAINT ck_solicitud_titulo_len CHECK (CHAR_LENGTH(titulo) >= 20),
  CONSTRAINT ck_solicitud_desc_len   CHECK (CHAR_LENGTH(descripcion) <= 200),
  CONSTRAINT ck_solicitud_n_ing      CHECK (n_ingenieros BETWEEN 0 AND 3),
  CONSTRAINT fk_sol_tipo     FOREIGN KEY (tipo)     REFERENCES tipo(tipo)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_sol_amb      FOREIGN KEY (ambiente) REFERENCES ambiente(id_ambiente)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_sol_topico   FOREIGN KEY (topico)   REFERENCES topico(topico)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_sol_autor    FOREIGN KEY (autor)    REFERENCES usuario(rut)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_sol_estado   FOREIGN KEY (estado)   REFERENCES estado(estado)
    ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE INDEX ix_solicitud_topico ON solicitud(topico);
CREATE INDEX ix_solicitud_autor  ON solicitud(autor);

CREATE TABLE ingeniero_funcionalidad (
  rut            VARCHAR(12)  NOT NULL,
  id_solicitud   BIGINT       NOT NULL,
  topico         VARCHAR(60)  NOT NULL,
  CONSTRAINT pk_ing_func PRIMARY KEY (rut, id_solicitud),
  CONSTRAINT fk_ing_func_ing
    FOREIGN KEY (rut)        REFERENCES ingeniero(rut)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ing_func_sol
    FOREIGN KEY (id_solicitud) REFERENCES solicitud(id_solicitud)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ing_func_especialidad
    FOREIGN KEY (rut, topico) REFERENCES ingeniero_especialidad(rut, topico)
    ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE INDEX ix_ing_func_sol  ON ingeniero_funcionalidad(id_solicitud);
CREATE INDEX ix_ing_func_top  ON ingeniero_funcionalidad(topico);

DELIMITER $$
CREATE TRIGGER bi_ing_func_limits
BEFORE INSERT ON ingeniero_funcionalidad
FOR EACH ROW
BEGIN
  IF (SELECT n_ingenieros FROM solicitud WHERE id_solicitud = NEW.id_solicitud) >= 3 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La solicitud ya tiene 3 ingenieros asignados';
  END IF;

  IF (SELECT COUNT(*) FROM ingeniero_funcionalidad WHERE rut = NEW.rut) >= 25 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El ingeniero ya está asignado al máximo de 25 solicitudes';
  END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER ai_ing_func_inc
AFTER INSERT ON ingeniero_funcionalidad
FOR EACH ROW
BEGIN
  UPDATE solicitud
    SET n_ingenieros = n_ingenieros + 1
    WHERE id_solicitud = NEW.id_solicitud;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER bd_ing_func_dec_guard
BEFORE DELETE ON ingeniero_funcionalidad
FOR EACH ROW
BEGIN
  IF (SELECT n_ingenieros FROM solicitud WHERE id_solicitud = OLD.id_solicitud) <= 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Inconsistencia: contador ya está en 0';
  END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER ad_ing_func_dec
AFTER DELETE ON ingeniero_funcionalidad
FOR EACH ROW
BEGIN
  UPDATE solicitud
    SET n_ingenieros = n_ingenieros - 1
    WHERE id_solicitud = OLD.id_solicitud;
END$$
DELIMITER ;

CREATE TABLE criterio (
  id_criterio     BIGINT NOT NULL AUTO_INCREMENT,
  id_solicitud    BIGINT NOT NULL,
  descripcion     VARCHAR(255) NOT NULL,
  CONSTRAINT pk_criterio PRIMARY KEY (id_criterio),
  CONSTRAINT fk_criterio_sol
    FOREIGN KEY (id_solicitud) REFERENCES solicitud(id_solicitud)
    ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE resena (
  id_resena      BIGINT NOT NULL AUTO_INCREMENT,
  id_solicitud   BIGINT NOT NULL,
  rut_ingeniero  VARCHAR(12) NOT NULL,
  observacion    TEXT NOT NULL,
  fecha          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT pk_resena PRIMARY KEY (id_resena),
  CONSTRAINT fk_resena_sol FOREIGN KEY (id_solicitud) REFERENCES solicitud(id_solicitud)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_resena_ing FOREIGN KEY (rut_ingeniero) REFERENCES ingeniero(rut)
    ON DELETE CASCADE ON UPDATE CASCADE
);
CREATE INDEX ix_resena_sol ON resena(id_solicitud);

CREATE INDEX ix_criterio_sol ON criterio(id_solicitud);

CREATE OR REPLACE VIEW vw_validaciones AS
SELECT  s.id_solicitud,
        s.titulo,
        s.n_ingenieros,
        (SELECT COUNT(*) FROM criterio c WHERE c.id_solicitud = s.id_solicitud) AS n_criterios
FROM solicitud s;
