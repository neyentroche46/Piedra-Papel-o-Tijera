<?php
/**
 * JM_Juego - Clase para manejar operaciones del juego Piedra, Papel o Tijera
 * Prefijo: jm (iniciales del desarrollador)
 * Proyecto: Piedra, Papel o Tijera - Parcial PLP3
 */

require_once 'jm_conexion.php';

class JM_Juego {
    private $jm_conexion;
    
    public function __construct() {
        $conexion = new JM_Conexion();
        $this->jm_conexion = $conexion->jm_obtener_conexion();
    }
    
    /**
     * Guarda una partida en la base de datos
     * @param int $usuario_id
     * @param string $eleccion_usuario
     * @param string $eleccion_computadora
     * @param string $resultado
     * @return array
     */
    public function jm_guardar_partida($usuario_id, $eleccion_usuario, $eleccion_computadora, $resultado) {
        // Verificar conexión a la base de datos
        if (!$this->jm_conexion) {
            return [
                'success' => false, 
                'message' => 'Error de conexión a la base de datos'
            ];
        }
        
        // Validar datos de entrada
        if (empty($usuario_id) || empty($eleccion_usuario) || empty($eleccion_computadora) || empty($resultado)) {
            return [
                'success' => false, 
                'message' => 'Datos de partida incompletos'
            ];
        }
        
        // Validar elecciones válidas
        $elecciones_validas = ['piedra', 'papel', 'tijera'];
        $resultados_validos = ['ganaste', 'perdiste', 'empate'];
        
        if (!in_array($eleccion_usuario, $elecciones_validas) || 
            !in_array($eleccion_computadora, $elecciones_validas) ||
            !in_array($resultado, $resultados_validos)) {
            return [
                'success' => false, 
                'message' => 'Datos de partida no válidos'
            ];
        }
        
        $usuario_id = (int)$usuario_id;
        
        // Preparar consulta de inserción
        $query = "INSERT INTO jm_partidas (jm_usuario_id, jm_eleccion_usuario, jm_eleccion_computadora, jm_resultado) 
                  VALUES (?, ?, ?, ?)";
        
        try {
            $stmt = $this->jm_conexion->prepare($query);
            if (!$stmt) {
                throw new Exception("Error en preparación de consulta: " . $this->jm_conexion->error);
            }
            
            $stmt->bind_param("isss", $usuario_id, $eleccion_usuario, $eleccion_computadora, $resultado);
            
            if ($stmt->execute()) {
                $partida_id = $stmt->insert_id;
                
                // Actualizar estadísticas del usuario
                $this->jm_actualizar_estadisticas($usuario_id, $resultado);
                
                return [
                    'success' => true, 
                    'partida_id' => $partida_id,
                    'message' => 'Partida guardada exitosamente'
                ];
            } else {
                throw new Exception("Error en ejecución: " . $stmt->error);
            }
            
        } catch (Exception $e) {
            error_log("JM_Error [Guardar Partida]: " . $e->getMessage());
            return [
                'success' => false, 
                'message' => 'Error al guardar partida. Intente nuevamente.'
            ];
        }
    }
    
    /**
     * Obtiene las estadísticas de un usuario
     * @param int $usuario_id
     * @return array
     */
    public function jm_obtener_estadisticas_usuario($usuario_id) {
        // Verificar conexión a la base de datos
        if (!$this->jm_conexion) {
            return [
                'jm_victorias' => 0,
                'jm_derrotas' => 0,
                'jm_empates' => 0,
                'jm_total_partidas' => 0,
                'jm_mejor_racha' => 0
            ];
        }
        
        $usuario_id = (int)$usuario_id;
        
        $query = "SELECT jm_victorias, jm_derrotas, jm_empates, jm_total_partidas, jm_mejor_racha 
                  FROM jm_estadisticas 
                  WHERE jm_usuario_id = ? 
                  LIMIT 1";
        
        try {
            $stmt = $this->jm_conexion->prepare($query);
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                return $result->fetch_assoc();
            }
            
        } catch (Exception $e) {
            error_log("JM_Error [Obtener Estadísticas]: " . $e->getMessage());
        }
        
