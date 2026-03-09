<?php
// utils_contenedores.php
require_once __DIR__ . '/../../config/db_config.php';

// Funciones auxiliares para contenedores

function validarContenedor($codigo) {
    // La tabla `contenedores_registrados` fue eliminada.
    // Para mantener compatibilidad, aceptamos el código proporcionado
    // como identificador válido si no está vacío.
    $codigo = trim((string)$codigo);
    if ($codigo === '') return false;
    return $codigo;
}