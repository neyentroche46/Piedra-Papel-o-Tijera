<?php
/**
 * JM_Juego - Clase principal del juego Piedra, Papel o Tijera
 * Prefijo: jm (iniciales del desarrollador)
 * Proyecto: Piedra, Papel o Tijera - Parcial PLP3
 * Ubicación: classes/juego.php
 */

class JM_Juego {
    private $jm_opciones;
    private $jm_reglas;
    private $jm_estadisticas;
    
    public function __construct() {
        $this->jm_inicializar_opciones();
        $this->jm_inicializar_reglas();
        $this->jm_inicializar_estadisticas();
    }
    
    /**
     * Inicializa las opciones disponibles del juego
     */
    private function jm_inicializar_opciones() {
        $this->jm_opciones = [
            'piedra' => [
                'nombre' => 'Piedra',
                'emoji' => '✊',
                'color' => '#94a3b8',
                'vence_a' => ['tijera']
            ],
            'papel' => [
                'nombre' => 'Papel', 
                'emoji' => '✋',
                'color' => '#f8fafc',
                'vence_a' => ['piedra']
            ],
            'tijera' => [
                'nombre' => 'Tijera',
                'emoji' => '✌️',
                'color' => '#fecaca',
                'vence_a' => ['papel']
            ]
        ];
    }
    
    /**
     * Inicializa las reglas del juego
     */
    private function jm_inicializar_reglas() {
        $this->jm_reglas = [
            'piedra' => 'tijera',    // Piedra vence a Tijera
            'tijera' => 'papel',     // Tijera vence a Papel
            'papel' => 'piedra'      // Papel vence a Piedra
        ];
    }
    
    /**
     * Inicializa las estadísticas del juego
     */
    private function jm_inicializar_estadisticas() {
        $this->jm_estadisticas = [
            'partidas_totales' => 0,
            'victorias_usuario' => 0,
            'victorias_computadora' => 0,
            'empates' => 0,
            'racha_actual' => 0,
            'mejor_racha' => 0,
            'historial' => []
        ];
    }
    
    /**
     * Juega una partida de Piedra, Papel o Tijera
     * @param string $eleccion_usuario
     * @return array
     */
    public function jm_jugar($eleccion_usuario) {
        // Validar elección del usuario
        if (!$this->jm_validar_eleccion($eleccion_usuario)) {
            return [
                'success' => false,
                'message' => 'Elección no válida'
            ];
        }
        
        // Generar elección de la computadora
        $eleccion_computadora = $this->jm_generar_eleccion_computadora();
        
        // Determinar el resultado
        $resultado = $this->jm_determinar_resultado($eleccion_usuario, $eleccion_computadora);
        
        // Actualizar estadísticas
        $this->jm_actualizar_estadisticas($resultado);
        
        // Guardar en historial
        $this->jm_guardar_historial($eleccion_usuario, $eleccion_computadora, $resultado);
        
        return [
            'success' => true,
            'eleccion_usuario' => $eleccion_usuario,
            'eleccion_computadora' => $eleccion_computadora,
            'resultado' => $resultado,
            'emoji_usuario' => $this->jm_opciones[$eleccion_usuario]['emoji'],
            'emoji_computadora' => $this->jm_opciones[$eleccion_computadora]['emoji'],
            'mensaje' => $this->jm_generar_mensaje_resultado($eleccion_usuario, $eleccion_computadora, $resultado)
        ];
    }
    
    /**
     * Valida que la elección del usuario sea válida
     * @param string $eleccion
     * @return bool
     */
    private function jm_validar_eleccion($eleccion) {
        return array_key_exists($eleccion, $this->jm_opciones);
    }
    
    /**
     * Genera una elección aleatoria para la computadora
     * @return string
     */
    private function jm_generar_eleccion_computadora() {
        $opciones = array_keys($this->jm_opciones);
        $indice_aleatorio = array_rand($opciones);
        return $opciones[$indice_aleatorio];
    }
    
    /**
     * Determina el resultado de la partida
     * @param string $usuario
     * @param string $computadora
     * @return string
     */
    private function jm_determinar_resultado($usuario, $computadora) {
        if ($usuario === $computadora) {
            return 'empate';
        }
        
        return $this->jm_reglas[$usuario] === $computadora ? 'ganaste' : 'perdiste';
    }
    
