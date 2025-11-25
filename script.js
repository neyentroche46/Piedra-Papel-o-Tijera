// ===== SISTEMA DE TEMA OSCURO/CLARO =====
class JM_ThemeManager {
    constructor() {
        this.jm_currentTheme = localStorage.getItem('jm_theme') || 'light';
        this.jm_initTheme();
    }
    
    jm_initTheme() {
        document.documentElement.setAttribute('data-theme', this.jm_currentTheme);
        this.jm_updateThemeToggle();
    }
    
    jm_toggleTheme() {
        this.jm_currentTheme = this.jm_currentTheme === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', this.jm_currentTheme);
        localStorage.setItem('jm_theme', this.jm_currentTheme);
        this.jm_updateThemeToggle();
    }
    
    jm_updateThemeToggle() {
        const toggle = document.querySelector('.jm_theme_toggle');
        const icon = toggle.querySelector('.jm_theme_icon');
        
        if (this.jm_currentTheme === 'dark') {
            icon.textContent = '☀️';
            icon.style.transform = 'rotate(180deg)';
        } else {
            icon.textContent = '🌙';
            icon.style.transform = 'rotate(0deg)';
        }
    }
}

// ===== SISTEMA DE PUNTUACIÓN Y ESTADÍSTICAS =====
class JM_PuntuacionManager {
    constructor() {
        this.jm_puntuacion = this.jm_cargarPuntuacion();
        this.jm_historial = this.jm_cargarHistorial();
    }
    
    jm_cargarPuntuacion() {
        const guardado = localStorage.getItem('jm_puntuacion');
        if (guardado) {
            return JSON.parse(guardado);
        }
        
        return {
            victorias: 0,
            derrotas: 0,
            empates: 0,
            racha_actual: 0,
            mejor_racha: 0,
            total_partidas: 0
        };
    }
    
    jm_cargarHistorial() {
        const guardado = localStorage.getItem('jm_historial');
        return guardado ? JSON.parse(guardado) : [];
    }
    
    jm_guardarPuntuacion() {
        localStorage.setItem('jm_puntuacion', JSON.stringify(this.jm_puntuacion));
    }
    
    jm_guardarHistorial() {
        // Mantener solo las últimas 20 partidas
        if (this.jm_historial.length > 20) {
            this.jm_historial = this.jm_historial.slice(0, 20);
        }
        localStorage.setItem('jm_historial', JSON.stringify(this.jm_historial));
    }
    
    jm_actualizarPuntuacion(resultado) {
        this.jm_puntuacion.total_partidas++;
        
        switch(resultado) {
            case 'ganaste':
                this.jm_puntuacion.victorias++;
                this.jm_puntuacion.racha_actual++;
                if (this.jm_puntuacion.racha_actual > this.jm_puntuacion.mejor_racha) {
                    this.jm_puntuacion.mejor_racha = this.jm_puntuacion.racha_actual;
                }
                break;
            case 'perdiste':
                this.jm_puntuacion.derrotas++;
                this.jm_puntuacion.racha_actual = 0;
                break;
            case 'empate':
                this.jm_puntuacion.empates++;
                break;
        }
        
        this.jm_guardarPuntuacion();
    }
    
    jm_agregarAlHistorial(partida) {
        this.jm_historial.unshift({
            id: Date.now(),
            fecha: new Date().toLocaleTimeString(),
            ...partida
        });
        this.jm_guardarHistorial();
    }
    
    jm_reiniciarEstadisticas() {
        this.jm_puntuacion = {
            victorias: 0,
            derrotas: 0,
            empates: 0,
            racha_actual: 0,
            mejor_racha: 0,
            total_partidas: 0
        };
        this.jm_historial = [];
        this.jm_guardarPuntuacion();
        this.jm_guardarHistorial();
        return true;
    }
}

