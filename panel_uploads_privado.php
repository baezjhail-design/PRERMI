<?php
// Panel privado independiente para visualizar imagenes en /uploads.

declare(strict_types=1);

$sessionSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $sessionSecure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
} else {
    // Compatibilidad con PHP < 7.3 (sin soporte de arreglo de opciones).
    session_set_cookie_params(0, '/; samesite=Strict', '', $sessionSecure, true);
}
session_start();

const PANEL_USER = 'root';
const PANEL_PASS = 'PRERMI1234.jhailbaez';
const AUTH_SESSION_KEY = 'private_upload_panel_auth';
const PER_PAGE = 60;

$baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    $username = trim((string) $_POST['username']);
    $password = (string) $_POST['password'];

    $isValidUser = hash_equals(PANEL_USER, $username);
    $isValidPass = hash_equals(PANEL_PASS, $password);

    if ($isValidUser && $isValidPass) {
        session_regenerate_id(true);
        $_SESSION[AUTH_SESSION_KEY] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $error = 'Credenciales invalidas.';
}

$isAuthenticated = !empty($_SESSION[AUTH_SESSION_KEY]);

if (!$isAuthenticated):
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel Privado Uploads</title>
    <style>
        :root {
            --bg: #0f172a;
            --card: #111827;
            --line: #1f2937;
            --text: #e5e7eb;
            --muted: #9ca3af;
            --accent: #22c55e;
            --danger: #ef4444;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: radial-gradient(circle at top right, #1e293b, var(--bg));
            font-family: "Segoe UI", Tahoma, sans-serif;
            color: var(--text);
        }
        .card {
            width: min(420px, 92vw);
            background: color-mix(in srgb, var(--card) 92%, black 8%);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 14px 40px rgba(0, 0, 0, 0.4);
        }
        h1 {
            margin: 0 0 8px;
            font-size: 1.3rem;
        }
        p {
            margin: 0 0 18px;
            color: var(--muted);
        }
        label {
            font-size: .9rem;
            color: var(--muted);
            display: block;
            margin-bottom: 6px;
        }
        input {
            width: 100%;
            background: #0b1220;
            border: 1px solid var(--line);
            color: var(--text);
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 14px;
        }
        button {
            width: 100%;
            border: 0;
            border-radius: 10px;
            background: var(--accent);
            color: #052e16;
            font-weight: 700;
            padding: 11px;
            cursor: pointer;
        }
        .error {
            margin-bottom: 12px;
            color: #fee2e2;
            background: color-mix(in srgb, var(--danger) 24%, transparent 76%);
            border: 1px solid color-mix(in srgb, var(--danger) 40%, transparent 60%);
            padding: 9px 10px;
            border-radius: 8px;
            font-size: .92rem;
        }
    </style>
</head>
<body>
    <main class="card">
        <h1>Panel Privado de Uploads</h1>
        <p>Acceso interno separado del sistema principal.</p>

        <?php if ($error !== ''): ?>
            <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <label for="username">Usuario</label>
            <input id="username" name="username" type="text" required>

            <label for="password">Contrasena</label>
            <input id="password" name="password" type="password" required>

            <button type="submit">Entrar</button>
        </form>
    </main>
</body>
</html>
<?php
exit;
endif;

$imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
$images = [];

if (is_dir($baseDir)) {
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            $ext = strtolower(pathinfo($fileInfo->getFilename(), PATHINFO_EXTENSION));
            if (!in_array($ext, $imageExtensions, true)) {
                continue;
            }

            $absolutePath = $fileInfo->getPathname();
            $relativePath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $absolutePath);
            $publicPath = 'uploads/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

            $images[] = [
                'path' => $publicPath,
                'name' => $fileInfo->getFilename(),
                'size' => $fileInfo->getSize(),
                'mtime' => $fileInfo->getMTime(),
            ];
        }
    } catch (UnexpectedValueException $e) {
        if ($error === '') {
            $error = 'Se omitieron carpetas sin permisos en uploads.';
        }
    }
}

usort(
    $images,
    static function (array $a, array $b): int {
        if ($a['mtime'] === $b['mtime']) {
            return 0;
        }

        return ($a['mtime'] < $b['mtime']) ? 1 : -1;
    }
);

$totalImages = count($images);
$totalPages = max(1, (int) ceil($totalImages / PER_PAGE));
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$page = min($page, $totalPages);
$offset = ($page - 1) * PER_PAGE;
$currentImages = array_slice($images, $offset, PER_PAGE);

$facialRecords = [];
$facialError = '';

