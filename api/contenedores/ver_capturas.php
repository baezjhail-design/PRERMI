<?php
// ver_capturas.php - Visor de capturas del ESP32-S3 CAM
$capturasDir = __DIR__ . '/../../uploads/capturas_cam/';
$baseUrl = '/PRERMI/uploads/capturas_cam/';

$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todas';

$archivos = [];
if (is_dir($capturasDir)) {
    $files = glob($capturasDir . '*.jpg');
    usort($files, function ($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    foreach ($files as $file) {
        $name = basename($file);
        $esReconocido = strpos($name, '_reconocido_') !== false && strpos($name, '_no_reconocido_') === false;

        if ($filtro === 'reconocido' && !$esReconocido) continue;
        if ($filtro === 'no_reconocido' && $esReconocido) continue;

        $archivos[] = [
            'nombre' => $name,
            'url' => $baseUrl . rawurlencode($name),
            'fecha' => date('Y-m-d H:i:s', filemtime($file)),
            'tamano' => round(filesize($file) / 1024, 1),
            'reconocido' => $esReconocido
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capturas ESP32-S3 CAM</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #1a1a2e; color: #eee; padding: 20px; }
        h1 { text-align: center; margin-bottom: 10px; color: #e94560; }
        .stats { text-align: center; margin-bottom: 20px; color: #aaa; }
        .filters { text-align: center; margin-bottom: 20px; }
        .filters a {
            display: inline-block; padding: 8px 20px; margin: 0 5px;
            border-radius: 20px; text-decoration: none; color: #fff;
            background: #16213e; border: 1px solid #0f3460;
            transition: background 0.2s;
        }
        .filters a:hover, .filters a.active { background: #e94560; border-color: #e94560; }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
        }
        .card {
            background: #16213e; border-radius: 10px; overflow: hidden;
            border: 2px solid transparent; transition: border-color 0.2s;
        }
        .card:hover { border-color: #e94560; }
        .card img {
            width: 100%; height: 200px; object-fit: cover;
            cursor: pointer; transition: opacity 0.2s;
        }
        .card img:hover { opacity: 0.85; }
        .card .info { padding: 10px; }
        .card .info .fecha { font-size: 0.85em; color: #aaa; }
        .card .info .estado {
            display: inline-block; padding: 3px 10px; border-radius: 12px;
            font-size: 0.8em; font-weight: bold; margin-top: 5px;
        }
        .estado.ok { background: #0a8754; color: #fff; }
        .estado.fail { background: #c0392b; color: #fff; }
        .card .info .size { font-size: 0.75em; color: #666; margin-top: 4px; }
        .empty { text-align: center; padding: 60px; color: #666; }
        .modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.9); z-index: 100; justify-content: center; align-items: center;
            cursor: pointer;
        }
        .modal.show { display: flex; }
        .modal img { max-width: 90%; max-height: 90%; border-radius: 8px; }
    </style>
</head>
<body>
    <h1>Capturas ESP32-S3 CAM</h1>
    <div class="stats">
        <?php
        $total = count($archivos);
        $reconocidos = count(array_filter($archivos, fn($a) => $a['reconocido']));
        $noReconocidos = $total - $reconocidos;
        echo "Total: {$total} | Reconocidos: {$reconocidos} | No reconocidos: {$noReconocidos}";
        ?>
    </div>

    <div class="filters">
        <a href="?filtro=todas" class="<?= $filtro === 'todas' ? 'active' : '' ?>">Todas</a>
        <a href="?filtro=reconocido" class="<?= $filtro === 'reconocido' ? 'active' : '' ?>">Reconocidos</a>
        <a href="?filtro=no_reconocido" class="<?= $filtro === 'no_reconocido' ? 'active' : '' ?>">No Reconocidos</a>
    </div>

    <?php if (empty($archivos)): ?>
        <div class="empty">No hay capturas <?= $filtro !== 'todas' ? "con filtro '$filtro'" : '' ?></div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($archivos as $a): ?>
                <div class="card">
                    <img src="<?= htmlspecialchars($a['url']) ?>" alt="<?= htmlspecialchars($a['nombre']) ?>" onclick="openModal(this.src)" loading="lazy">
                    <div class="info">
                        <div class="fecha"><?= htmlspecialchars($a['fecha']) ?></div>
                        <span class="estado <?= $a['reconocido'] ? 'ok' : 'fail' ?>">
                            <?= $a['reconocido'] ? 'Reconocido' : 'No Reconocido' ?>
                        </span>
                        <div class="size"><?= $a['tamano'] ?> KB</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="modal" id="modal" onclick="this.classList.remove('show')">
        <img id="modalImg" src="" alt="Preview">
    </div>

    <script>
        function openModal(src) {
            document.getElementById('modalImg').src = src;
            document.getElementById('modal').classList.add('show');
        }
    </script>
</body>
</html>