// ===== ALGORITMO PRINCIPAL DEL JUEGO =====
class JM_JuegoManager {
    constructor(puntuacionManager) {
        this.puntuacionManager = puntuacionManager;
    }
    
    jm_obtenerJugadaComputadora() {
        const opciones = ['piedra', 'papel', 'tijera'];
        const indiceAleatorio = Math.floor(Math.random() * 3);
        return opciones[indiceAleatorio];
    }
    
    jm_determinarGanador(jugador, computadora) {
        if (jugador === computadora) {
            return 'empate';
        }
        
        // Reglas del juego corregidas
        if (
            (jugador === 'piedra' && computadora === 'tijera') ||
            (jugador === 'papel' && computadora === 'piedra') ||
            (jugador === 'tijera' && computadora === 'papel')
        ) {
            return 'ganaste';
        }
        
        return 'perdiste';
    }
    
    jm_obtenerIcono(jugada) {
        const iconos = {
            'piedra': '✊',
            'papel': '✋',
            'tijera': '✌️'
        };
        return iconos[jugada];
    }
    
    jm_obtenerTextoJugada(jugada) {
        const textos = {
            'piedra': 'Piedra',
            'papel': 'Papel',
            'tijera': 'Tijera'
        };
        return textos[jugada];
    }
    
    jm_jugar(seleccionUsuario) {
        const seleccionComputadora = this.jm_obtenerJugadaComputadora();
        const resultado = this.jm_determinarGanador(seleccionUsuario, seleccionComputadora);
        
        // Crear objeto de partida
        const partida = {
            jugador: seleccionUsuario,
            computadora: seleccionComputadora,
            resultado: resultado,
            icono_jugador: this.jm_obtenerIcono(seleccionUsuario),
            icono_computadora: this.jm_obtenerIcono(seleccionComputadora),
            texto_jugador: this.jm_obtenerTextoJugada(seleccionUsuario),
            texto_computadora: this.jm_obtenerTextoJugada(seleccionComputadora)
        };
        
        // Actualizar puntuación
        this.puntuacionManager.jm_actualizarPuntuacion(resultado);
        
        // Agregar al historial
        this.puntuacionManager.jm_agregarAlHistorial(partida);
        
        return {
            seleccionUsuario,
            seleccionComputadora,
            resultado,
            partida: partida
        };
    }
}

// ===== SISTEMA DE INTERFAZ Y VISUALIZACIÓN =====
class JM_InterfaceManager {
    constructor(juegoManager, puntuacionManager) {
        this.juegoManager = juegoManager;
        this.puntuacionManager = puntuacionManager;
        this.jm_apiManager = new JM_ApiManager();
    }
    
    jm_mostrarResultado(seleccionUsuario, seleccionComputadora, resultado, partida) {
        const mensajeElement = document.getElementById('jm_mensaje_resultado');
        const iconoUsuario = this.juegoManager.jm_obtenerIcono(seleccionUsuario);
        const iconoComputadora = this.juegoManager.jm_obtenerIcono(seleccionComputadora);
        const textoUsuario = this.juegoManager.jm_obtenerTextoJugada(seleccionUsuario);
        const textoComputadora = this.juegoManager.jm_obtenerTextoJugada(seleccionComputadora);
        
        let mensaje = '';
        let claseResultado = '';
        
        switch(resultado) {
            case 'ganaste':
                mensaje = `¡GANASTE! ${iconoUsuario} ${textoUsuario} vence a ${iconoComputadora} ${textoComputadora}`;
                claseResultado = 'jm_ganador';
                break;
            case 'perdiste':
                mensaje = `¡PERDISTE! ${iconoComputadora} ${textoComputadora} vence a ${iconoUsuario} ${textoUsuario}`;
                claseResultado = 'jm_perdedor';
                break;
            case 'empate':
                mensaje = `¡EMPATE! ${iconoUsuario} ${textoUsuario} vs ${iconoComputadora} ${textoComputadora}`;
                claseResultado = 'jm_empate_resultado';
                break;
        }
        
        mensajeElement.textContent = mensaje;
        mensajeElement.className = 'jm_mensaje_resultado jm_animacion_resultado ' + claseResultado;
        
        // Efectos visuales en los botones
        this.jm_animarSeleccion(seleccionUsuario, resultado);
        
        // Remover animación después de que termine
        setTimeout(() => {
            mensajeElement.classList.remove('jm_animacion_resultado');
        }, 800);
        
        // Intentar guardar en la base de datos si el usuario está autenticado
        this.jm_intentarGuardarEnBD(partida);
    }
    
