<?php
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$password = "";
$dbname = "prer_mi";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["error" => "Error BD"]);
    exit;
}

$sql = "SELECT peso, sensor_metal, fecha 
        FROM contenedores 
        ORDER BY id DESC 
        LIMIT 1";

$result = $conn->query($sql);

if ($result->num_rows > 0) {

    $row = $result->fetch_assoc();
    echo json_encode($row);

} else {

    echo json_encode([
        "peso" => 0,
        "sensor_metal" => 0,
        "fecha" => "Sin datos"
    ]);
}

$conn->close();
?>