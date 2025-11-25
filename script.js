// Versión para GitHub Pages - Solo JavaScript
console.log('✅ Juego cargado para GitHub Pages');

class JM_GameGitHub {
    constructor() {
        this.stats = this.loadStats();
        this.history = this.loadHistory();
        this.init();
    }

    loadStats() {
        const saved = localStorage.getItem('jm_stats');
        return saved ? JSON.parse(saved) : {
            victories: 0,
            defeats: 0,
            draws: 0,
            currentStreak: 0,
            bestStreak: 0,
            totalGames: 0
        };
    }

    loadHistory() {
        const saved = localStorage.getItem('jm_history');
        return saved ? JSON.parse(saved) : [];
    }

    saveStats() {
        localStorage.setItem('jm_stats', JSON.stringify(this.stats));
    }

    saveHistory() {
        // Mantener solo últimas 20 partidas
        if (this.history.length > 20) {
            this.history = this.history.slice(0, 20);
        }
        localStorage.setItem('jm_history', JSON.stringify(this.history));
    }

    init() {
        this.initTheme();
        this.initEvents();
        this.updateUI();
        console.log('✅ Juego inicializado');
    }

    initTheme() {
        this.currentTheme = localStorage.getItem('jm_theme') || 'light';
        document.documentElement.setAttribute('data-theme', this.currentTheme);
        this.updateThemeToggle();
    }