    jm_animarSeleccion(seleccionUsuario, resultado) {
        const botonSeleccionado = document.querySelector(`[data-opcion="${seleccionUsuario}"]`);
        
        if (botonSeleccionado) {
            // Remover clases anteriores
            botonSeleccionado.classList.remove('jm_animacion_ganador', 'jm_animacion_perdedor', 'jm_animacion_empate');
            
            // Agregar clase según resultado
            switch(resultado) {
                case 'ganaste':
                    botonSeleccionado.classList.add('jm_animacion_ganador');
                    break;
                case 'perdiste':
                    botonSeleccionado.classList.add('jm_animacion_perdedor');
                    break;
                case 'empate':
                    botonSeleccionado.classList.add('jm_animacion_empate');
                    break;
            }
            
            // Remover animación después de 1 segundo
            setTimeout(() => {
                botonSeleccionado.classList.remove('jm_animacion_ganador', 'jm_animacion_perdedor', 'jm_animacion_empate');
            }, 1000);
        }
    }
    
    async jm_intentarGuardarEnBD(partida) {
        try {
            // Verificar si hay un usuario autenticado (simulado por ahora)
            const usuarioAutenticado = localStorage.getItem('jm_usuario_autenticado');
            
            if (usuarioAutenticado) {
                const resultado = await this.jm_apiManager.jm_guardarPartida({
                    eleccion_usuario: partida.jugador,
                    eleccion_computadora: partida.computadora,
                    resultado: partida.resultado
                });
                
                if (resultado.success) {
                    console.log('Partida guardada en base de datos');
                }
            }
        } catch (error) {
            console.log('Partida guardada localmente (sin conexión a BD)');
        }
    }
    
    jm_calcularPorcentajes() {
        const total = this.puntuacionManager.jm_puntuacion.total_partidas;
        if (total === 0) return { victorias: 0, derrotas: 0, empates: 0 };
        
        return {
            victorias: ((this.puntuacionManager.jm_puntuacion.victorias / total) * 100).toFixed(1),
            derrotas: ((this.puntuacionManager.jm_puntuacion.derrotas / total) * 100).toFixed(1),
            empates: ((this.puntuacionManager.jm_puntuacion.empates / total) * 100).toFixed(1)
        };
    }
    
    jm_actualizarEstadisticas() {
        const p = this.puntuacionManager.jm_puntuacion;
        const porcentajes = this.jm_calcularPorcentajes();
        
        // Actualizar contadores principales
        document.getElementById('jm_contador_victorias').textContent = p.victorias;
        document.getElementById('jm_contador_derrotas').textContent = p.derrotas;
        document.getElementById('jm_contador_empates').textContent = p.empates;
        document.getElementById('jm_total_partidas').textContent = p.total_partidas;
        
        // Actualizar porcentajes
        document.getElementById('jm_porcentaje_victorias').textContent = porcentajes.victorias + '%';
        document.getElementById('jm_porcentaje_derrotas').textContent = porcentajes.derrotas + '%';
        document.getElementById('jm_porcentaje_empates').textContent = porcentajes.empates + '%';
        
        // Actualizar rachas
        document.getElementById('jm_racha_actual').textContent = p.racha_actual;
        document.getElementById('jm_mejor_racha').textContent = p.mejor_racha;
        
        // Actualizar barras de progreso
        this.jm_actualizarBarrasProgreso(porcentajes);
    }
    
