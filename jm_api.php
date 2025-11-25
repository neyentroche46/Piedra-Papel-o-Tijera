<?php
/**
 * JM_API - API RESTful para el juego Piedra, Papel o Tijera
 * Prefijo: jm (iniciales del desarrollador)
 * Proyecto: Piedra, Papel o Tijera - Parcial PLP3
 */

// Headers para API REST
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

// Manejar preflight requests para CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir clases necesarias
require_once 'includes/jm_usuario.php';
require_once 'includes/jm_juego.php';

class JM_API {
    private $jm_usuario;
    private $jm_juego;
    
    public function __construct() {
        $this->jm_usuario = new JM_Usuario();
        $this->jm_juego = new JM_Juego();
    }
    
    /**
     * Procesa todas las requests a la API
     */
    public function jm_procesar_request() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        
        // El último segmento es el endpoint
        $endpoint = end($segments);
        
        try {
            switch($endpoint) {
                case 'registrar':
                    if ($method === 'POST') {
                        $this->jm_registrar_usuario();
                    } else {
                        $this->jm_enviar_error(405, 'Método no permitido');
                    }
                    break;
                    
                case 'login':
                    if ($method === 'POST') {
                        $this->jm_iniciar_sesion();
                    } else {
                        $this->jm_enviar_error(405, 'Método no permitido');
                    }
                    break;
                    
                case 'logout':
                    if ($method === 'POST') {
                        $this->jm_cerrar_sesion();
                    } else {
                        $this->jm_enviar_error(405, 'Método no permitido');
                    }
                    break;
                    
                case 'guardar_partida':
                    if ($method === 'POST') {
                        $this->jm_guardar_partida();
                    } else {
                        $this->jm_enviar_error(405, 'Método no permitido');
                    }
                    break;
                    
                case 'estadisticas':
                    if ($method === 'GET') {
                        $this->jm_obtener_estadisticas();
                    } else {
                        $this->jm_enviar_error(405, 'Método no permitido');
                    }
                    break;
                    
                case 'historial':
                    if ($method === 'GET') {
                        $this->jm_obtener_historial();
                    } else {
                        $this->jm_enviar_error(405, 'Método no permitido');
                    }
                    break;
                    
                case 'ranking':
                    if ($method === 'GET') {
                        $this->jm_obtener_ranking();
                    } else {
                        $this->jm_enviar_error(405, 'Método no permitido');
                    }
                    break;
                    
                case 'estadisticas_globales':
                    if ($method === 'GET') {
                        $this->jm_obtener_estadisticas_globales();
                    } else {
                        $this->jm_enviar_error(405, 'Método no permitido');
                    }
                    break;
                    
                case 'partidas_recientes':
                    if ($method === 'GET') {
                        $this->jm_obtener_partidas_recientes();
                    } else {
                        $this->jm_enviar_error(405, 'Método no permitido');
                    }
                    break;
                    
                case 'usuario_actual':
                    if ($method === 'GET') {
                        $this->jm_obtener_usuario_actual();
                    } else {
                        $this->jm_enviar_error(405, 'Método no permitido');
                    }
                    break;
                    
                case 'reiniciar_estadisticas':
                    if ($method === 'POST') {
                        $this->jm_reiniciar_estadisticas();
                    } else {
                        $this->jm_enviar_error(405, 'Método no permitido');
                    }
                    break;
                    
                case 'actualizar_perfil':
                    if ($method === 'PUT') {
                        $this->jm_actualizar_perfil();
                    } else {
                        $this->jm_enviar_error(405, 'Método no permitido');
                    }
                    break;
                    
                case 'cambiar_password':
                    if ($method === 'PUT') {
                        $this->jm_cambiar_password();
                    } else {
                        $this->jm_enviar_error(405, 'Método no permitido');
                    }
                    break;
                    
                case 'health':
                    if ($method === 'GET') {
                        $this->jm_health_check();
                    } else {
                        $this->jm_enviar_error(405, 'Método no permitido');
                    }
                    break;
                    
                default:
                    $this->jm_enviar_error(404, 'Endpoint no encontrado');
            }
        } catch (Exception $e) {
            error_log("JM_Error [API]: " . $e->getMessage());
            $this->jm_enviar_error(500, 'Error interno del servidor');
        }
    }
    
    /**
     * Obtiene y decodifica los datos JSON del request
     * @return array
     */
    private function jm_obtener_datos_json() {
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            return [];
        }
        
        $datos = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->jm_enviar_error(400, 'JSON inválido: ' . json_last_error_msg());
        }
        
        return $datos;
    }
    
    /**
     * Envía una respuesta JSON exitosa
     * @param array $datos
     * @param int $codigo
     */
    private function jm_enviar_respuesta($datos, $codigo = 200) {
        http_response_code($codigo);
        echo json_encode(array_merge([
            'success' => true,
            'timestamp' => time()
        ], $datos));
        exit;
    }
    
    /**
     * Envía una respuesta de error
     * @param int $codigo
     * @param string $mensaje
     */
    private function jm_enviar_error($codigo, $mensaje) {
        http_response_code($codigo);
        echo json_encode([
            'success' => false,
            'message' => $mensaje,
            'codigo' => $codigo,
            'timestamp' => time()
        ]);
        exit;
    }
    
    /**
     * Verifica que el usuario esté autenticado
     */
    private function jm_verificar_autenticacion() {
        if (!$this->jm_usuario->jm_esta_autenticado()) {
            $this->jm_enviar_error(401, 'No autenticado. Por favor inicie sesión.');
        }
    }
    
    /**
     * Endpoint: Registrar nuevo usuario
     */
    private function jm_registrar_usuario() {
        $datos = $this->jm_obtener_datos_json();
        
        // Validar datos requeridos
        if (!isset($datos['nombre_usuario']) || !isset($datos['email']) || !isset($datos['password'])) {
            $this->jm_enviar_error(400, 'Datos incompletos. Se requieren: nombre_usuario, email, password');
        }
        
        $resultado = $this->jm_usuario->jm_registrar_usuario(
            trim($datos['nombre_usuario']),
            trim($datos['email']),
            $datos['password']
        );
        
        if ($resultado['success']) {
            $this->jm_enviar_respuesta([
                'message' => $resultado['message'],
                'usuario' => [
                    'id' => $resultado['usuario_id'],
                    'nombre_usuario' => $resultado['nombre_usuario']
                ]
            ], 201);
        } else {
            $this->jm_enviar_error(400, $resultado['message']);
        }
    }
    
    /**
     * Endpoint: Iniciar sesión
     */
    private function jm_iniciar_sesion() {
        $datos = $this->jm_obtener_datos_json();
        
        // Validar datos requeridos
        if (!isset($datos['nombre_usuario']) || !isset($datos['password'])) {
            $this->jm_enviar_error(400, 'Datos incompletos. Se requieren: nombre_usuario, password');
        }
        
        $resultado = $this->jm_usuario->jm_iniciar_sesion(
            trim($datos['nombre_usuario']),
            $datos['password']
        );
        
        if ($resultado['success']) {
            $this->jm_enviar_respuesta([
                'message' => $resultado['message'],
                'usuario' => $resultado['usuario']
            ]);
        } else {
            $this->jm_enviar_error(401, $resultado['message']);
        }
    }
    
    /**
     * Endpoint: Cerrar sesión
     */
    private function jm_cerrar_sesion() {
        $resultado = $this->jm_usuario->jm_cerrar_sesion();
        $this->jm_enviar_respuesta([
            'message' => $resultado['message']
        ]);
    }
    
    /**
     * Endpoint: Guardar partida
     */
    private function jm_guardar_partida() {
        $this->jm_verificar_autenticacion();
        $datos = $this->jm_obtener_datos_json();
        
        // Validar datos requeridos
        if (!isset($datos['eleccion_usuario']) || !isset($datos['eleccion_computadora']) || !isset($datos['resultado'])) {
            $this->jm_enviar_error(400, 'Datos de partida incompletos. Se requieren: eleccion_usuario, eleccion_computadora, resultado');
        }
        
        $usuario = $this->jm_usuario->jm_obtener_usuario_actual();
        
        $resultado = $this->jm_juego->jm_guardar_partida(
            $usuario['id'],
            $datos['eleccion_usuario'],
            $datos['eleccion_computadora'],
            $datos['resultado']
        );
        
        if ($resultado['success']) {
            $this->jm_enviar_respuesta([
                'message' => $resultado['message'],
                'partida_id' => $resultado['partida_id']
            ], 201);
        } else {
            $this->jm_enviar_error(400, $resultado['message']);
        }
    }
    
    /**
     * Endpoint: Obtener estadísticas del usuario
     */
    private function jm_obtener_estadisticas() {
        $this->jm_verificar_autenticacion();
        $usuario = $this->jm_usuario->jm_obtener_usuario_actual();
        
        $estadisticas = $this->jm_juego->jm_obtener_estadisticas_usuario($usuario['id']);
        
        $this->jm_enviar_respuesta([
            'estadisticas' => $estadisticas,
            'usuario' => $usuario
        ]);
    }
    
    /**
     * Endpoint: Obtener historial de partidas
     */
    private function jm_obtener_historial() {
        $this->jm_verificar_autenticacion();
        $usuario = $this->jm_usuario->jm_obtener_usuario_actual();
        
        $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 10;
        $historial = $this->jm_juego->jm_obtener_historial_usuario($usuario['id'], $limite);
        
        $this->jm_enviar_respuesta([
            'historial' => $historial,
            'total' => count($historial),
            'limite' => $limite
        ]);
    }
    
    /**
     * Endpoint: Obtener ranking de jugadores
     */
    private function jm_obtener_ranking() {
        $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 10;
        $ranking = $this->jm_juego->jm_obtener_ranking($limite);
        
        $this->jm_enviar_respuesta([
            'ranking' => $ranking,
            'total' => count($ranking),
            'limite' => $limite
        ]);
    }
    
    /**
     * Endpoint: Obtener estadísticas globales
     */
    private function jm_obtener_estadisticas_globales() {
        $estadisticas_globales = $this->jm_juego->jm_obtener_estadisticas_globales();
        
        $this->jm_enviar_respuesta([
            'estadisticas_globales' => $estadisticas_globales
        ]);
    }
    
    /**
     * Endpoint: Obtener partidas recientes
     */
    private function jm_obtener_partidas_recientes() {
        $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 5;
        $partidas_recientes = $this->jm_juego->jm_obtener_partidas_recientes($limite);
        
        $this->jm_enviar_respuesta([
            'partidas_recientes' => $partidas_recientes,
            'total' => count($partidas_recientes),
            'limite' => $limite
        ]);
    }
    
    /**
     * Endpoint: Obtener usuario actual
     */
    private function jm_obtener_usuario_actual() {
        if ($this->jm_usuario->jm_esta_autenticado()) {
            $usuario = $this->jm_usuario->jm_obtener_usuario_actual();
            $this->jm_enviar_respuesta([
                'usuario' => $usuario
            ]);
        } else {
            $this->jm_enviar_respuesta([
                'usuario' => null
            ]);
        }
    }
    
    /**
     * Endpoint: Reiniciar estadísticas del usuario
     */
    private function jm_reiniciar_estadisticas() {
        $this->jm_verificar_autenticacion();
        $usuario = $this->jm_usuario->jm_obtener_usuario_actual();
        
        $resultado = $this->jm_juego->jm_reiniciar_estadisticas($usuario['id']);
        
        if ($resultado['success']) {
            $this->jm_enviar_respuesta([
                'message' => $resultado['message']
            ]);
        } else {
            $this->jm_enviar_error(400, $resultado['message']);
        }
    }
    
    /**
     * Endpoint: Actualizar perfil del usuario
     */
    private function jm_actualizar_perfil() {
        $this->jm_verificar_autenticacion();
        $usuario = $this->jm_usuario->jm_obtener_usuario_actual();
        $datos = $this->jm_obtener_datos_json();
        
        // Validar datos requeridos
        if (!isset($datos['nombre_usuario']) || !isset($datos['email'])) {
            $this->jm_enviar_error(400, 'Datos incompletos. Se requieren: nombre_usuario, email');
        }
        
        $resultado = $this->jm_usuario->jm_actualizar_perfil(
            $usuario['id'],
            trim($datos['nombre_usuario']),
            trim($datos['email'])
        );
        
        if ($resultado['success']) {
            $this->jm_enviar_respuesta([
                'message' => $resultado['message']
            ]);
        } else {
            $this->jm_enviar_error(400, $resultado['message']);
        }
    }
    
    /**
     * Endpoint: Cambiar contraseña
     */
    private function jm_cambiar_password() {
        $this->jm_verificar_autenticacion();
        $usuario = $this->jm_usuario->jm_obtener_usuario_actual();
        $datos = $this->jm_obtener_datos_json();
        
        // Validar datos requeridos
        if (!isset($datos['password_actual']) || !isset($datos['nueva_password'])) {
            $this->jm_enviar_error(400, 'Datos incompletos. Se requieren: password_actual, nueva_password');
        }
        
        $resultado = $this->jm_usuario->jm_cambiar_password(
            $usuario['id'],
            $datos['password_actual'],
            $datos['nueva_password']
        );
        
        if ($resultado['success']) {
            $this->jm_enviar_respuesta([
                'message' => $resultado['message']
            ]);
        } else {
            $this->jm_enviar_error(400, $resultado['message']);
        }
    }
    
    /**
     * Endpoint: Health check de la API
     */
    private function jm_health_check() {
        $health_data = [
            'status' => 'online',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0.0',
            'endpoints' => [
                'POST /registrar' => 'Registrar nuevo usuario',
                'POST /login' => 'Iniciar sesión',
                'POST /logout' => 'Cerrar sesión',
                'POST /guardar_partida' => 'Guardar partida jugada',
                'GET /estadisticas' => 'Obtener estadísticas del usuario',
                'GET /historial' => 'Obtener historial de partidas',
                'GET /ranking' => 'Obtener ranking de jugadores',
                'GET /estadisticas_globales' => 'Obtener estadísticas globales',
                'GET /partidas_recientes' => 'Obtener partidas recientes',
                'GET /usuario_actual' => 'Obtener usuario actual',
                'POST /reiniciar_estadisticas' => 'Reiniciar estadísticas',
                'PUT /actualizar_perfil' => 'Actualizar perfil',
                'PUT /cambiar_password' => 'Cambiar contraseña'
            ]
        ];
        
        $this->jm_enviar_respuesta($health_data);
    }
}

// Manejo de errores global
set_exception_handler(function($exception) {
    error_log("JM_Error [API Global]: " . $exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => $exception->getMessage(),
        'timestamp' => time()
    ]);
    exit;
});

// Inicializar y ejecutar la API
try {
    $api = new JM_API();
    $api->jm_procesar_request();
} catch (Exception $e) {
    error_log("JM_Error [API Init]: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al inicializar la API',
        'timestamp' => time()
    ]);
}
?>