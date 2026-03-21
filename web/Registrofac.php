<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];
$minPhotosRequired = 15;
$shotsPerBatch = 3;

$userFaceDir = __DIR__ . '/../uploads/rostros/' . $userId;
$existingPhotoCount = 0;
if (is_dir($userFaceDir)) {
    $files = glob($userFaceDir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
    $existingPhotoCount = is_array($files) ? count($files) : 0;
}

$photosRemaining = max(0, $minPhotosRequired - $existingPhotoCount);
$goalReached = $existingPhotoCount >= $minPhotosRequired;
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registro Facial Inteligente - PRERMI</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body {
    background: linear-gradient(145deg, #0f172a 0%, #1e3a8a 55%, #0ea5e9 100%);
    min-height: 100vh;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.navbar-user {
    background: rgba(2, 6, 23, 0.45);
    backdrop-filter: blur(10px);
    padding: 1rem 2rem;
}

.navbar-user .navbar-brand {
    color: #e2e8f0;
    font-weight: 700;
}

.container-main {
    padding: 3rem 2rem;
}

.card-custom {
    background: #f8fafc;
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
    width: min(65%, 330px);
    aspect-ratio: 3 / 4;
    border: 3px dashed rgba(14, 165, 233, 0.85);
    border-radius: 50%;
    pointer-events: none;
    z-index: 10;
}

.btn-custom {
    background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%);
    color: white;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 10px;
    transition: 0.3s;
}

.btn-custom:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(14,165,233,0.35);
    color: white;
}

.instructions {
    background: #e0f2fe;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
    text-align: left;
}

.instructions h6 {
    color: #0369a1;
    margin-bottom: 0.5rem;
}

.instructions ul {
    margin: 0;
    padding-left: 1.5rem;
    font-size: 0.9rem;
}

#preview {
    border: 3px solid #0ea5e9;
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

.status-panel {
    background: #ecfeff;
    border: 1px solid #a5f3fc;
    border-radius: 10px;
    padding: 12px;
    margin-bottom: 12px;
    text-align: left;
}

.status-value {
    font-weight: 700;
    color: #0f172a;
}

.capture-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(110px, 1fr));
    gap: 10px;
    margin-top: 12px;
}

.capture-item {
    border: 2px dashed #7dd3fc;
    border-radius: 10px;
    background: #f0f9ff;
    min-height: 90px;
    padding: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0369a1;
    font-size: 0.85rem;
    text-align: center;
}

.capture-item img {
    width: 100%;
    height: 84px;
    object-fit: cover;
    border-radius: 8px;
}