    jm_actualizarBarrasProgreso(porcentajes) {
        document.getElementById('jm_barra_victorias').style.width = porcentajes.victorias + '%';
        document.getElementById('jm_barra_derrotas').style.width = porcentajes.derrotas + '%';
        document.getElementById('jm_barra_empates').style.width = porcentajes.empates + '%';
    }
    
    jm_mostrarHistorial() {
        const contenedor = document.getElementById('jm_historial_partidas');
        const historial = this.puntuacionManager.jm_historial;
        
        if (historial.length === 0) {
            contenedor.innerHTML = '<div class="jm_partida_item jm_empate"><span class="jm_text_muted">No hay partidas jugadas aún</span></div>';
            return;
        }
        
        contenedor.innerHTML = historial.map(partida => {
            const textoResultado = partida.resultado === 'ganaste' ? 'GANASTE' : 
                                 partida.resultado === 'perdiste' ? 'PERDISTE' : 'EMPATE';
            
            return `
                <div class="jm_partida_item jm_${partida.resultado}">
                    <span class="jm_partida_hora">${partida.fecha}</span>
                    <span class="jm_partida_icono">${partida.icono_jugador}</span>
                    <span class="jm_partida_vs">VS</span>
                    <span class="jm_partida_icono">${partida.icono_computadora}</span>
                    <span class="jm_partida_resultado">${textoResultado}</span>
                </div>
            `;
        }).join('');
    }
    
    jm_exportarDatos() {
        const datos = {
            puntuacion: this.puntuacionManager.jm_puntuacion,
            historial: this.puntuacionManager.jm_historial,
            fechaExportacion: new Date().toLocaleString(),
            version: '1.0'
        };
        
        const blob = new Blob([JSON.stringify(datos, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `estadisticas_piedra_papel_tijera_${new Date().getTime()}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        this.jm_mostrarNotificacion('Datos exportados exitosamente', 'success');
    }
    
    jm_mostrarNotificacion(mensaje, tipo = 'info') {
        // Crear elemento de notificación
        const notificacion = document.createElement('div');
        notificacion.className = `jm_notificacion jm_notificacion_${tipo}`;
        notificacion.innerHTML = `
            <span>${mensaje}</span>
            <button class="jm_notificacion_cerrar">&times;</button>
        `;
        
        // Estilos para la notificación
        notificacion.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${tipo === 'success' ? '#10b981' : tipo === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: jm_slideIn 0.3s ease;
        `;
        
        document.body.appendChild(notificacion);
        
        // Auto-eliminar después de 3 segundos
        setTimeout(() => {
            if (notificacion.parentNode) {
                notificacion.style.animation = 'jm_slideOut 0.3s ease';
                setTimeout(() => notificacion.parentNode.removeChild(notificacion), 300);
            }
        }, 3000);
        
        // Cerrar manualmente
        notificacion.querySelector('.jm_notificacion_cerrar').onclick = () => {
            notificacion.parentNode.removeChild(notificacion);
        };
    }
}

// ===== SISTEMA DE API (SIMULADO) =====
class JM_ApiManager {
    constructor() {
        this.jm_baseUrl = 'jm_api.php';
    }
    
    async jm_guardarPartida(partidaData) {
        // Simulación de llamada a API
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve({ 
                    success: true, 
                    message: 'Partida guardada exitosamente',
                    partida_id: Date.now()
                });
            }, 100);
        });
    }
}

// ===== SISTEMA DE AUTENTICACIÓN (SIMULADO) =====
class JM_AuthManager {
    constructor() {
        this.jm_usuarioActual = null;
        this.jm_cargarUsuario();
    }
    
