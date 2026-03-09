<?php
session_start();

$input = json_decode(file_get_contents("php://input"), true);

if ($input) {
    $_SESSION['usuario_id'] = $input['id'];
    $_SESSION['usuario_nombre'] = $input['nombre'];
    $_SESSION['usuario_token'] = $input['token'];

    echo json_encode(["ok"=>true]);
} else {
    echo json_encode(["ok"=>false]);
}
