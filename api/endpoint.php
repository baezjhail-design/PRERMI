<?php
/**
 * endpoint.php
 * Registra/actualiza 2 contenedores fijos para uso global del sistema.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/utils.php';

$requiredColumns = [
    'id',
    'codigo_contenedor',
    'api_key',
    'nivel_basura',
    'ubicacion',
    'latitud',
    'longitud',
    'tipo_contenedor',
    'estado',
    'ultimo_token',
    'token_generado_en',
    'token_expira_en',
    'creado_en',
    'actualizado_en'
];

$fixedContainers = [
    [
        'id' => 1,
        'codigo_contenedor' => 'CONT-PRERMI-001',
        'api_key' => 'PRERMI_KEY_CONT_001_FIXED',
        'nivel_basura' => 15,
        'ubicacion' => 'Santiago de los Caballeros - Zona Centro',
        'latitud' => 19.4517,
        'longitud' => -70.6970,
        'tipo_contenedor' => 'metal',
        'estado' => 'activo',
        'ultimo_token' => 'TOKEN_CONT_001_FIXED',
        'token_generado_en' => '2026-03-06 08:00:00',
        'token_expira_en' => '2026-12-31 23:59:59',
        'creado_en' => '2026-03-06 08:00:00',
        'actualizado_en' => '2026-03-06 08:00:00'
    ],
    [
        'id' => 2,
        'codigo_contenedor' => 'CONT-PRERMI-002',
        'api_key' => 'PRERMI_KEY_CONT_002_FIXED',
        'nivel_basura' => 22,
        'ubicacion' => 'Santo Domingo - Distrito Nacional',
        'latitud' => 18.4861,
        'longitud' => -69.9312,
        'tipo_contenedor' => 'reciclable',
        'estado' => 'activo',
        'ultimo_token' => 'TOKEN_CONT_002_FIXED',
        'token_generado_en' => '2026-03-06 08:00:00',
        'token_expira_en' => '2026-12-31 23:59:59',
        'creado_en' => '2026-03-06 08:00:00',
        'actualizado_en' => '2026-03-06 08:00:00'
    ]
];

try {
    $pdo = getPDO();

    // Verifica que la tabla tenga los campos esperados por el endpoint.
    $descStmt = $pdo->query('DESCRIBE contenedores_registrados');
    $existingColumns = array_map(
        static fn($row) => $row['Field'],
        $descStmt->fetchAll(PDO::FETCH_ASSOC)
    );

    $missing = array_values(array_diff($requiredColumns, $existingColumns));
    if (!empty($missing)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'msg' => 'La tabla contenedores_registrados no contiene todos los campos requeridos.',
            'faltantes' => $missing
        ]);
        exit;
    }

    $sql = "INSERT INTO contenedores_registrados (
                id, codigo_contenedor, api_key, nivel_basura, ubicacion,
                latitud, longitud, tipo_contenedor, estado, ultimo_token,
                token_generado_en, token_expira_en, creado_en, actualizado_en
            ) VALUES (
                :id, :codigo_contenedor, :api_key, :nivel_basura, :ubicacion,
                :latitud, :longitud, :tipo_contenedor, :estado, :ultimo_token,
                :token_generado_en, :token_expira_en, :creado_en, :actualizado_en
            )
            ON DUPLICATE KEY UPDATE
                codigo_contenedor = VALUES(codigo_contenedor),
                api_key = VALUES(api_key),
                nivel_basura = VALUES(nivel_basura),
                ubicacion = VALUES(ubicacion),
                latitud = VALUES(latitud),
                longitud = VALUES(longitud),
                tipo_contenedor = VALUES(tipo_contenedor),
                estado = VALUES(estado),
                ultimo_token = VALUES(ultimo_token),
                token_generado_en = VALUES(token_generado_en),
                token_expira_en = VALUES(token_expira_en),
                creado_en = VALUES(creado_en),
                actualizado_en = VALUES(actualizado_en)";

    $stmt = $pdo->prepare($sql);

    $affectedRows = 0;
    foreach ($fixedContainers as $container) {
        $stmt->execute($container);
        $affectedRows += $stmt->rowCount();
    }

    registrarLog('Endpoint fijo de contenedores ejecutado: 2 contenedores insertados/actualizados', 'info');

    jsonOk([
        'msg' => 'Contenedores fijos registrados correctamente',
        'total_contenedores_procesados' => count($fixedContainers),
        'filas_afectadas' => $affectedRows,
        'contenedores' => $fixedContainers
    ]);
} catch (Throwable $e) {
    jsonErr('Error en endpoint de contenedores: ' . $e->getMessage(), 500);
}