    jm_cargarUsuario() {
        const usuarioGuardado = localStorage.getItem('jm_usuario_actual');
        if (usuarioGuardado) {
            this.jm_usuarioActual = JSON.parse(usuarioGuardado);
            localStorage.setItem('jm_usuario_autenticado', 'true');
        }
    }
    
    jm_iniciarSesion(usuario, password) {
        // Simulación de autenticación
        if (usuario && password.length >= 6) {
            this.jm_usuarioActual = {
                nombre: usuario,
                email: `${usuario}@ejemplo.com`,
                id: Date.now()
            };
            
            localStorage.setItem('jm_usuario_actual', JSON.stringify(this.jm_usuarioActual));
            localStorage.setItem('jm_usuario_autenticado', 'true');
            
            return { success: true, message: 'Inicio de sesión exitoso' };
        }
        return { success: false, message: 'Credenciales incorrectas' };
    }
    
    jm_registrarUsuario(usuario, email, password) {
        // Simulación de registro
        if (usuario && email && password.length >= 6) {
            this.jm_usuarioActual = {
                nombre: usuario,
                email: email,
                id: Date.now()
            };
            
            localStorage.setItem('jm_usuario_actual', JSON.stringify(this.jm_usuarioActual));
            localStorage.setItem('jm_usuario_autenticado', 'true');
            
            return { success: true, message: 'Usuario registrado exitosamente' };
        }
        return { success: false, message: 'Error en el registro' };
    }
    
    jm_cerrarSesion() {
        this.jm_usuarioActual = null;
        localStorage.removeItem('jm_usuario_actual');
        localStorage.removeItem('jm_usuario_autenticado');
        return { success: true, message: 'Sesión cerrada' };
    }
}

// ===== INICIALIZACIÓN DE LA APLICACIÓN =====
class JM_Aplicacion {
    constructor() {
        this.themeManager = new JM_ThemeManager();
        this.puntuacionManager = new JM_PuntuacionManager();
        this.juegoManager = new JM_JuegoManager(this.puntuacionManager);
        this.interfaceManager = new JM_InterfaceManager(this.juegoManager, this.puntuacionManager);
        this.authManager = new JM_AuthManager();
        
        this.jm_inicializarEventListeners();
        this.jm_actualizarInterfaz();
    }
    
    jm_inicializarEventListeners() {
        // Botones de juego - CORREGIDO: Usar event delegation
        document.addEventListener('click', (e) => {
            if (e.target.closest('.jm_opcion')) {
                const boton = e.target.closest('.jm_opcion');
                const opcion = boton.dataset.opcion;
                this.jm_procesarJugada(opcion);
            }
        });
        
        // Botón reiniciar estadísticas
        document.getElementById('jm_reiniciar').addEventListener('click', () => {
            this.jm_reiniciarEstadisticas();
        });
        
        // Botón exportar datos
        document.getElementById('jm_exportar').addEventListener('click', () => {
            this.interfaceManager.jm_exportarDatos();
        });
        
        // Toggle de tema
        document.querySelector('.jm_theme_toggle').addEventListener('click', () => {
            this.themeManager.jm_toggleTheme();
        });
        
        // Formularios de autenticación
        document.getElementById('jm_login_form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.jm_procesarLogin();
        });
        