try {
    require_once __DIR__ . '/api/utils.php';

    if (function_exists('getPDO')) {
        $pdo = getPDO();
        $stmt = $pdo->query(
            "SELECT r.user_id, r.filename, r.creado_en, u.nombre, u.apellido, u.usuario
             FROM rostros r
             LEFT JOIN usuarios u ON u.id = r.user_id
             ORDER BY r.creado_en DESC
             LIMIT 300"
        );

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as $row) {
            $userId = (int) ($row['user_id'] ?? 0);
            $nombre = trim((string) ($row['nombre'] ?? ''));
            $apellido = trim((string) ($row['apellido'] ?? ''));
            $usuario = trim((string) ($row['usuario'] ?? ''));
            $fullName = trim($nombre . ' ' . $apellido);
            $displayName = $fullName !== '' ? $fullName : ($usuario !== '' ? $usuario : ('Usuario #' . $userId));

            $filename = basename((string) ($row['filename'] ?? ''));
            $candidatePaths = [];

            if ($filename !== '') {
                $candidatePaths[] = $baseDir . DIRECTORY_SEPARATOR . 'rostros' . DIRECTORY_SEPARATOR . $filename;
            }

            if ($userId > 0) {
                $candidatePaths[] = $baseDir . DIRECTORY_SEPARATOR . 'rostros' . DIRECTORY_SEPARATOR . 'face_' . $userId . '.jpg';
                $candidatePaths[] = $baseDir . DIRECTORY_SEPARATOR . 'rostros' . DIRECTORY_SEPARATOR . 'face_' . $userId . '.jpeg';
                $candidatePaths[] = $baseDir . DIRECTORY_SEPARATOR . 'rostros' . DIRECTORY_SEPARATOR . 'face_' . $userId . '.png';
                $candidatePaths[] = $baseDir . DIRECTORY_SEPARATOR . 'rostros' . DIRECTORY_SEPARATOR . 'face_' . $userId . '.webp';
            }

            $publicFacePath = '';
            foreach ($candidatePaths as $candidate) {
                if (is_file($candidate)) {
                    $publicFacePath = 'uploads/rostros/' . basename($candidate);
                    break;
                }
            }

            $facialRecords[] = [
                'user_id' => $userId,
                'display_name' => $displayName,
                'username' => $usuario,
                'filename' => $filename,
                'created_at' => (string) ($row['creado_en'] ?? ''),
                'face_path' => $publicFacePath,
            ];
        }
    } else {
        $facialError = 'No se pudo cargar la conexion de base de datos.';
    }
} catch (Throwable $e) {
    $facialError = 'No se pudieron cargar los registros faciales.';
}

