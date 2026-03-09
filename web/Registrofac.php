<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registro Facial - PRERMI</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.navbar-user {
    background: rgba(0,0,0,0.1);
    backdrop-filter: blur(10px);
    padding: 1rem 2rem;
}

.navbar-user .navbar-brand {
    color: white;
    font-weight: 700;
}

.container-main {
    padding: 3rem 2rem;
}

.card-custom {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    padding: 2rem;
}

.video-container {
    position: relative;
    display: flex;
    justify-content: center;
    margin-bottom: 1.5rem;
}

video, canvas {
    border-radius: 15px;
    width: 100%;
    max-width: 500px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
}

#faceOverlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 280px;
    height: 350px;
    border: 3px dashed rgba(102, 126, 234, 0.7);
    border-radius: 50%;
    pointer-events: none;
    z-index: 10;
}

.btn-custom {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 10px;
    transition: 0.3s;
}

.btn-custom:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(102,126,234,0.4);
    color: white;
}

.instructions {
    background: #f0f4ff;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
    text-align: left;
}

.instructions h6 {
    color: #667eea;
    margin-bottom: 0.5rem;
}

.instructions ul {
    margin: 0;
    padding-left: 1.5rem;
    font-size: 0.9rem;
}

#preview {
    border: 3px solid #667eea;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.loading-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}

.loading-overlay.active {
    display: flex;
}

.loading-content {
    text-align: center;
    color: white;
}

.spinner {
    border: 4px solid rgba(255,255,255,0.3);
    border-top: 4px solid white;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
</head>

<body>

<nav class="navbar navbar-user">
    <span class="navbar-brand">
        <i class="fas fa-camera"></i> PRERMI - Registro Facial
    </span>
</nav>

<div class="container-main">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card-custom text-center">
                <h3 class="mb-3"><i class="fas fa-user-check"></i> Registro Facial</h3>

                <div class="instructions">
                    <h6><i class="fas fa-info-circle"></i> Instrucciones para mejor captura:</h6>
                    <ul>
                        <li>Ubique su rostro dentro del óvalo guía</li>
                        <li>Asegure buena iluminación frontal (evite contraluz)</li>
                        <li>Retire lentes, gorra o accesorios que cubran el rostro</li>
                        <li>Mantenga expresión neutral y mire a la cámara</li>
                        <li>Evite sombras fuertes en el rostro</li>
                    </ul>
                </div>

                <div class="video-container">
                    <video id="video" autoplay playsinline></video>
                    <div id="faceOverlay"></div>
                </div>

                <canvas id="canvas" style="display:none;"></canvas>

                <button class="btn btn-custom mb-3" onclick="capturePhoto()">
                    <i class="fas fa-camera"></i> Capturar Foto
                </button>

                <div class="mt-3" id="previewContainer" style="display:none;">
                    <img id="preview" class="img-fluid" style="border-radius:15px; max-height:400px;">
                </div>

                <form method="POST" action="guardar_foto.php" id="formGuardar" style="display:none;">
                    <input type="hidden" name="image_data" id="image_data">
                    <button type="submit" class="btn btn-success mt-3">
                        <i class="fas fa-save"></i> Guardar y Continuar
                    </button>
                    <button type="button" class="btn btn-warning mt-3" onclick="resetCapture()">
                        <i class="fas fa-redo"></i> Tomar Otra Foto
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="spinner"></div>
        <p>Procesando imagen...</p>
    </div>
</div>

<script>
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const preview = document.getElementById('preview');
const previewContainer = document.getElementById('previewContainer');
const imageDataInput = document.getElementById('image_data');
const formGuardar = document.getElementById('formGuardar');
const loadingOverlay = document.getElementById('loadingOverlay');

// Configuración de cámara con resolución óptima para reconocimiento facial
const constraints = {
    video: {
        width: { ideal: 1280 },
        height: { ideal: 720 },
        facingMode: 'user',
        frameRate: { ideal: 30 }
    }
};

// Activar cámara con configuración optimizada
navigator.mediaDevices.getUserMedia(constraints)
.then(stream => {
    video.srcObject = stream;
    video.play();
})
.catch(error => {
    console.error('Error al acceder a la cámara:', error);
    alert("No se pudo acceder a la cámara. Verifique los permisos.");
});

// Capturar imagen con procesamiento mejorado
function capturePhoto() {
    // Usar dimensiones del video real
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    const ctx = canvas.getContext('2d');
    
    // Aplicar mejoras de imagen para mejor detección facial
    ctx.filter = 'contrast(1.1) brightness(1.05)';
    ctx.drawImage(video, 0, 0);
    
    // Resetear filtro
    ctx.filter = 'none';

    // Convertir a JPEG con alta calidad (92% para balance entre calidad y tamaño)
    const dataURL = canvas.toDataURL("image/jpeg", 0.92);
    
    // Mostrar preview
    preview.src = dataURL;
    previewContainer.style.display = "block";
    imageDataInput.value = dataURL;
    
    // Mostrar formulario de guardado
    formGuardar.style.display = "block";
    
    // Scroll suave al preview
    previewContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Resetear captura
function resetCapture() {
    previewContainer.style.display = "none";
    formGuardar.style.display = "none";
    imageDataInput.value = "";
}

// Interceptar envío del formulario para mostrar loading
formGuardar.addEventListener('submit', function(e) {
    if (!imageDataInput.value) {
        e.preventDefault();
        alert('Por favor capture una foto primero');
        return;
    }
    
    // Mostrar overlay de carga
    loadingOverlay.classList.add('active');
    
    // El formulario se enviará normalmente
    // La redirección la manejará guardar_foto.php
});

// Detener cámara cuando se abandona la página
window.addEventListener('beforeunload', function() {
    if (video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
    }
});
</script>

</body>
</html>