    /**
     * Genera un mensaje descriptivo del resultado
     * @param string $usuario
     * @param string $computadora
     * @param string $resultado
     * @return string
     */
    private function jm_generar_mensaje_resultado($usuario, $computadora, $resultado) {
        $nombre_usuario = $this->jm_opciones[$usuario]['nombre'];
        $nombre_computadora = $this->jm_opciones[$computadora]['nombre'];
        $emoji_usuario = $this->jm_opciones[$usuario]['emoji'];
        $emoji_computadora = $this->jm_opciones[$computadora]['emoji'];
        
        switch ($resultado) {
            case 'ganaste':
                return "¡GANASTE! $emoji_usuario $nombre_usuario vence a $emoji_computadora $nombre_computadora";
            case 'perdiste':
                return "Perdiste... $emoji_computadora $nombre_computadora vence a $emoji_usuario $nombre_usuario";
            case 'empate':
                return "¡EMPATE! $emoji_usuario vs $emoji_computadora";
            default:
                return "Resultado no determinado";
        }
    }
    
    /**
     * Actualiza las estadísticas del juego
     * @param string $resultado
     */
    private function jm_actualizar_estadisticas($resultado) {
        $this->jm_estadisticas['partidas_totales']++;
        
        switch ($resultado) {
            case 'ganaste':
                $this->jm_estadisticas['victorias_usuario']++;
                $this->jm_estadisticas['racha_actual']++;
                if ($this->jm_estadisticas['racha_actual'] > $this->jm_estadisticas['mejor_racha']) {
                    $this->jm_estadisticas['mejor_racha'] = $this->jm_estadisticas['racha_actual'];
                }
                break;
            case 'perdiste':
                $this->jm_estadisticas['victorias_computadora']++;
                $this->jm_estadisticas['racha_actual'] = 0;
                break;
            case 'empate':
                $this->jm_estadisticas['empates']++;
                break;
        }
    }
    
    /**
     * Guarda la partida en el historial
     * @param string $usuario
     * @param string $computadora
     * @param string $resultado
     */
    private function jm_guardar_historial($usuario, $computadora, $resultado) {
        $partida = [
            'timestamp' => time(),
            'fecha' => date('Y-m-d H:i:s'),
            'usuario' => $usuario,
            'computadora' => $computadora,
            'resultado' => $resultado,
            'emoji_usuario' => $this->jm_opciones[$usuario]['emoji'],
            'emoji_computadora' => $this->jm_opciones[$computadora]['emoji']
        ];
        
        array_unshift($this->jm_estadisticas['historial'], $partida);
        
        // Mantener solo las últimas 50 partidas
        if (count($this->jm_estadisticas['historial']) > 50) {
            array_pop($this->jm_estadisticas['historial']);
        }
    }
    
    /**
     * Obtiene las estadísticas actuales del juego
     * @return array
     */
    public function jm_obtener_estadisticas() {
        $estadisticas = $this->jm_estadisticas;
        
        // Calcular porcentajes
        $total_partidas = $estadisticas['partidas_totales'];
        if ($total_partidas > 0) {
            $estadisticas['porcentaje_victorias'] = round(($estadisticas['victorias_usuario'] / $total_partidas) * 100, 1);
            $estadisticas['porcentaje_derrotas'] = round(($estadisticas['victorias_computadora'] / $total_partidas) * 100, 1);
            $estadisticas['porcentaje_empates'] = round(($estadisticas['empates'] / $total_partidas) * 100, 1);
        } else {
            $estadisticas['porcentaje_victorias'] = 0;
            $estadisticas['porcentaje_derrotas'] = 0;
            $estadisticas['porcentaje_empates'] = 0;
        }
        
        return $estadisticas;
    }
    
    /**
     * Obtiene el historial de partidas
     * @param int $limite
     * @return array
     */
    public function jm_obtener_historial($limite = 10) {
        if ($limite <= 0) {
            return $this->jm_estadisticas['historial'];
        }
        
        return array_slice($this->jm_estadisticas['historial'], 0, $limite);
    }
    
