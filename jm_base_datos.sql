-- =============================================
-- BASE DE DATOS: jm_parcial_plp3
-- PROYECTO: Piedra, Papel o Tijera
-- DESARROLLADOR: JM
-- FECHA: 25/11/2025
-- =============================================

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS jm_parcial_plp3 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Seleccionar la base de datos
USE jm_parcial_plp3;

-- =============================================
-- TABLA: jm_usuarios
-- Almacena la información de los usuarios registrados
-- =============================================
CREATE TABLE IF NOT EXISTS jm_usuarios (
    jm_id INT PRIMARY KEY AUTO_INCREMENT,
    jm_nombre_usuario VARCHAR(50) UNIQUE NOT NULL,
    jm_email VARCHAR(100) UNIQUE NOT NULL,
    jm_password_hash VARCHAR(255) NOT NULL,
    jm_fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    jm_ultimo_login TIMESTAMP NULL,
    jm_activo BOOLEAN DEFAULT TRUE,
    
    -- Índices para mejorar rendimiento
    INDEX idx_jm_nombre_usuario (jm_nombre_usuario),
    INDEX idx_jm_email (jm_email),
    INDEX idx_jm_fecha_registro (jm_fecha_registro)
) ENGINE=InnoDB;

-- =============================================
-- TABLA: jm_partidas
-- Registra todas las partidas jugadas
-- =============================================
CREATE TABLE IF NOT EXISTS jm_partidas (
    jm_id INT PRIMARY KEY AUTO_INCREMENT,
    jm_usuario_id INT NOT NULL,
    jm_eleccion_usuario ENUM('piedra', 'papel', 'tijera') NOT NULL,
    jm_eleccion_computadora ENUM('piedra', 'papel', 'tijera') NOT NULL,
    jm_resultado ENUM('ganaste', 'perdiste', 'empate') NOT NULL,
    jm_fecha_partida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Clave foránea para relación con usuarios
    FOREIGN KEY (jm_usuario_id) 
        REFERENCES jm_usuarios(jm_id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    -- Índices para consultas frecuentes
    INDEX idx_jm_usuario_id (jm_usuario_id),
    INDEX idx_jm_fecha_partida (jm_fecha_partida),
    INDEX idx_jm_resultado (jm_resultado),
    INDEX idx_jm_usuario_fecha (jm_usuario_id, jm_fecha_partida)
) ENGINE=InnoDB;

-- =============================================
-- TABLA: jm_estadisticas
-- Estadísticas acumuladas por usuario
-- =============================================
CREATE TABLE IF NOT EXISTS jm_estadisticas (
    jm_id INT PRIMARY KEY AUTO_INCREMENT,
    jm_usuario_id INT UNIQUE NOT NULL,
    jm_victorias INT DEFAULT 0,
    jm_derrotas INT DEFAULT 0,
    jm_empates INT DEFAULT 0,
    jm_total_partidas INT DEFAULT 0,
    jm_mejor_racha INT DEFAULT 0,
    jm_ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Clave foránea
    FOREIGN KEY (jm_usuario_id) 
        REFERENCES jm_usuarios(jm_id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    -- Índices
    INDEX idx_jm_usuario_id (jm_usuario_id),
    INDEX idx_jm_victorias (jm_victorias),
    INDEX idx_jm_total_partidas (jm_total_partidas)
) ENGINE=InnoDB;

-- =============================================
-- TABLA: jm_rankings
-- Sistema de ranking y puntuaciones
-- =============================================
CREATE TABLE IF NOT EXISTS jm_rankings (
    jm_id INT PRIMARY KEY AUTO_INCREMENT,
    jm_usuario_id INT UNIQUE NOT NULL,
    jm_puntuacion INT DEFAULT 0,
    jm_posicion INT DEFAULT 0,
    jm_ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Clave foránea
    FOREIGN KEY (jm_usuario_id) 
        REFERENCES jm_usuarios(jm_id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    -- Índices para ranking
    INDEX idx_jm_usuario_id (jm_usuario_id),
    INDEX idx_jm_puntuacion (jm_puntuacion DESC),
    INDEX idx_jm_posicion (jm_posicion),
    INDEX idx_jm_puntuacion_posicion (jm_puntuacion DESC, jm_posicion ASC)
) ENGINE=InnoDB;

-- =============================================
-- DATOS DE EJEMPLO PARA PRUEBAS
-- =============================================

-- Insertar usuarios de ejemplo (password: "password" encriptado)
INSERT IGNORE INTO jm_usuarios (jm_nombre_usuario, jm_email, jm_password_hash) VALUES
('ProPlayer123', 'pro@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('TijerasMaster', 'tijeras@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('PiedraPro', 'piedra@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('PapelGamer', 'papel@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('NuevoJugador', 'nuevo@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Champion', 'champ@ejemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insertar estadísticas de ejemplo
INSERT IGNORE INTO jm_estadisticas (jm_usuario_id, jm_victorias, jm_derrotas, jm_empates, jm_total_partidas, jm_mejor_racha) VALUES
(1, 87, 10, 3, 100, 15),   -- ProPlayer123: 87% victorias
(2, 82, 15, 3, 100, 12),   -- TijerasMaster: 82% victorias
(3, 79, 18, 3, 100, 10),   -- PiedraPro: 79% victorias
(4, 75, 22, 3, 100, 8),    -- PapelGamer: 75% victorias
(5, 68, 28, 4, 100, 6),    -- NuevoJugador: 68% victorias
(6, 92, 5, 3, 100, 20);    -- Champion: 92% victorias

-- Insertar datos de ranking
INSERT IGNORE INTO jm_rankings (jm_usuario_id, jm_puntuacion, jm_posicion) VALUES
(6, 1250, 1),  -- Champion
(1, 1150, 2),  -- ProPlayer123
(2, 1050, 3),  -- TijerasMaster
(3, 980, 4),   -- PiedraPro
(4, 920, 5),   -- PapelGamer
(5, 850, 6);   -- NuevoJugador

-- Insertar partidas de ejemplo para el usuario 1 (ProPlayer123)
INSERT IGNORE INTO jm_partidas (jm_usuario_id, jm_eleccion_usuario, jm_eleccion_computadora, jm_resultado, jm_fecha_partida) VALUES
(1, 'piedra', 'tijera', 'ganaste', DATE_SUB(NOW(), INTERVAL 10 MINUTE)),
(1, 'papel', 'piedra', 'ganaste', DATE_SUB(NOW(), INTERVAL 8 MINUTE)),
(1, 'tijera', 'papel', 'ganaste', DATE_SUB(NOW(), INTERVAL 6 MINUTE)),
(1, 'piedra', 'piedra', 'empate', DATE_SUB(NOW(), INTERVAL 4 MINUTE)),
(1, 'tijera', 'piedra', 'perdiste', DATE_SUB(NOW(), INTERVAL 2 MINUTE)),
(1, 'papel', 'tijera', 'perdiste', NOW());

-- Insertar partidas de ejemplo para el usuario 2 (TijerasMaster)
INSERT IGNORE INTO jm_partidas (jm_usuario_id, jm_eleccion_usuario, jm_eleccion_computadora, jm_resultado, jm_fecha_partida) VALUES
(2, 'tijera', 'papel', 'ganaste', DATE_SUB(NOW(), INTERVAL 15 MINUTE)),
(2, 'tijera', 'piedra', 'perdiste', DATE_SUB(NOW(), INTERVAL 12 MINUTE)),
(2, 'papel', 'papel', 'empate', DATE_SUB(NOW(), INTERVAL 9 MINUTE)),
(2, 'piedra', 'tijera', 'ganaste', DATE_SUB(NOW(), INTERVAL 7 MINUTE)),
(2, 'tijera', 'tijera', 'empate', DATE_SUB(NOW(), INTERVAL 5 MINUTE)),
(2, 'papel', 'piedra', 'ganaste', DATE_SUB(NOW(), INTERVAL 3 MINUTE));

-- =============================================
-- VISTAS PARA CONSULTAS FRECUENTES
-- =============================================

-- Vista para el ranking principal
CREATE OR REPLACE VIEW jm_vista_ranking AS
SELECT 
    r.jm_posicion,
    u.jm_nombre_usuario,
    e.jm_victorias,
    e.jm_derrotas,
    e.jm_empates,
    e.jm_total_partidas,
    e.jm_mejor_racha,
    ROUND((e.jm_victorias / GREATEST(e.jm_total_partidas, 1)) * 100, 2) as jm_porcentaje_victorias,
    r.jm_puntuacion,
    r.jm_ultima_actualizacion
FROM jm_rankings r
INNER JOIN jm_usuarios u ON r.jm_usuario_id = u.jm_id
INNER JOIN jm_estadisticas e ON r.jm_usuario_id = e.jm_usuario_id
WHERE u.jm_activo = TRUE
ORDER BY r.jm_puntuacion DESC, r.jm_posicion ASC;

-- Vista para estadísticas globales
CREATE OR REPLACE VIEW jm_vista_estadisticas_globales AS
SELECT 
    COUNT(DISTINCT u.jm_id) as jm_total_usuarios,
    COUNT(p.jm_id) as jm_total_partidas,
    SUM(CASE WHEN p.jm_resultado = 'ganaste' THEN 1 ELSE 0 END) as jm_total_victorias,
    SUM(CASE WHEN p.jm_resultado = 'perdiste' THEN 1 ELSE 0 END) as jm_total_derrotas,
    SUM(CASE WHEN p.jm_resultado = 'empate' THEN 1 ELSE 0 END) as jm_total_empates,
    COUNT(CASE WHEN DATE(p.jm_fecha_partida) = CURDATE() THEN 1 END) as jm_partidas_hoy,
    MAX(u.jm_fecha_registro) as jm_ultimo_registro
FROM jm_usuarios u
LEFT JOIN jm_partidas p ON u.jm_id = p.jm_usuario_id
WHERE u.jm_activo = TRUE;

-- =============================================
-- PROCEDIMIENTOS ALMACENADOS
-- =============================================

-- Procedimiento para actualizar estadísticas después de una partida
DELIMITER //
CREATE PROCEDURE jm_actualizar_estadisticas_partida(
    IN p_usuario_id INT,
    IN p_resultado ENUM('ganaste', 'perdiste', 'empate')
)
BEGIN
    DECLARE v_victorias INT;
    DECLARE v_derrotas INT;
    DECLARE v_empates INT;
    DECLARE v_total_partidas INT;
    DECLARE v_mejor_racha INT;
    DECLARE v_racha_actual INT;
    
    -- Obtener estadísticas actuales
    SELECT jm_victorias, jm_derrotas, jm_empates, jm_total_partidas, jm_mejor_racha
    INTO v_victorias, v_derrotas, v_empates, v_total_partidas, v_mejor_racha
    FROM jm_estadisticas
    WHERE jm_usuario_id = p_usuario_id;
    
    -- Calcular racha actual (simplificado)
    SET v_racha_actual = 0;
    IF p_resultado = 'ganaste' THEN
        SET v_racha_actual = COALESCE(v_mejor_racha, 0) + 1;
    END IF;
    
    -- Actualizar contadores según resultado
    CASE p_resultado
        WHEN 'ganaste' THEN SET v_victorias = v_victorias + 1;
        WHEN 'perdiste' THEN SET v_derrotas = v_derrotas + 1;
        WHEN 'empate' THEN SET v_empates = v_empates + 1;
    END CASE;
    
    SET v_total_partidas = v_total_partidas + 1;
    
    -- Actualizar mejor racha si es necesario
    IF v_racha_actual > COALESCE(v_mejor_racha, 0) THEN
        SET v_mejor_racha = v_racha_actual;
    END IF;
    
    -- Actualizar la tabla de estadísticas
    UPDATE jm_estadisticas 
    SET jm_victorias = v_victorias,
        jm_derrotas = v_derrotas,
        jm_empates = v_empates,
        jm_total_partidas = v_total_partidas,
        jm_mejor_racha = v_mejor_racha,
        jm_ultima_actualizacion = NOW()
    WHERE jm_usuario_id = p_usuario_id;
    
    -- Actualizar ranking
    CALL jm_actualizar_ranking_usuario(p_usuario_id);
    
END//
DELIMITER ;

-- Procedimiento para actualizar ranking de usuario
DELIMITER //
CREATE PROCEDURE jm_actualizar_ranking_usuario(IN p_usuario_id INT)
BEGIN
    DECLARE v_puntuacion INT;
    DECLARE v_victorias INT;
    DECLARE v_total_partidas INT;
    DECLARE v_mejor_racha INT;
    DECLARE v_porcentaje_victorias DECIMAL(5,2);
    
    -- Obtener estadísticas del usuario
    SELECT jm_victorias, jm_total_partidas, jm_mejor_racha
    INTO v_victorias, v_total_partidas, v_mejor_racha
    FROM jm_estadisticas
    WHERE jm_usuario_id = p_usuario_id;
    
    -- Calcular porcentaje de victorias
    IF v_total_partidas > 0 THEN
        SET v_porcentaje_victorias = (v_victorias / v_total_partidas) * 100;
    ELSE
        SET v_porcentaje_victorias = 0;
    END IF;
    
    -- Calcular puntuación (victorias * 10 + porcentaje + racha * 5)
    SET v_puntuacion = (v_victorias * 10) + v_porcentaje_victorias + (v_mejor_racha * 5);
    
    -- Insertar o actualizar ranking
    INSERT INTO jm_rankings (jm_usuario_id, jm_puntuacion, jm_ultima_actualizacion)
    VALUES (p_usuario_id, v_puntuacion, NOW())
    ON DUPLICATE KEY UPDATE 
        jm_puntuacion = v_puntuacion,
        jm_ultima_actualizacion = NOW();
    
    -- Recalcular todas las posiciones
    CALL jm_recalcular_posiciones_ranking();
    
END//
DELIMITER ;

-- Procedimiento para recalcular todas las posiciones del ranking
DELIMITER //
CREATE PROCEDURE jm_recalcular_posiciones_ranking()
BEGIN
    UPDATE jm_rankings r
    JOIN (
        SELECT 
            jm_usuario_id,
            RANK() OVER (ORDER BY jm_puntuacion DESC) as nueva_posicion
        FROM jm_rankings
    ) as temp ON r.jm_usuario_id = temp.jm_usuario_id
    SET r.jm_posicion = temp.nueva_posicion;
END//
DELIMITER ;

-- =============================================
-- TRIGGERS PARA MANTENER INTEGRIDAD DE DATOS
-- =============================================

-- Trigger: Crear estadísticas automáticamente cuando se registra un usuario
DELIMITER //
CREATE TRIGGER jm_after_insert_usuario
AFTER INSERT ON jm_usuarios
FOR EACH ROW
BEGIN
    INSERT INTO jm_estadisticas (jm_usuario_id) VALUES (NEW.jm_id);
    INSERT INTO jm_rankings (jm_usuario_id) VALUES (NEW.jm_id);
END//
DELIMITER ;

-- Trigger: Actualizar estadísticas después de insertar partida
DELIMITER //
CREATE TRIGGER jm_after_insert_partida
AFTER INSERT ON jm_partidas
FOR EACH ROW
BEGIN
    CALL jm_actualizar_estadisticas_partida(NEW.jm_usuario_id, NEW.jm_resultado);
END//
DELIMITER ;

-- =============================================
-- CONSULTAS DE VERIFICACIÓN
-- =============================================

-- Verificar que todo se creó correctamente
SELECT '✅ Base de datos creada exitosamente' as Estado;

-- Mostrar resumen de tablas creadas
SELECT 
    TABLE_NAME as 'Tabla',
    TABLE_ROWS as 'Registros',
    TABLE_COLLATION as 'Collation',
    ENGINE as 'Motor'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'jm_parcial_plp3';

-- Mostrar datos de ejemplo insertados
SELECT 'Usuarios:' as '';
SELECT jm_id, jm_nombre_usuario, jm_email FROM jm_usuarios;

SELECT 'Estadísticas:' as '';
SELECT u.jm_nombre_usuario, e.jm_victorias, e.jm_derrotas, e.jm_empates, e.jm_total_partidas
FROM jm_estadisticas e
JOIN jm_usuarios u ON e.jm_usuario_id = u.jm_id;

SELECT 'Ranking:' as '';
SELECT * FROM jm_vista_ranking LIMIT 10;

SELECT 'Estadísticas Globales:' as '';
SELECT * FROM jm_vista_estadisticas_globales;