    initEvents() {
        // Botones de juego
        document.querySelectorAll('.jm_opcion').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.play(e.target.closest('.jm_opcion').dataset.opcion);
            });
        });

        // Toggle tema
        document.querySelector('.jm_theme_toggle').addEventListener('click', () => {
            this.toggleTheme();
        });

        // Reiniciar estadísticas
        document.getElementById('jm_reiniciar').addEventListener('click', () => {
            this.resetStats();
        });

        // Exportar datos
        document.getElementById('jm_exportar').addEventListener('click', () => {
            this.exportData();
        });
    }

    play(userChoice) {
        console.log('🎮 Jugando:', userChoice);
        
        const choices = ['piedra', 'papel', 'tijera'];
        const computerChoice = choices[Math.floor(Math.random() * 3)];
        
        let result = '';
        if (userChoice === computerChoice) {
            result = 'draw';
        } else if (
            (userChoice === 'piedra' && computerChoice === 'tijera') ||
            (userChoice === 'papel' && computerChoice === 'piedra') ||
            (userChoice === 'tijera' && computerChoice === 'papel')
        ) {
            result = 'win';
        } else {
            result = 'lose';
        }

        this.updateStats(result);
        this.addToHistory(userChoice, computerChoice, result);
        this.showResult(userChoice, computerChoice, result);
        this.updateUI();
    }

    updateStats(result) {
        this.stats.totalGames++;
        
        switch(result) {
            case 'win':
                this.stats.victories++;
                this.stats.currentStreak++;
                if (this.stats.currentStreak > this.stats.bestStreak) {
                    this.stats.bestStreak = this.stats.currentStreak;
                }
                break;
            case 'lose':
                this.stats.defeats++;
                this.stats.currentStreak = 0;
                break;
            case 'draw':
                this.stats.draws++;
                break;
        }
        
        this.saveStats();
    }

    addToHistory(user, computer, result) {
        const game = {
            timestamp: new Date().toLocaleTimeString(),
            user: user,
            computer: computer,
            result: result,
            userIcon: this.getIcon(user),
            computerIcon: this.getIcon(computer)
        };
        
        this.history.unshift(game);
        this.saveHistory();
    }

    getIcon(choice) {
        const icons = {
            'piedra': '✊',
            'papel': '✋',
            'tijera': '✌️'
        };
        return icons[choice];
    }

    showResult(user, computer, result) {
        const messageEl = document.getElementById('jm_mensaje_resultado');
        const userIcon = this.getIcon(user);
        const computerIcon = this.getIcon(computer);
        
        let message = '';
        let className = '';
        
        switch(result) {
            case 'win':
                message = `¡GANASTE! ${userIcon} vence a ${computerIcon}`;
                className = 'jm_ganador';
                break;
            case 'lose':
                message = `Perdiste... ${computerIcon} vence a ${userIcon}`;
                className = 'jm_perdedor';
                break;
            case 'draw':
                message = `¡EMPATE! ${userIcon} vs ${computerIcon}`;
                className = 'jm_empate_resultado';
                break;
        }
        
        messageEl.textContent = message;
        messageEl.className = `jm_mensaje_resultado jm_animacion_resultado ${className}`;
    }

    updateUI() {
        // Actualizar contadores
        document.getElementById('jm_contador_victorias').textContent = this.stats.victories;
        document.getElementById('jm_contador_derrotas').textContent = this.stats.defeats;
        document.getElementById('jm_contador_empates').textContent = this.stats.draws;
        document.getElementById('jm_total_partidas').textContent = this.stats.totalGames;
        document.getElementById('jm_racha_actual').textContent = this.stats.currentStreak;
        document.getElementById('jm_mejor_racha').textContent = this.stats.bestStreak;
        
        // Actualizar porcentajes
        const total = this.stats.totalGames || 1;
        const victoryPercent = ((this.stats.victories / total) * 100).toFixed(1);
        const defeatPercent = ((this.stats.defeats / total) * 100).toFixed(1);
        const drawPercent = ((this.stats.draws / total) * 100).toFixed(1);
        
        document.getElementById('jm_porcentaje_victorias').textContent = victoryPercent + '%';
        document.getElementById('jm_porcentaje_derrotas').textContent = defeatPercent + '%';
        document.getElementById('jm_porcentaje_empates').textContent = drawPercent + '%';
        
        // Actualizar barras
        document.getElementById('jm_barra_victorias').style.width = victoryPercent + '%';
        document.getElementById('jm_barra_derrotas').style.width = defeatPercent + '%';
        document.getElementById('jm_barra_empates').style.width = drawPercent + '%';
        
        // Actualizar historial
        this.updateHistory();
    }

    updateHistory() {
        const container = document.getElementById('jm_historial_partidas');
        
        if (this.history.length === 0) {
            container.innerHTML = '<div class="jm_partida_item jm_empate"><span>No hay partidas jugadas</span></div>';
            return;
        }
        
        container.innerHTML = this.history.map(game => `
            <div class="jm_partida_item jm_${game.result === 'win' ? 'ganaste' : game.result === 'lose' ? 'perdiste' : 'empate'}">
                <span class="jm_partida_hora">${game.timestamp}</span>
                <span class="jm_partida_icono">${game.userIcon}</span>
                <span class="jm_partida_vs">VS</span>
                <span class="jm_partida_icono">${game.computerIcon}</span>
                <span class="jm_partida_resultado">${game.result === 'win' ? 'GANASTE' : game.result === 'lose' ? 'PERDISTE' : 'EMPATE'}</span>
            </div>
        `).join('');
    }

    toggleTheme() {
        this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', this.currentTheme);
        localStorage.setItem('jm_theme', this.currentTheme);
        this.updateThemeToggle();
    }

    updateThemeToggle() {
        const toggle = document.querySelector('.jm_theme_toggle');
        const icon = toggle.querySelector('.jm_theme_icon');
        
        if (this.currentTheme === 'dark') {
            icon.textContent = '☀️';
            icon.style.transform = 'rotate(180deg)';
        } else {
            icon.textContent = '🌙';
            icon.style.transform = 'rotate(0deg)';
        }
    }

    resetStats() {
        if (confirm('¿Estás seguro de que quieres reiniciar todas las estadísticas?')) {
            this.stats = {
                victories: 0,
                defeats: 0,
                draws: 0,
                currentStreak: 0,
                bestStreak: 0,
                totalGames: 0
            };
            this.history = [];
            this.saveStats();
            this.saveHistory();
            this.updateUI();
            document.getElementById('jm_mensaje_resultado').textContent = 'Estadísticas reiniciadas';
        }
    }

    exportData() {
        const data = {
            stats: this.stats,
            history: this.history,
            exportDate: new Date().toLocaleString()
        };
        
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `estadisticas_piedra_papel_tijera_${Date.now()}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
}

// Inicializar el juego cuando se carga la página
document.addEventListener('DOMContentLoaded', () => {
    window.game = new JM_GameGitHub();
});