    /**
     * Obtiene las opciones disponibles del juego
     * @return array
     */
    public function jm_obtener_opciones() {
        return $this->jm_opciones;
    }
    
    /**
     * Obtiene las reglas del juego
     * @return array
     */
    public function jm_obtener_reglas() {
        return $this->jm_reglas;
    }
    
    /**
     * Reinicia las estadísticas del juego
     * @return array
     */
    public function jm_reiniciar_estadisticas() {
        $this->jm_inicializar_estadisticas();
        
        return [
            'success' => true,
            'message' => 'Estadísticas reiniciadas correctamente'
        ];
    }
    
    /**
     * Obtiene un análisis de las tendencias de juego
     * @return array
     */
    public function jm_obtener_analisis() {
        $historial = $this->jm_estadisticas['historial'];
        $analisis = [
            'elecciones_usuario' => [],
            'elecciones_computadora' => [],
            'resultados_por_eleccion' => [],
            'partidas_hoy' => 0
        ];
        
        $hoy = date('Y-m-d');
        
        foreach ($historial as $partida) {
            // Conteo de elecciones del usuario
            if (!isset($analisis['elecciones_usuario'][$partida['usuario']])) {
                $analisis['elecciones_usuario'][$partida['usuario']] = 0;
            }
            $analisis['elecciones_usuario'][$partida['usuario']]++;
            
            // Conteo de elecciones de la computadora
            if (!isset($analisis['elecciones_computadora'][$partida['computadora']])) {
                $analisis['elecciones_computadora'][$partida['computadora']] = 0;
            }
            $analisis['elecciones_computadora'][$partida['computadora']]++;
            
            // Resultados por elección
            $clave = $partida['usuario'] . '_vs_' . $partida['computadora'];
            if (!isset($analisis['resultados_por_eleccion'][$clave])) {
                $analisis['resultados_por_eleccion'][$clave] = 0;
            }
            $analisis['resultados_por_eleccion'][$clave]++;
            
            // Partidas hoy
            if (date('Y-m-d', $partida['timestamp']) === $hoy) {
                $analisis['partidas_hoy']++;
            }
        }
        
        // Elección más frecuente del usuario
        if (!empty($analisis['elecciones_usuario'])) {
            $analisis['eleccion_favorita'] = array_keys($analisis['elecciones_usuario'], max($analisis['elecciones_usuario']))[0];
        }
        
        return $analisis;
    }
    
    /**
     * Obtiene sugerencias de juego basadas en el historial
     * @return array
     */
    public function jm_obtener_sugerencias() {
        $analisis = $this->jm_obtener_analisis();
        $sugerencias = [];
        
        if (empty($analisis['elecciones_computadora'])) {
            return [
                'sugerencia' => 'piedra',
                'razon' => 'Es una elección equilibrada para comenzar'
            ];
        }
        
        // Encontrar la elección más común de la computadora
        $eleccion_comun_computadora = array_keys(
            $analisis['elecciones_computadora'], 
            max($analisis['elecciones_computadora'])
        )[0];
        
        // Sugerir la opción que vence a la elección común de la computadora
        $sugerencia = $this->jm_reglas[$eleccion_comun_computadora];
        
        return [
            'sugerencia' => $sugerencia,
            'razon' => "La computadora elige frecuentemente " . $this->jm_opciones[$eleccion_comun_computadora]['nombre'],
            'contra' => $eleccion_comun_computadora
        ];
    }
    
