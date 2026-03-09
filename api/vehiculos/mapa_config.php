<?php
// API/vehiculos/mapa_config.php
// Simple archivo con configuración para el mapa (centro y zoom)
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
  "center" => ["lat"=>18.7357, "lng"=>-70.1627],
  "zoom" => 8
]);
