<?php
/**
 * Template HTML header para todas las pÃ¡ginas de usuario
 * Uso: include(__DIR__ . '/../../template_header.php');
 */

if (!isset($pageTitle)) $pageTitle = 'PRERMI';
if (!isset($pageIcon)) $pageIcon = 'fas fa-dashboard';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - PRERMI</title>
    <link rel="icon" type="image/x-icon" href="/PRERMI/web/assets/img/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="/PRERMI/web/assets/img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="manifest" href="manifest.json">
    <?php if (isset($extraCss)): ?>
        <?php echo $extraCss; ?>
    <?php endif; ?>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/PRERMI/index.php">
                <i class="fas fa-truck"></i> PRERMI
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/PRERMI/index.php">
                            <i class="fas fa-home"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-chart-line"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="usuarios/index_usuario.php">
                            <i class="fas fa-car"></i> Mis Datos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="perfil.php">
                            <i class="fas fa-user-circle"></i> Perfil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../api/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Salir
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- PAGE HEADER -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem 0; margin-bottom: 2rem;">
        <div class="container">
            <div class="page-title" style="font-size: clamp(1.2rem, 4vw, 1.8rem);">
                <i class="<?php echo htmlspecialchars($pageIcon); ?>"></i>
                <?php echo htmlspecialchars($pageTitle); ?>
            </div>
            <?php if (isset($pageSubtitle)): ?>
                <p class="page-subtitle" style="font-size: clamp(0.85rem, 2.5vw, 1rem);"><?php echo htmlspecialchars($pageSubtitle); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-container">
        <div class="container-fluid">

