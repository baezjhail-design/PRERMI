<?php
// CREADO POR Jhail Baez - 2026-03-15
// Endpoint de simulación para ESP32-S3 CAM
// Simula el ciclo completo del contenedor inteligente
// Uso: POST JSON con parámetros opcionales para simular eventos

header('Content-Type: application/json; charset=utf-8');

// --- Configuración fija (simula hardware y credenciales) ---
$CONTAINER_ID_FIXED = 15;
$CONTAINER_CODE_FIXED = 'CONT-PRERMI-001';
$CONTAINER_API_KEY_FIXED = 'PRERMI_KEY_CONT_001_FIXED';
$CONTAINER_LOCATION_FIXED = 'Santiago de los Caballeros - Zona Centro';
$CONTAINER_TOKEN_FIXED = 'TOKEN_CONT_001_FIXED';
$MCU_DEVICE_ID = 'ESP32-S3-CAM';
$MCU_DEVICE_KEY = 'PRERMI-ESP32S3-CAM-2026-$ecure!';

// --- Simulación de ciclo ---
function simulate_container_cycle($params) {
    // Paso 1: Verificación facial (simulada)
    $face_verified = $params['face_verified'] ?? true;
    $user_id = $params['user_id'] ?? rand(1000, 9999);
    $user_name = $params['user_name'] ?? 'Usuario Simulado';

    if (!$face_verified) {
        return [
            'success' => false,
            'step' => 'face_verification',
            'message' => 'Identidad no confirmada',
            'locked_until' => time() + 60,
        ];
    }

    // Paso 2: Medición de peso
    $weight_kg = $params['weight_kg'] ?? round(mt_rand(10, 100) / 10, 2);
    $metal_detected = $params['metal_detected'] ?? false;

    // Paso 3: Verificación de metal
    if ($metal_detected) {
        // Simula sanción
        return [
            'success' => true,
            'step' => 'metal_detected',
            'user_id' => $user_id,
            'user_name' => $user_name,
            'weight_kg' => $weight_kg,
            'sanction_sent' => true,
            'message' => 'Metal detectado, sanción registrada',
        ];
    }

    // Paso 4: Registro de depósito
    $kwh = $weight_kg * 0.0011;
    $cost_rd = round($kwh * 5.50, 2);

    // Simula envío de datos
    $deposit_data = [
        'id_usuario' => $user_id,
        'id_contenedor' => $CONTAINER_ID_FIXED,
        'token_usado' => $CONTAINER_TOKEN_FIXED,
        'peso' => $weight_kg,
        'tipo_residuo' => $metal_detected ? 'metal' : 'organico',
        'credito_kwh' => $kwh,
        'metal_detectado' => $metal_detected ? 1 : 0,
        'procesado_por' => 'Administrador',
        'observaciones' => 'N/A',
        'contenedor_id' => $CONTAINER_ID_FIXED,
    ];

    return [
        'success' => true,
        'step' => 'deposit_registered',
        'user_id' => $user_id,
        'user_name' => $user_name,
        'weight_kg' => $weight_kg,
        'kwh' => $kwh,
        'cost_rd' => $cost_rd,
        'deposit_data' => $deposit_data,
        'message' => 'Depósito registrado correctamente',
    ];
}

// --- Entrada principal ---
$input = file_get_contents('php://input');
$params = json_decode($input, true) ?: [];

$result = simulate_container_cycle($params);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
