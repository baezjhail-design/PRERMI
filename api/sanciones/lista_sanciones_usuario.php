<?php
require_once "../utils.php";

$user_id = requireUsuarioSession();
$pdo = getPDO();

try {

    $stmt = $pdo->prepare(""
        SELECT s.id, s.descripcion, s.peso, s.creado_en,
               s.contenedor_id AS contenedor_id
        FROM sanciones s
        WHERE s.user_id = ?
        ORDER BY s.creado_en DESC
    ""
    );
    $stmt->execute([$user_id]);

    jsonOk([
        "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch (Exception $e) {
    jsonErr("Error obteniendo sanciones", 500);
}