        document.getElementById('jm_register_form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.jm_procesarRegistro();
        });
        
        // Toggle entre login y registro
        document.getElementById('jm_toggle_registro').addEventListener('click', (e) => {
            e.preventDefault();
            this.jm_toggleFormularioAuth();
        });
    }
    
    jm_procesarJugada(seleccionUsuario) {
        const resultado = this.juegoManager.jm_jugar(seleccionUsuario);
        this.interfaceManager.jm_mostrarResultado(
            resultado.seleccionUsuario,
            resultado.seleccionComputadora,
            resultado.resultado,
            resultado.partida
        );
        this.jm_actualizarInterfaz();
    }
    
    jm_reiniciarEstadisticas() {
        if (confirm('¿Estás seguro de que quieres reiniciar todas las estadísticas? Esta acción no se puede deshacer.')) {
            this.puntuacionManager.jm_reiniciarEstadisticas();
            this.interfaceManager.jm_mostrarNotificacion('Estadísticas reiniciadas exitosamente', 'success');
            this.jm_actualizarInterfaz();
        }
    }
    
    jm_procesarLogin() {
        const usuario = document.getElementById('jm_login_usuario').value;
        const password = document.getElementById('jm_login_password').value;
        
        const resultado = this.authManager.jm_iniciarSesion(usuario, password);
        
        if (resultado.success) {
            this.interfaceManager.jm_mostrarNotificacion(resultado.message, 'success');
            document.getElementById('jm_login_form').reset();
        } else {
            this.interfaceManager.jm_mostrarNotificacion(resultado.message, 'error');
        }
    }
    
    jm_procesarRegistro() {
        const usuario = document.getElementById('jm_register_usuario').value;
        const email = document.getElementById('jm_register_email').value;
        const password = document.getElementById('jm_register_password').value;
        
        const resultado = this.authManager.jm_registrarUsuario(usuario, email, password);
        
        if (resultado.success) {
            this.interfaceManager.jm_mostrarNotificacion(resultado.message, 'success');
            document.getElementById('jm_register_form').reset();
            this.jm_toggleFormularioAuth(); // Volver al login
        } else {
            this.interfaceManager.jm_mostrarNotificacion(resultado.message, 'error');
        }
    }
    
    jm_toggleFormularioAuth() {
        const loginForm = document.getElementById('jm_login_form');
        const registerForm = document.getElementById('jm_register_form');
        const toggleLink = document.getElementById('jm_toggle_registro');
        
        if (loginForm.classList.contains('jm_hidden')) {
            loginForm.classList.remove('jm_hidden');
            registerForm.classList.add('jm_hidden');
            toggleLink.textContent = 'Regístrate aquí';
        } else {
            loginForm.classList.add('jm_hidden');
            registerForm.classList.remove('jm_hidden');
            toggleLink.textContent = 'Inicia sesión aquí';
        }
    }
    
    jm_actualizarInterfaz() {
        this.interfaceManager.jm_actualizarEstadisticas();
        this.interfaceManager.jm_mostrarHistorial();
    }
}

// ===== INICIALIZAR LA APLICACIÓN CUANDO EL DOM ESTÉ LISTO =====
document.addEventListener('DOMContentLoaded', function() {
    new JM_Aplicacion();
});

// ===== ESTILOS CSS PARA ANIMACIONES (agregar al CSS existente) =====
const estiloAnimaciones = document.createElement('style');
estiloAnimaciones.textContent = `
    @keyframes jm_slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes jm_slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .jm_notificacion_cerrar {
        background: none;
        border: none;
        color: white;
        font-size: 18px;
        cursor: pointer;
        padding: 0;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Animaciones para las selecciones */
    .jm_animacion_ganador {
        animation: jm_ganadorAnim 1s ease;
        box-shadow: 0 0 20px rgba(16, 185, 129, 0.5) !important;
    }
    
    .jm_animacion_perdedor {
        animation: jm_perdedorAnim 1s ease;
        box-shadow: 0 0 20px rgba(239, 68, 68, 0.5) !important;
    }
    
    .jm_animacion_empate {
        animation: jm_empateAnim 1s ease;
        box-shadow: 0 0 20px rgba(245, 158, 11, 0.5) !important;
    }
    
    @keyframes jm_ganadorAnim {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    @keyframes jm_perdedorAnim {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    
    @keyframes jm_empateAnim {
        0%, 100% { transform: rotate(0deg); }
        25% { transform: rotate(5deg); }
        75% { transform: rotate(-5deg); }
    }
`;
document.head.appendChild(estiloAnimaciones);