function bytesToHuman(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $value = (float) $bytes;
    $unit = 0;

    while ($value >= 1024 && $unit < count($units) - 1) {
        $value /= 1024;
        $unit++;
    }

    return number_format($value, $unit === 0 ? 0 : 2) . ' ' . $units[$unit];
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel Privado Uploads</title>
    <style>
        :root {
            --bg: #f3f4f6;
            --card: #ffffff;
            --line: #d1d5db;
            --title: #0f172a;
            --text: #334155;
            --muted: #6b7280;
            --accent: #0369a1;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: linear-gradient(180deg, #e2e8f0 0%, var(--bg) 280px);
            font-family: "Segoe UI", Tahoma, sans-serif;
            color: var(--text);
        }
        .container {
            width: min(1280px, 94vw);
            margin: 28px auto 40px;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }
        h1 {
            margin: 0;
            color: var(--title);
            font-size: 1.6rem;
        }
        .hint {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: .9rem;
        }
        .logout-form button {
            border: 1px solid var(--line);
            background: #fff;
            border-radius: 10px;
            padding: 8px 12px;
            cursor: pointer;
            font-weight: 600;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 10px;
            margin-bottom: 16px;
        }
        .stat {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 10px 12px;
        }
        .stat .label {
            font-size: .82rem;
            color: var(--muted);
            margin-bottom: 4px;
        }
        .stat .value {
            font-size: 1.06rem;
            color: var(--title);
            font-weight: 700;
        }
        .section-title {
            margin: 18px 0 10px;
            color: var(--title);
            font-size: 1.2rem;
        }
        .faces-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }
        .face-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }
        .face-photo {
            aspect-ratio: 4 / 3;
            background: #0f172a;
            display: grid;
            place-items: center;
        }
        .face-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .face-missing {
            color: #cbd5e1;
            font-size: .86rem;
            text-align: center;
            padding: 0 10px;
        }
        .face-meta {
            padding: 10px;
        }
        .face-name {
            font-size: .94rem;
            color: var(--title);
            font-weight: 700;
            margin-bottom: 4px;
        }
        .face-sub {
            color: var(--muted);
            font-size: .82rem;
            margin-bottom: 3px;
            word-break: break-word;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 12px;
        }
        .item {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }
        .item a {
            display: block;
            aspect-ratio: 4 / 3;
            background: #111827;
        }
        .item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .meta {
            padding: 10px;
        }
        .name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--title);
            margin-bottom: 5px;
            font-size: .92rem;
            font-weight: 600;
        }
        .sub {
            color: var(--muted);
            font-size: .82rem;
        }
        .pagination {
            margin-top: 18px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .pagination a,
        .pagination span {
            text-decoration: none;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--text);
            padding: 8px 10px;
            border-radius: 9px;
            font-size: .9rem;
        }
        .pagination .current {
            color: #fff;
            background: var(--accent);
            border-color: var(--accent);
        }
        .empty {
            background: #fff;
            border: 1px dashed var(--line);
            border-radius: 12px;
            padding: 26px;
            text-align: center;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="topbar">
            <div>
                <h1>Panel Privado de Uploads</h1>
                <p class="hint">Seccion independiente para revisar imagenes en /uploads</p>
            </div>
            <form class="logout-form" method="post">
                <button type="submit" name="logout" value="1">Cerrar sesion</button>
            </form>
        </div>

        <section class="stats">
            <article class="stat">
                <div class="label">Imagenes encontradas</div>
                <div class="value"><?php echo number_format($totalImages); ?></div>
            </article>
            <article class="stat">
                <div class="label">Pagina actual</div>
                <div class="value"><?php echo $page . ' / ' . $totalPages; ?></div>
            </article>
            <article class="stat">
                <div class="label">Carpeta escaneada</div>
                <div class="value">uploads</div>
            </article>
            <article class="stat">
                <div class="label">Registros faciales</div>
                <div class="value"><?php echo number_format(count($facialRecords)); ?></div>
            </article>
        </section>

        <h2 class="section-title">Registros faciales por usuario</h2>
        <?php if ($facialError !== ''): ?>
            <div class="empty"><?php echo htmlspecialchars($facialError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php elseif (count($facialRecords) === 0): ?>
            <div class="empty">No hay registros faciales en la base de datos.</div>
        <?php else: ?>
            <section class="faces-grid">
                <?php foreach ($facialRecords as $face): ?>
                    <article class="face-card">
                        <div class="face-photo">
                            <?php if ($face['face_path'] !== ''): ?>
                                <a href="<?php echo htmlspecialchars($face['face_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                    <img src="<?php echo htmlspecialchars($face['face_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Rostro de <?php echo htmlspecialchars($face['display_name'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                                </a>
                            <?php else: ?>
                                <div class="face-missing">Imagen no disponible en uploads/rostros</div>
                            <?php endif; ?>
                        </div>
                        <div class="face-meta">
                            <div class="face-name"><?php echo htmlspecialchars($face['display_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="face-sub">ID usuario: <?php echo (int) $face['user_id']; ?></div>
                            <div class="face-sub">Usuario: <?php echo htmlspecialchars($face['username'] !== '' ? $face['username'] : 'N/D', ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="face-sub">Archivo: <?php echo htmlspecialchars($face['filename'] !== '' ? $face['filename'] : 'N/D', ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="face-sub">Registro: <?php echo htmlspecialchars($face['created_at'] !== '' ? $face['created_at'] : 'N/D', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <h2 class="section-title">Imagenes detectadas en uploads</h2>

        <?php if ($totalImages === 0): ?>
            <div class="empty">No se encontraron imagenes en la carpeta uploads.</div>
        <?php else: ?>
            <section class="grid">
                <?php foreach ($currentImages as $img): ?>
                    <article class="item">
                        <a href="<?php echo htmlspecialchars($img['path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                            <img src="<?php echo htmlspecialchars($img['path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($img['name'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                        </a>
                        <div class="meta">
                            <div class="name"><?php echo htmlspecialchars($img['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="sub">
                                <?php echo bytesToHuman((int) $img['size']); ?>
                                ·
                                <?php echo date('Y-m-d H:i:s', (int) $img['mtime']); ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <?php if ($totalPages > 1): ?>
                <nav class="pagination" aria-label="Paginacion">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>">Anterior</a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <?php if ($i === $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>">Siguiente</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
