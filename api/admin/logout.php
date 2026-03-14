<?php
// api/admin/logout.php - Logout elegante con animaciÃ³n

session_start();

// Destruir sesiÃ³n
session_destroy();

// Destruir cookies si existen
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerrando SesiÃ³n - PRERMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            overflow: hidden;
        }

        .logout-container {
            max-width: 500px;
            width: 100%;
        }

        .logout-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 60px 40px;
            text-align: center;
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logout-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ff5252 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: popIn 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: 0 10px 30px rgba(255, 107, 107, 0.3);
        }

        @keyframes popIn {
            0% {
                transform: scale(0) rotate(-45deg);
                opacity: 0;
            }
            50% {
                transform: scale(1.1) rotate(10deg);
            }
            100% {
                transform: scale(1) rotate(0deg);
                opacity: 1;
            }
        }

        .logout-icon i {
            font-size: 60px;
            color: white;
        }

        .logout-card h1 {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            animation: fadeInUp 0.8s ease-out 0.3s both;
        }

        .logout-card p {
            color: #666;
            font-size: 16px;
            margin-bottom: 10px;
            animation: fadeInUp 0.8s ease-out 0.5s both;
            line-height: 1.6;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .countdown {
            margin-top: 30px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 12px;
            animation: fadeInUp 0.8s ease-out 0.7s both;
        }

        .countdown-text {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .countdown-timer {
            font-size: 24px;
            font-weight: 700;
            color: #ff6b6b;
        }

        .progress-bar-container {
            width: 100%;
            height: 4px;
            background: #ddd;
            border-radius: 2px;
            margin-top: 15px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff6b6b 0%, #ff5252 100%);
            animation: progress 3s linear forwards;
        }

        @keyframes progress {
            from {
                width: 100%;
            }
            to {
                width: 0%;
            }
        }

        .logout-footer {
            margin-top: 30px;
            font-size: 13px;
            color: #999;
            animation: fadeInUp 0.8s ease-out 0.9s both;
        }

        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            animation: float 20s infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(0px) translateX(0px);
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(100px);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <!-- PartÃ­culas animadas de fondo -->
    <div class="particles" id="particles"></div>

    <div class="logout-container">
        <div class="logout-card">
            <!-- Ãcono X animado -->
            <div class="logout-icon">
                <i class="fas fa-times"></i>
            </div>

            <!-- Texto principal -->
            <h1>Â¡Hasta Luego!</h1>
            <p>Tu sesiÃ³n ha sido cerrada correctamente.</p>
            <p style="font-size: 14px; margin-top: 15px;">
                Gracias por usar PRERMI
            </p>

            <!-- Contador de redirecciÃ³n -->
            <div class="countdown">
                <div class="countdown-text">Redirigiendo en:</div>
                <div class="countdown-timer" id="timer">3</div>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill"></div>
                </div>
            </div>

            <div class="logout-footer">
                Si no se redirige automáticamente, <a href="../../web/admin/loginA.php" style="color: #ff6b6b; text-decoration: none; font-weight: 600;">haz clic aquí</a>
            </div>
        </div>
    </div>

    <script>
        // Generar partÃ­culas flotantes
        function generateParticles() {
            const container = document.getElementById('particles');
            const particleCount = 20;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';

                const randomLeft = Math.random() * window.innerWidth;
                const randomDelay = Math.random() * 5;
                const randomDuration = 15 + Math.random() * 10;

                particle.style.left = randomLeft + 'px';
                particle.style.top = window.innerHeight + 'px';
                particle.style.animationDelay = randomDelay + 's';
                particle.style.animationDuration = randomDuration + 's';

                container.appendChild(particle);
            }
        }

        // Inicializar partÃ­culas
        generateParticles();

        // Contador regresivo
        let timeLeft = 3;
        const timerElement = document.getElementById('timer');

        const interval = setInterval(() => {
            timeLeft--;
            timerElement.textContent = timeLeft;

            if (timeLeft <= 0) {
                clearInterval(interval);
                // Redirigir con animaciÃ³n de salida
                document.querySelector('.logout-card').style.animation = 'slideIn 0.6s ease-out reverse forwards';
                setTimeout(() => {
                    window.location.href = '../../web/admin/loginA.php';
                }, 600);
            }
        }, 1000);

        // Permitir redirecciÃ³n manual al hacer clic en el enlace
        document.addEventListener('click', (e) => {
            if (e.target.tagName === 'A') {
                clearInterval(interval);
            }
        });
    </script>
</body>
</html>

