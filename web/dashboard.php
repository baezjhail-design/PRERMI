<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$usuario = $_SESSION['usuario'];
$isAdmin = ($usuario === 'admin');
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.header {
    text-align: center;
    padding: 2rem;
}

.page-title {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.page-subtitle {
    font-size: 1rem;
    opacity: 0.9;
}

/* BOTÓN BIOREGISTER */
.biobutton {
    background: white;
    color: #667eea;
    font-weight: 600;
    padding: 0.7rem 1.4rem;
    border-radius: 8px;
    text-decoration: none;
    display: inline-block;
    margin-top: 1rem;
    transition: all 0.3s ease;
}

.biobutton:hover {
    background: #f1f3ff;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.container {
    padding: 2rem;
    text-align: center;
}

.card {
    background: white;
    color: #333;
    padding: 1.5rem;
    margin: 1rem auto;
    border-radius: 10px;
    width: 300px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}
</style>
</head>

<body>

<div class="header">
    <h1 class="page-title">Panel de Control</h1>
    <p class="page-subtitle">Bienvenido, <?php echo htmlspecialchars($usuario); ?></p>

    <!-- BOTÓN QUE REDIRECCIONA -->
    <a href="BIOreguister.php" class="biobutton">
        <i class="fas fa-dna"></i> Registro Biométrico
    </a>
</div>

<div class="container">
    <div class="card">
        <h3>Usuarios</h3>
        <p>Administrar usuarios del sistema</p>
    </div>

    <div class="card">
        <h3>Configuración</h3>
        <p>Ajustes del sistema</p>
    </div>
</div>

</body>
</html>