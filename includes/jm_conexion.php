<?php
/**
 * JM_Conexion - Clase para manejar la conexión a la base de datos
 * Prefijo: jm (iniciales del desarrollador)
 * Proyecto: Piedra, Papel o Tijera - Parcial PLP3
 */

class JM_Conexion {
    // Configuración de la base de datos
    private $jm_host = 'localhost';
    private $jm_usuario = 'root';
    private $jm_password = '';
    private $jm_base_datos = 'jm_parcial_plp3';
    private $jm_puerto = 3306;
    
    // Variables de conexión y error
    private $jm_conexion;
    private $jm_error;
    private $jm_esta_conectado = false;
    
    public function __construct() {
        $this->jm_conectar();
    }
    
    /**
     * Establece la conexión con la base de datos
     */
    private function jm_conectar() {
        try {
            // Crear conexión MySQLi
            $this->jm_conexion = new mysqli(
                $this->jm_host,
                $this->jm_usuario, 
                $this->jm_password,
                $this->jm_base_datos,
                $this->jm_puerto
            );
            
            // Verificar errores de conexión
            if ($this->jm_conexion->connect_error) {
                throw new Exception(
                    "Error de conexión a la base de datos: " . 
                    $this->jm_conexion->connect_error . " (" . 
                    $this->jm_conexion->connect_errno . ")"
                );
            }
            
            // Configurar charset para caracteres especiales
            $this->jm_conexion->set_charset("utf8mb4");
            
            // Configurar timezone
            $this->jm_conexion->query("SET time_zone = '-05:00'");
            
            $this->jm_esta_conectado = true;
            
        } catch (Exception $e) {
            $this->jm_error = $e->getMessage();
            $this->jm_esta_conectado = false;
            
            // Log del error
            error_log("JM_Error [Conexión BD]: " . $this->jm_error);
        }
    }
    
    /**
     * Obtiene la instancia de la conexión MySQLi
     * @return mysqli|null
     */
    public function jm_obtener_conexion() {
        return $this->jm_conexion;
    }
    
    /**
     * Verifica si la conexión está activa
     * @return bool
     */
    public function jm_esta_conectado() {
        return $this->jm_esta_conectado && 
               $this->jm_conexion && 
               $this->jm_conexion->ping();
    }
    
    /**
     * Cierra la conexión a la base de datos
     */
    public function jm_cerrar_conexion() {
        if ($this->jm_conexion && $this->jm_esta_conectado) {
            $this->jm_conexion->close();
            $this->jm_esta_conectado = false;
        }
    }
    
    /**
     * Escapa caracteres especiales para prevenir SQL injection
     * @param string $dato - Dato a escapar
     * @return string - Dato escapado
     */
    public function jm_escapar($dato) {
        if (!$this->jm_esta_conectado()) {
            return $dato;
        }
        
        // Limpiar y escapar el dato
        $dato_limpio = trim($dato);
        $dato_limpio = htmlspecialchars($dato_limpio, ENT_QUOTES, 'UTF-8');
        $dato_limpio = $this->jm_conexion->real_escape_string($dato_limpio);
        
        return $dato_limpio;
    }
    
    /**
     * Ejecuta una consulta SELECT y retorna los resultados
     * @param string $sql - Consulta SQL
     * @param array $params - Parámetros para prepared statement
     * @return array|false - Resultados o false en error
     */
    public function jm_ejecutar_select($sql, $params = []) {
        if (!$this->jm_esta_conectado()) {
            $this->jm_error = "No hay conexión a la base de datos";
            return false;
        }
        
        try {
            $stmt = $this->jm_conexion->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error en preparación: " . $this->jm_conexion->error);
            }
            
            if (!empty($params)) {
                $tipos = '';
                $valores = [];
                
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $tipos .= 'i';
                    } elseif (is_float($param)) {
                        $tipos .= 'd';
                    } else {
                        $tipos .= 's';
                    }
                    $valores[] = $param;
                }
                
                $stmt->bind_param($tipos, ...$valores);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Error en ejecución: " . $stmt->error);
            }
            
            $resultado = $stmt->get_result();
            $datos = [];
            
            while ($fila = $resultado->fetch_assoc()) {
                $datos[] = $fila;
            }
            
            $stmt->close();
            return $datos;
            
        } catch (Exception $e) {
            $this->jm_error = $e->getMessage();
            error_log("JM_Error [SELECT]: " . $this->jm_error);
            return false;
        }
    }
    
    /**
     * Ejecuta una consulta INSERT, UPDATE o DELETE
     * @param string $sql - Consulta SQL
     * @param array $params - Parámetros para prepared statement
     * @return int|false - ID del insert o número de filas afectadas
     */
    public function jm_ejecutar_consulta($sql, $params = []) {
        if (!$this->jm_esta_conectado()) {
            $this->jm_error = "No hay conexión a la base de datos";
            return false;
        }
        
        try {
            $stmt = $this->jm_conexion->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error en preparación: " . $this->jm_conexion->error);
            }
            
            if (!empty($params)) {
                $tipos = '';
                $valores = [];
                
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $tipos .= 'i';
                    } elseif (is_float($param)) {
                        $tipos .= 'd';
                    } else {
                        $tipos .= 's';
                    }
                    $valores[] = $param;
                }
                
                $stmt->bind_param($tipos, ...$valores);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Error en ejecución: " . $stmt->error);
            }
            
            $filas_afectadas = $stmt->affected_rows;
            $id_insertado = $stmt->insert_id;
            
            $stmt->close();
            
            // Retornar ID si es INSERT, sino filas afectadas
            return $id_insertado > 0 ? $id_insertado : $filas_afectadas;
            
        } catch (Exception $e) {
            $this->jm_error = $e->getMessage();
            error_log("JM_Error [Consulta]: " . $this->jm_error);
            return false;
        }
    }
    
    /**
     * Inicia una transacción
     * @return bool
     */
    public function jm_iniciar_transaccion() {
        if (!$this->jm_esta_conectado()) {
            return false;
        }
        return $this->jm_conexion->begin_transaction();
    }
    
    /**
     * Confirma una transacción
     * @return bool
     */
    public function jm_confirmar_transaccion() {
        if (!$this->jm_esta_conectado()) {
            return false;
        }
        return $this->jm_conexion->commit();
    }
    
    /**
     * Revierte una transacción
     * @return bool
     */
    public function jm_revertir_transaccion() {
        if (!$this->jm_esta_conectado()) {
            return false;
        }
        return $this->jm_conexion->rollback();
    }
    
    /**
     * Obtiene el último error ocurrido
     * @return string
     */
    public function jm_obtener_error() {
        return $this->jm_error;
    }
    
    /**
     * Obtiene información de la conexión
     * @return array
     */
    public function jm_obtener_info_conexion() {
        return [
            'host' => $this->jm_host,
            'base_datos' => $this->jm_base_datos,
            'usuario' => $this->jm_usuario,
            'conectado' => $this->jm_esta_conectado(),
            'charset' => $this->jm_conexion ? $this->jm_conexion->character_set_name() : 'N/A',
            'version' => $this->jm_conexion ? $this->jm_conexion->server_version : 'N/A'
        ];
    }
    
    /**
     * Destructor - cierra la conexión automáticamente
     */
    public function __destruct() {
        $this->jm_cerrar_conexion();
    }
}

// Función auxiliar para crear instancia de conexión
function jm_crear_conexion() {
    return new JM_Conexion();
}

?>