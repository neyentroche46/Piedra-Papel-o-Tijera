<?php
/**
 * JM_Usuario - Clase para manejar operaciones de usuarios
 * Prefijo: jm (iniciales del desarrollador)
 * Proyecto: Piedra, Papel o Tijera - Parcial PLP3
 */

require_once 'jm_conexion.php';

class JM_Usuario {
    private $jm_conexion;
    private $jm_usuario_actual;
    
    public function __construct() {
        $conexion = new JM_Conexion();
        $this->jm_conexion = $conexion->jm_obtener_conexion();
        $this->jm_inicializar_sesion();
    }
    
    /**
     * Inicializa la sesión si no está activa
     */
    private function jm_inicializar_sesion() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Registra un nuevo usuario en el sistema
     * @param string $nombre_usuario
     * @param string $email
     * @param string $password
     * @return array
     */
    public function jm_registrar_usuario($nombre_usuario, $email, $password) {
        // Verificar conexión a la base de datos
        if (!$this->jm_conexion) {
            return [
                'success' => false, 
                'message' => 'Error de conexión a la base de datos'
            ];
        }
        
        // Validar datos de entrada
        if (empty($nombre_usuario) || empty($email) || empty($password)) {
            return [
                'success' => false, 
                'message' => 'Todos los campos son obligatorios'
            ];
        }
        
        // Limpiar y validar datos
        $nombre_usuario = trim($nombre_usuario);
        $email = trim($email);
        $password = trim($password);
        
        // Validar formato de email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false, 
                'message' => 'El formato del email no es válido'
            ];
        }
        
        // Validar longitud de contraseña
        if (strlen($password) < 6) {
            return [
                'success' => false, 
                'message' => 'La contraseña debe tener al menos 6 caracteres'
            ];
        }
        
        // Validar nombre de usuario
        if (strlen($nombre_usuario) < 3 || strlen($nombre_usuario) > 50) {
            return [
                'success' => false, 
                'message' => 'El nombre de usuario debe tener entre 3 y 50 caracteres'
            ];
        }
        
        // Verificar si el usuario o email ya existen
        $usuario_existente = $this->jm_verificar_usuario_existente($nombre_usuario, $email);
        if ($usuario_existente['existe']) {
            return [
                'success' => false, 
                'message' => $usuario_existente['mensaje']
            ];
        }
        
        // Hash de la contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Preparar consulta de inserción
        $query = "INSERT INTO jm_usuarios (jm_nombre_usuario, jm_email, jm_password_hash) 
                  VALUES (?, ?, ?)";
        
        try {
            $stmt = $this->jm_conexion->prepare($query);
            if (!$stmt) {
                throw new Exception("Error en preparación de consulta: " . $this->jm_conexion->error);
            }
            
            $stmt->bind_param("sss", $nombre_usuario, $email, $password_hash);
            
            if ($stmt->execute()) {
                $usuario_id = $stmt->insert_id;
                
                // Inicializar estadísticas para el nuevo usuario
                $this->jm_inicializar_estadisticas($usuario_id);
                
                // Iniciar sesión automáticamente
                $this->jm_iniciar_sesion_usuario($usuario_id, $nombre_usuario, $email);
                
                return [
                    'success' => true, 
                    'message' => 'Usuario registrado exitosamente',
                    'usuario_id' => $usuario_id,
                    'nombre_usuario' => $nombre_usuario
                ];
            } else {
                throw new Exception("Error en ejecución: " . $stmt->error);
            }
            
        } catch (Exception $e) {
            error_log("JM_Error [Registro Usuario]: " . $e->getMessage());
            return [
                'success' => false, 
                'message' => 'Error al registrar usuario. Intente nuevamente.'
            ];
        }
    }
    
    /**
     * Inicia sesión de un usuario
     * @param string $nombre_usuario
     * @param string $password
     * @return array
     */
    public function jm_iniciar_sesion($nombre_usuario, $password) {
        // Verificar conexión a la base de datos
        if (!$this->jm_conexion) {
            return [
                'success' => false, 
                'message' => 'Error de conexión a la base de datos'
            ];
        }
        
        // Validar datos de entrada
        if (empty($nombre_usuario) || empty($password)) {
            return [
                'success' => false, 
                'message' => 'Usuario y contraseña son obligatorios'
            ];
        }
        
        $nombre_usuario = trim($nombre_usuario);
        
        // Preparar consulta para obtener usuario
        $query = "SELECT jm_id, jm_nombre_usuario, jm_email, jm_password_hash 
                  FROM jm_usuarios 
                  WHERE jm_nombre_usuario = ? AND jm_activo = 1 
                  LIMIT 1";
        
        try {
            $stmt = $this->jm_conexion->prepare($query);
            if (!$stmt) {
                throw new Exception("Error en preparación de consulta: " . $this->jm_conexion->error);
            }
            
            $stmt->bind_param("s", $nombre_usuario);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $usuario = $result->fetch_assoc();
                
                // Verificar contraseña
                if (password_verify($password, $usuario['jm_password_hash'])) {
                    // Actualizar último login
                    $this->jm_actualizar_ultimo_login($usuario['jm_id']);
                    
                    // Iniciar sesión
                    $this->jm_iniciar_sesion_usuario(
                        $usuario['jm_id'], 
                        $usuario['jm_nombre_usuario'], 
                        $usuario['jm_email']
                    );
                    
                    return [
                        'success' => true, 
                        'message' => 'Inicio de sesión exitoso',
                        'usuario' => [
                            'id' => $usuario['jm_id'],
                            'nombre_usuario' => $usuario['jm_nombre_usuario'],
                            'email' => $usuario['jm_email']
                        ]
                    ];
                }
            }
            
            return [
                'success' => false, 
                'message' => 'Credenciales incorrectas'
            ];
            
        } catch (Exception $e) {
            error_log("JM_Error [Inicio Sesión]: " . $e->getMessage());
            return [
                'success' => false, 
                'message' => 'Error en el inicio de sesión. Intente nuevamente.'
            ];
        }
    }
    
    /**
     * Cierra la sesión del usuario actual
     * @return array
     */
    public function jm_cerrar_sesion() {
        $this->jm_inicializar_sesion();
        
        // Destruir todas las variables de sesión
        $_SESSION = array();
        
        // Destruir la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
        
        return [
            'success' => true, 
            'message' => 'Sesión cerrada exitosamente'
        ];
    }
    
    /**
     * Verifica si el usuario está autenticado
     * @return bool
     */
    public function jm_esta_autenticado() {
        $this->jm_inicializar_sesion();
        return isset($_SESSION['jm_autenticado']) && 
               $_SESSION['jm_autenticado'] === true &&
               isset($_SESSION['jm_usuario_id']);
    }
    
    /**
     * Obtiene los datos del usuario actual
     * @return array|null
     */
    public function jm_obtener_usuario_actual() {
        if (!$this->jm_esta_autenticado()) {
            return null;
        }
        
        $this->jm_inicializar_sesion();
        
        return [
            'id' => $_SESSION['jm_usuario_id'],
            'nombre_usuario' => $_SESSION['jm_nombre_usuario'],
            'email' => $_SESSION['jm_email'] ?? ''
        ];
    }
    
    /**
     * Actualiza el perfil del usuario
     * @param int $usuario_id
     * @param string $nombre_usuario
     * @param string $email
     * @return array
     */
    public function jm_actualizar_perfil($usuario_id, $nombre_usuario, $email) {
        if (!$this->jm_conexion) {
            return ['success' => false, 'message' => 'Error de conexión'];
        }
        
        // Validar datos
        if (empty($nombre_usuario) || empty($email)) {
            return ['success' => false, 'message' => 'Todos los campos son obligatorios'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email no válido'];
        }
        
        // Verificar si el nuevo nombre de usuario o email ya existen (excluyendo el usuario actual)
        $query_check = "SELECT jm_id FROM jm_usuarios 
                       WHERE (jm_nombre_usuario = ? OR jm_email = ?) 
                       AND jm_id != ?";
        
        $stmt_check = $this->jm_conexion->prepare($query_check);
        $stmt_check->bind_param("ssi", $nombre_usuario, $email, $usuario_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            return ['success' => false, 'message' => 'El nombre de usuario o email ya están en uso'];
        }
        
        // Actualizar perfil
        $query = "UPDATE jm_usuarios 
                 SET jm_nombre_usuario = ?, jm_email = ? 
                 WHERE jm_id = ?";
        
        try {
            $stmt = $this->jm_conexion->prepare($query);
            $stmt->bind_param("ssi", $nombre_usuario, $email, $usuario_id);
            
            if ($stmt->execute()) {
                // Actualizar sesión
                $_SESSION['jm_nombre_usuario'] = $nombre_usuario;
                $_SESSION['jm_email'] = $email;
                
                return [
                    'success' => true, 
                    'message' => 'Perfil actualizado exitosamente'
                ];
            } else {
                throw new Exception("Error en ejecución: " . $stmt->error);
            }
            
        } catch (Exception $e) {
            error_log("JM_Error [Actualizar Perfil]: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al actualizar perfil'];
        }
    }
    
    /**
     * Cambia la contraseña del usuario
     * @param int $usuario_id
     * @param string $password_actual
     * @param string $nueva_password
     * @return array
     */
    public function jm_cambiar_password($usuario_id, $password_actual, $nueva_password) {
        if (!$this->jm_conexion) {
            return ['success' => false, 'message' => 'Error de conexión'];
        }
        
        // Validar nueva contraseña
        if (strlen($nueva_password) < 6) {
            return ['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres'];
        }
        
        // Obtener hash actual
        $query = "SELECT jm_password_hash FROM jm_usuarios WHERE jm_id = ?";
        $stmt = $this->jm_conexion->prepare($query);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $usuario = $result->fetch_assoc();
            
            // Verificar contraseña actual
            if (!password_verify($password_actual, $usuario['jm_password_hash'])) {
                return ['success' => false, 'message' => 'La contraseña actual es incorrecta'];
            }
            
            // Actualizar contraseña
            $nueva_password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
            $query_update = "UPDATE jm_usuarios SET jm_password_hash = ? WHERE jm_id = ?";
            
            $stmt_update = $this->jm_conexion->prepare($query_update);
            $stmt_update->bind_param("si", $nueva_password_hash, $usuario_id);
            
            if ($stmt_update->execute()) {
                return ['success' => true, 'message' => 'Contraseña cambiada exitosamente'];
            } else {
                return ['success' => false, 'message' => 'Error al cambiar contraseña'];
            }
        }
        
        return ['success' => false, 'message' => 'Usuario no encontrado'];
    }
    
    /**
     * Verifica si un usuario o email ya existen
     * @param string $nombre_usuario
     * @param string $email
     * @return array
     */
    private function jm_verificar_usuario_existente($nombre_usuario, $email) {
        $query = "SELECT jm_nombre_usuario, jm_email 
                  FROM jm_usuarios 
                  WHERE jm_nombre_usuario = ? OR jm_email = ? 
                  LIMIT 1";
        
        $stmt = $this->jm_conexion->prepare($query);
        $stmt->bind_param("ss", $nombre_usuario, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $usuario_existente = $result->fetch_assoc();
            
            if ($usuario_existente['jm_nombre_usuario'] === $nombre_usuario) {
                return [
                    'existe' => true,
                    'mensaje' => 'El nombre de usuario ya está en uso'
                ];
            } elseif ($usuario_existente['jm_email'] === $email) {
                return [
                    'existe' => true,
                    'mensaje' => 'El email ya está registrado'
                ];
            }
        }
        
        return ['existe' => false, 'mensaje' => ''];
    }
    
    /**
     * Inicializa las estadísticas para un nuevo usuario
     * @param int $usuario_id
     */
    private function jm_inicializar_estadisticas($usuario_id) {
        $query = "INSERT INTO jm_estadisticas (jm_usuario_id) VALUES (?)";
        
        try {
            $stmt = $this->jm_conexion->prepare($query);
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("JM_Error [Inicializar Estadísticas]: " . $e->getMessage());
        }
    }
    
    /**
     * Actualiza la fecha del último login
     * @param int $usuario_id
     */
    private function jm_actualizar_ultimo_login($usuario_id) {
        $query = "UPDATE jm_usuarios SET jm_ultimo_login = NOW() WHERE jm_id = ?";
        
        try {
            $stmt = $this->jm_conexion->prepare($query);
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("JM_Error [Actualizar Último Login]: " . $e->getMessage());
        }
    }
    
    /**
     * Inicia la sesión del usuario
     * @param int $usuario_id
     * @param string $nombre_usuario
     * @param string $email
     */
    private function jm_iniciar_sesion_usuario($usuario_id, $nombre_usuario, $email) {
        $this->jm_inicializar_sesion();
        
        $_SESSION['jm_usuario_id'] = $usuario_id;
        $_SESSION['jm_nombre_usuario'] = $nombre_usuario;
        $_SESSION['jm_email'] = $email;
        $_SESSION['jm_autenticado'] = true;
        $_SESSION['jm_ultimo_acceso'] = time();
    }
    
    /**
     * Obtiene información básica de un usuario por ID
     * @param int $usuario_id
     * @return array|null
     */
    public function jm_obtener_usuario_por_id($usuario_id) {
        if (!$this->jm_conexion) {
            return null;
        }
        
        $query = "SELECT jm_id, jm_nombre_usuario, jm_email, jm_fecha_registro, jm_ultimo_login 
                  FROM jm_usuarios 
                  WHERE jm_id = ? AND jm_activo = 1 
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
            error_log("JM_Error [Obtener Usuario por ID]: " . $e->getMessage());
        }
        
        return null;
    }
}

// Función auxiliar para crear instancia de usuario
function jm_crear_instancia_usuario() {
    return new JM_Usuario();
}

?>