.hint-box {
    font-size: 0.88rem;
    background: #fff;
    border: 1px solid #dbeafe;
    border-radius: 10px;
    padding: 10px;
    margin-top: 12px;
    text-align: left;
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
        <div class="col-lg-8">
            <div class="card-custom text-center">
                <h3 class="mb-3"><i class="fas fa-user-check"></i> Registro Facial Inteligente</h3>

                <div class="status-panel">
                    <div>ID de usuario: <span class="status-value"><?php echo $userId; ?></span></div>
                    <div>Fotos registradas: <span class="status-value"><?php echo $existingPhotoCount; ?></span> / <?php echo $minPhotosRequired; ?></div>
                    <div>Faltantes para nivel recomendado: <span class="status-value"><?php echo $photosRemaining; ?></span></div>
                    <?php if ($goalReached): ?>
                        <div class="text-success mt-1"><i class="fas fa-check-circle"></i> Objetivo de calidad alcanzado.</div>
                    <?php else: ?>
                        <div class="text-primary mt-1"><i class="fas fa-camera"></i> Captura lotes de <?php echo $shotsPerBatch; ?> fotos hasta llegar a <?php echo $minPhotosRequired; ?>.</div>
                    <?php endif; ?>
                </div>

                <div class="instructions">
                    <h6><i class="fas fa-info-circle"></i> Guía avanzada para diferentes entornos:</h6>
                    <ul>
                        <li>Centre su rostro dentro del óvalo (rostro ocupando 45%-65% del marco).</li>
                        <li>Capture variaciones: cerca, media distancia, lejos moderado, sonrisa y expresión neutral.</li>
                        <li>Haga lotes con iluminación distinta: día interior, noche con luz frontal y fondo oscuro.</li>
                        <li>Evite contraluz; la luz debe venir de frente para resaltar ojos, nariz y boca.</li>
                        <li>No use filtros; mantenga la cámara estable y el rostro nítido.</li>
                    </ul>
                </div>

                <div class="video-container">
                    <video id="video" autoplay playsinline></video>
                    <div id="faceOverlay"></div>
                </div>

                <canvas id="canvas" style="display:none;"></canvas>

                <button class="btn btn-custom mb-3" id="btnCapture" onclick="captureBurst()">
                    <i class="fas fa-camera"></i> Capturar 3 Fotos Seguidas
                </button>

                <div class="hint-box" id="captureStatus">
                    Listo para capturar lote de 3 fotos.
                </div>

                <div class="capture-grid" id="captureGrid">
                    <div class="capture-item" id="slot0">Foto 1 pendiente</div>
                    <div class="capture-item" id="slot1">Foto 2 pendiente</div>
                    <div class="capture-item" id="slot2">Foto 3 pendiente</div>
                </div>

                <form method="POST" action="guardar_foto.php" id="formGuardar" style="display:none;">
                    <input type="hidden" name="image_data_list" id="image_data_list">
                    <button type="submit" class="btn btn-success mt-3">
                        <i class="fas fa-save"></i> Guardar Lote de 3 Fotos
                    </button>
                    <button type="button" class="btn btn-warning mt-3" onclick="resetCapture()">
                        <i class="fas fa-redo"></i> Reiniciar Capturas
                    </button>
                </form>

                <?php if ($goalReached): ?>
                    <div class="mt-3">
                        <a href="user-dashboard.php" class="btn btn-outline-success">
                            <i class="fas fa-check"></i> Continuar al panel de usuario
                        </a>
                    </div>
                <?php endif; ?>

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
const imageDataInput = document.getElementById('image_data_list');
const formGuardar = document.getElementById('formGuardar');
const loadingOverlay = document.getElementById('loadingOverlay');
const captureStatus = document.getElementById('captureStatus');
const btnCapture = document.getElementById('btnCapture');

const SHOTS_PER_BATCH = 3;
let capturedImages = [];
let isCapturing = false;

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

function wait(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

function estimateQuality(ctx, w, h) {
    const data = ctx.getImageData(0, 0, w, h).data;
    let lumSum = 0;
    let lumSqSum = 0;
    const step = 16;

    for (let i = 0; i < data.length; i += 4 * step) {
        const r = data[i], g = data[i + 1], b = data[i + 2];
        const lum = 0.299 * r + 0.587 * g + 0.114 * b;
        lumSum += lum;
        lumSqSum += lum * lum;
    }

    const n = data.length / (4 * step);
    const mean = lumSum / Math.max(1, n);
    const variance = (lumSqSum / Math.max(1, n)) - (mean * mean);

    return {
        mean,
        contrast: Math.max(0, variance)
    };
}

function renderCapturedSlots() {
    for (let i = 0; i < SHOTS_PER_BATCH; i++) {
        const slot = document.getElementById(`slot${i}`);
        if (!slot) continue;
        if (capturedImages[i]) {
            slot.innerHTML = `<img src="${capturedImages[i]}" alt="Captura ${i + 1}">`;
        } else {
            slot.textContent = `Foto ${i + 1} pendiente`;
        }
    }
}

async function captureBurst() {
    if (isCapturing) return;
    if (!video.videoWidth || !video.videoHeight) {
        alert('La cámara todavía no está lista. Intente de nuevo en 2 segundos.');
        return;
    }

    isCapturing = true;
    btnCapture.disabled = true;
    capturedImages = [];
    renderCapturedSlots();
    formGuardar.style.display = 'none';

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');

    for (let i = 0; i < SHOTS_PER_BATCH; i++) {
        captureStatus.textContent = `Preparando captura ${i + 1}/${SHOTS_PER_BATCH}...`;
        await wait(650);

        ctx.filter = 'contrast(1.08) brightness(1.06)';
        ctx.drawImage(video, 0, 0);
        ctx.filter = 'none';

        const q = estimateQuality(ctx, canvas.width, canvas.height);
        if (q.mean < 45 || q.mean > 215 || q.contrast < 380) {
            captureStatus.textContent = `Captura ${i + 1}: calidad baja (luz/contraste). Reintentando...`;
            i--;
            await wait(450);
            continue;
        }

        const dataURL = canvas.toDataURL('image/jpeg', 0.94);
        capturedImages.push(dataURL);
        renderCapturedSlots();
        captureStatus.textContent = `Captura ${capturedImages.length}/${SHOTS_PER_BATCH} lista.`;
        await wait(450);
    }

    imageDataInput.value = JSON.stringify(capturedImages);
    formGuardar.style.display = 'block';
    captureStatus.textContent = 'Lote completo. Revise y guarde las 3 capturas.';
    isCapturing = false;
    btnCapture.disabled = false;
}

// Resetear captura
function resetCapture() {
    capturedImages = [];
    imageDataInput.value = '';
    formGuardar.style.display = 'none';
    captureStatus.textContent = 'Capturas reiniciadas. Listo para nuevo lote.';
    renderCapturedSlots();
}

// Interceptar envío del formulario para mostrar loading
formGuardar.addEventListener('submit', function(e) {
    if (!imageDataInput.value || capturedImages.length !== SHOTS_PER_BATCH) {
        e.preventDefault();
        alert('Debe capturar exactamente 3 fotos antes de guardar.');
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