    /**
     * Simula múltiples partidas para análisis
     * @param int $numero_partidas
     * @return array
     */
    public function jm_simular_partidas($numero_partidas = 100) {
        if ($numero_partidas <= 0 || $numero_partidas > 10000) {
            return [
                'success' => false,
                'message' => 'Número de partidas no válido'
            ];
        }
        
        $resultados_simulacion = [
            'victorias_usuario' => 0,
            'victorias_computadora' => 0,
            'empates' => 0,
            'distribucion_opciones' => []
        ];
        
        $opciones = array_keys($this->jm_opciones);
        
        for ($i = 0; $i < $numero_partidas; $i++) {
            $eleccion_usuario = $opciones[array_rand($opciones)];
            $eleccion_computadora = $opciones[array_rand($opciones)];
            $resultado = $this->jm_determinar_resultado($eleccion_usuario, $eleccion_computadora);
            
            switch ($resultado) {
                case 'ganaste':
                    $resultados_simulacion['victorias_usuario']++;
                    break;
                case 'perdiste':
                    $resultados_simulacion['victorias_computadora']++;
                    break;
                case 'empate':
                    $resultados_simulacion['empates']++;
                    break;
            }
            
            // Distribución de opciones
            if (!isset($resultados_simulacion['distribucion_opciones'][$eleccion_usuario])) {
                $resultados_simulacion['distribucion_opciones'][$eleccion_usuario] = 0;
            }
            $resultados_simulacion['distribucion_opciones'][$eleccion_usuario]++;
        }
        
        // Calcular porcentajes
        $resultados_simulacion['porcentaje_victorias'] = round(($resultados_simulacion['victorias_usuario'] / $numero_partidas) * 100, 2);
        $resultados_simulacion['porcentaje_derrotas'] = round(($resultados_simulacion['victorias_computadora'] / $numero_partidas) * 100, 2);
        $resultados_simulacion['porcentaje_empates'] = round(($resultados_simulacion['empates'] / $numero_partidas) * 100, 2);
        
        return [
            'success' => true,
            'numero_partidas' => $numero_partidas,
            'resultados' => $resultados_simulacion
        ];
    }
    
    /**
     * Exporta los datos del juego en formato JSON
     * @return string
     */
    public function jm_exportar_datos() {
        $datos = [
            'estadisticas' => $this->jm_obtener_estadisticas(),
            'historial' => $this->jm_estadisticas['historial'],
            'analisis' => $this->jm_obtener_analisis(),
            'fecha_exportacion' => date('Y-m-d H:i:s'),
            'version' => '1.0.0'
        ];
        
        return json_encode($datos, JSON_PRETTY_PRINT);
    }
    
    /**
     * Obtiene información sobre una opción específica
     * @param string $opcion
     * @return array|null
     */
    public function jm_obtener_info_opcion($opcion) {
        if (!$this->jm_validar_eleccion($opcion)) {
            return null;
        }
        
        $info = $this->jm_opciones[$opcion];
        $info['debil_contra'] = array_keys($this->jm_reglas, $opcion)[0];
        $info['fuerte_contra'] = $this->jm_reglas[$opcion];
        
        return $info;
    }
    
    /**
     * Obtiene un resumen del rendimiento del jugador
     * @return array
     */
    public function jm_obtener_resumen_rendimiento() {
        $estadisticas = $this->jm_obtener_estadisticas();
        $analisis = $this->jm_obtener_analisis();
        
        $resumen = [
            'nivel' => 'Principiante',
            'puntuacion' => 0,
            'fortalezas' => [],
            'debilidades' => []
        ];
        
        // Calcular puntuación basada en victorias y racha
        $resumen['puntuacion'] = ($estadisticas['victorias_usuario'] * 10) + ($estadisticas['mejor_racha'] * 5);
        
        // Determinar nivel
        if ($estadisticas['partidas_totales'] >= 50 && $estadisticas['porcentaje_victorias'] >= 60) {
            $resumen['nivel'] = 'Experto';
        } elseif ($estadisticas['partidas_totales'] >= 20 && $estadisticas['porcentaje_victorias'] >= 40) {
            $resumen['nivel'] = 'Intermedio';
        }
        
        // Identificar fortalezas y debilidades
        if (!empty($analisis['elecciones_usuario'])) {
            $eleccion_favorita = $analisis['eleccion_favorita'] ?? null;
            if ($eleccion_favorita) {
                $resumen['fortalezas'][] = "Eres fuerte usando " . $this->jm_opciones[$eleccion_favorita]['nombre'];
            }
        }
        
        if ($estadisticas['racha_actual'] >= 3) {
            $resumen['fortalezas'][] = "¡Racha actual de {$estadisticas['racha_actual']} victorias!";
        }
        
        if ($estadisticas['porcentaje_empates'] > 30) {
            $resumen['debilidades'][] = "Muchos empates, intenta ser más impredecible";
        }
        
        return $resumen;
    }
}

// Función auxiliar para crear instancia del juego
function jm_crear_juego() {
    return new JM_Juego();
}

?>