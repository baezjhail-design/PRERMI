<?php
// PRERMI/api/status.php
// Devuelve el estado actual reportado por ESP32

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$base = __DIR__;
$statusFile = $base . '/status.json';
$controlFile = $base . '/control.json';

$status = [];
if (file_exists($statusFile)) {
    $status = json_decode(file_get_contents($statusFile), true);
    if (!is_array($status)) $status = [];
}

$control = [];
if (file_exists($controlFile)) {
    $control = json_decode(file_get_contents($controlFile), true);
    if (!is_array($control)) $control = [];
}

echo json_encode(['status'=>$status,'control'=>$control]);