        // Retornar estadísticas por defecto si no se encuentran
        return [
            'jm_victorias' => 0,
            'jm_derrotas' => 0,
            'jm_empates' => 0,
            'jm_total_partidas' => 0,
            'jm_mejor_racha' => 0
        ];
    }
    
    /**
     * Obtiene el historial de partidas de un usuario
     * @param int $usuario_id
     * @param int $limite
     * @return array
     */
    public function jm_obtener_historial_usuario($usuario_id, $limite = 10) {
        // Verificar conexión a la base de datos
        if (!$this->jm_conexion) {
            return [];
        }
        
        $usuario_id = (int)$usuario_id;
        $limite = (int)$limite;
        
        // Limitar el máximo de resultados por seguridad
        if ($limite > 50) {
            $limite = 50;
        }
        
        $query = "SELECT jm_eleccion_usuario, jm_eleccion_computadora, jm_resultado, jm_fecha_partida 
                  FROM jm_partidas 
                  WHERE jm_usuario_id = ? 
                  ORDER BY jm_fecha_partida DESC 
                  LIMIT ?";
        
        try {
            $stmt = $this->jm_conexion->prepare($query);
            $stmt->bind_param("ii", $usuario_id, $limite);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $historial = [];
            while ($fila = $result->fetch_assoc()) {
                $historial[] = $fila;
            }
            
            return $historial;
            
        } catch (Exception $e) {
            error_log("JM_Error [Obtener Historial]: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene el ranking de jugadores
     * @param int $limite
     * @return array
     */
    public function jm_obtener_ranking($limite = 10) {
        // Verificar conexión a la base de datos
        if (!$this->jm_conexion) {
            return [];
        }
        
        $limite = (int)$limite;
        
        // Limitar el máximo de resultados por seguridad
        if ($limite > 20) {
            $limite = 20;
        }
        
        $query = "SELECT u.jm_nombre_usuario, 
                         e.jm_victorias, 
                         e.jm_derrotas, 
                         e.jm_empates, 
                         e.jm_total_partidas,
                         e.jm_mejor_racha,
                         ROUND((e.jm_victorias / GREATEST(e.jm_total_partidas, 1)) * 100, 2) as jm_porcentaje_victorias
                  FROM jm_estadisticas e
                  INNER JOIN jm_usuarios u ON e.jm_usuario_id = u.jm_id
                  WHERE e.jm_total_partidas > 0
                  ORDER BY e.jm_victorias DESC, jm_porcentaje_victorias DESC
                  LIMIT ?";
        
        try {
            $stmt = $this->jm_conexion->prepare($query);
            $stmt->bind_param("i", $limite);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $ranking = [];
            $posicion = 1;
            
            while ($fila = $result->fetch_assoc()) {
                $fila['jm_posicion'] = $posicion++;
                $ranking[] = $fila;
            }
            
            return $ranking;
            
        } catch (Exception $e) {
            error_log("JM_Error [Obtener Ranking]: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene estadísticas globales del juego
     * @return array
     */
    public function jm_obtener_estadisticas_globales() {
        // Verificar conexión a la base de datos
        if (!$this->jm_conexion) {
            return [
                'total_partidas' => 0,
                'total_usuarios' => 0,
                'partidas_hoy' => 0,
                'mejor_jugador' => null
            ];
        }
        
        try {
            // Total de partidas jugadas
            $query_partidas = "SELECT COUNT(*) as total_partidas FROM jm_partidas";
            $result_partidas = $this->jm_conexion->query($query_partidas);
            $total_partidas = $result_partidas ? $result_partidas->fetch_assoc()['total_partidas'] : 0;
            
            // Total de usuarios registrados
            $query_usuarios = "SELECT COUNT(*) as total_usuarios FROM jm_usuarios WHERE jm_activo = 1";
            $result_usuarios = $this->jm_conexion->query($query_usuarios);
            $total_usuarios = $result_usuarios ? $result_usuarios->fetch_assoc()['total_usuarios'] : 0;
            
            // Partidas jugadas hoy
            $query_hoy = "SELECT COUNT(*) as partidas_hoy FROM jm_partidas WHERE DATE(jm_fecha_partida) = CURDATE()";
            $result_hoy = $this->jm_conexion->query($query_hoy);
            $partidas_hoy = $result_hoy ? $result_hoy->fetch_assoc()['partidas_hoy'] : 0;
            
            // Mejor jugador
            $query_mejor = "SELECT u.jm_nombre_usuario, e.jm_victorias 
                           FROM jm_estadisticas e 
                           INNER JOIN jm_usuarios u ON e.jm_usuario_id = u.jm_id 
                           ORDER BY e.jm_victorias DESC 
                           LIMIT 1";
            $result_mejor = $this->jm_conexion->query($query_mejor);
            $mejor_jugador = $result_mejor && $result_mejor->num_rows > 0 ? $result_mejor->fetch_assoc() : null;
            
            return [
                'total_partidas' => (int)$total_partidas,
                'total_usuarios' => (int)$total_usuarios,
                'partidas_hoy' => (int)$partidas_hoy,
                'mejor_jugador' => $mejor_jugador
            ];
            
        } catch (Exception $e) {
            error_log("JM_Error [Estadísticas Globales]: " . $e->getMessage());
            return [
                'total_partidas' => 0,
                'total_usuarios' => 0,
                'partidas_hoy' => 0,
                'mejor_jugador' => null
            ];
        }
    }
    
    /**
     * Obtiene las partidas más recientes de todos los usuarios
     * @param int $limite
     * @return array
     */
    public function jm_obtener_partidas_recientes($limite = 5) {
        // Verificar conexión a la base de datos
        if (!$this->jm_conexion) {
            return [];
        }
        
        $limite = (int)$limite;
        
        $query = "SELECT p.jm_eleccion_usuario, p.jm_eleccion_computadora, p.jm_resultado, p.jm_fecha_partida,
                         u.jm_nombre_usuario
                  FROM jm_partidas p
                  INNER JOIN jm_usuarios u ON p.jm_usuario_id = u.jm_id
                  ORDER BY p.jm_fecha_partida DESC 
                  LIMIT ?";
        
        try {
            $stmt = $this->jm_conexion->prepare($query);
            $stmt->bind_param("i", $limite);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $partidas = [];
            while ($fila = $result->fetch_assoc()) {
                $partidas[] = $fila;
            }
            
            return $partidas;
            
        } catch (Exception $e) {
            error_log("JM_Error [Partidas Recientes]: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Reinicia las estadísticas de un usuario
     * @param int $usuario_id
     * @return array
     */
    public function jm_reiniciar_estadisticas($usuario_id) {
        // Verificar conexión a la base de datos
        if (!$this->jm_conexion) {
            return [
                'success' => false, 
                'message' => 'Error de conexión a la base de datos'
            ];
        }
        
        $usuario_id = (int)$usuario_id;
        
        try {
            // Iniciar transacción
            $this->jm_conexion->begin_transaction();
            
            // Eliminar partidas del usuario
            $query_partidas = "DELETE FROM jm_partidas WHERE jm_usuario_id = ?";
            $stmt_partidas = $this->jm_conexion->prepare($query_partidas);
            $stmt_partidas->bind_param("i", $usuario_id);
            $stmt_partidas->execute();
            
            // Reiniciar estadísticas
            $query_estadisticas = "UPDATE jm_estadisticas 
                                  SET jm_victorias = 0, jm_derrotas = 0, jm_empates = 0, 
                                      jm_total_partidas = 0, jm_mejor_racha = 0,
                                      jm_ultima_actualizacion = NOW()
                                  WHERE jm_usuario_id = ?";
            $stmt_estadisticas = $this->jm_conexion->prepare($query_estadisticas);
            $stmt_estadisticas->bind_param("i", $usuario_id);
            $stmt_estadisticas->execute();
            
            // Actualizar ranking
            $this->jm_actualizar_ranking($usuario_id);
            
            // Confirmar transacción
            $this->jm_conexion->commit();
            
            return [
                'success' => true, 
                'message' => 'Estadísticas reiniciadas exitosamente'
            ];
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->jm_conexion->rollback();
            error_log("JM_Error [Reiniciar Estadísticas]: " . $e->getMessage());
            return [
                'success' => false, 
                'message' => 'Error al reiniciar estadísticas'
            ];
        }
    }
    
    /**
     * Actualiza las estadísticas de un usuario después de una partida
     * @param int $usuario_id
     * @param string $resultado
     */
    private function jm_actualizar_estadisticas($usuario_id, $resultado) {
        // Obtener estadísticas actuales
        $estadisticas = $this->jm_obtener_estadisticas_usuario($usuario_id);
        
        // Actualizar contadores según el resultado
        switch($resultado) {
            case 'ganaste':
                $estadisticas['jm_victorias']++;
                break;
            case 'perdiste':
                $estadisticas['jm_derrotas']++;
                break;
            case 'empate':
                $estadisticas['jm_empates']++;
                break;
        }
        
        $estadisticas['jm_total_partidas']++;
        
        // Calcular nueva racha
        $nueva_racha = $this->jm_calcular_nueva_racha($usuario_id, $resultado);
        if ($nueva_racha > $estadisticas['jm_mejor_racha']) {
            $estadisticas['jm_mejor_racha'] = $nueva_racha;
        }
        
        // Actualizar en la base de datos
        $query = "UPDATE jm_estadisticas 
                  SET jm_victorias = ?, jm_derrotas = ?, jm_empates = ?, 
                      jm_total_partidas = ?, jm_mejor_racha = ?, jm_ultima_actualizacion = NOW() 
                  WHERE jm_usuario_id = ?";
        
        try {
            $stmt = $this->jm_conexion->prepare($query);
            $stmt->bind_param("iiiiii", 
                $estadisticas['jm_victorias'],
                $estadisticas['jm_derrotas'],
                $estadisticas['jm_empates'],
                $estadisticas['jm_total_partidas'],
                $estadisticas['jm_mejor_racha'],
                $usuario_id
            );
            $stmt->execute();
            
            // Actualizar ranking
            $this->jm_actualizar_ranking($usuario_id);
            
        } catch (Exception $e) {
            error_log("JM_Error [Actualizar Estadísticas]: " . $e->getMessage());
        }
    }
    
    /**
     * Calcula la nueva racha del usuario
     * @param int $usuario_id
     * @param string $resultado
     * @return int
     */
    private function jm_calcular_nueva_racha($usuario_id, $resultado) {
        if ($resultado !== 'ganaste') {
            return 0;
        }
        
        // Obtener las últimas partidas para calcular racha actual
        $query = "SELECT jm_resultado 
                  FROM jm_partidas 
                  WHERE jm_usuario_id = ? 
                  ORDER BY jm_fecha_partida DESC 
                  LIMIT 10";
        
        try {
            $stmt = $this->jm_conexion->prepare($query);
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $racha_actual = 0;
            while ($fila = $result->fetch_assoc()) {
                if ($fila['jm_resultado'] === 'ganaste') {
                    $racha_actual++;
                } else {
                    break;
                }
            }
            
            return $racha_actual;
            
        } catch (Exception $e) {
            error_log("JM_Error [Calcular Racha]: " . $e->getMessage());
            return 1; // Racha mínima para victoria actual
        }
    }
    
    /**
     * Actualiza el ranking del usuario
     * @param int $usuario_id
     */
    private function jm_actualizar_ranking($usuario_id) {
        $estadisticas = $this->jm_obtener_estadisticas_usuario($usuario_id);
        
        // Calcular puntuación basada en victorias y porcentaje
        $puntuacion = $estadisticas['jm_victorias'] * 10;
        if ($estadisticas['jm_total_partidas'] > 0) {
            $porcentaje = ($estadisticas['jm_victorias'] / $estadisticas['jm_total_partidas']) * 100;
            $puntuacion += $porcentaje;
        }
        
        // Bonus por racha
        $puntuacion += $estadisticas['jm_mejor_racha'] * 5;
        
        // Verificar si ya existe en el ranking
        $query_check = "SELECT jm_id FROM jm_rankings WHERE jm_usuario_id = ?";
        $stmt_check = $this->jm_conexion->prepare($query_check);
        $stmt_check->bind_param("i", $usuario_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            // Actualizar existente
            $query = "UPDATE jm_rankings 
                      SET jm_puntuacion = ?, jm_ultima_actualizacion = NOW() 
                      WHERE jm_usuario_id = ?";
            $stmt = $this->jm_conexion->prepare($query);
            $stmt->bind_param("ii", $puntuacion, $usuario_id);
        } else {
            // Insertar nuevo
            $query = "INSERT INTO jm_rankings (jm_usuario_id, jm_puntuacion) VALUES (?, ?)";
            $stmt = $this->jm_conexion->prepare($query);
            $stmt->bind_param("ii", $usuario_id, $puntuacion);
        }
        
        $stmt->execute();
        
        // Recalcular posiciones
        $this->jm_recalcular_posiciones();
    }
    
    /**
     * Recalcula las posiciones del ranking
     */
    private function jm_recalcular_posiciones() {
        $query = "UPDATE jm_rankings r
                  JOIN (
                      SELECT jm_usuario_id, 
                             RANK() OVER (ORDER BY jm_puntuacion DESC) as nueva_posicion
                      FROM jm_rankings
                  ) as temp ON r.jm_usuario_id = temp.jm_usuario_id
                  SET r.jm_posicion = temp.nueva_posicion";
        
        try {
            $this->jm_conexion->query($query);
        } catch (Exception $e) {
            error_log("JM_Error [Recalcular Posiciones]: " . $e->getMessage());
        }
    }
}

// Función auxiliar para crear instancia del juego
function jm_crear_instancia_juego() {
    return new JM_Juego();
}

?>