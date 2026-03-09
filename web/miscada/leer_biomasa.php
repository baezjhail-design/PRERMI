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

$sql = "SELECT * FROM biomasa ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {

    $row = $result->fetch_assoc();
    echo json_encode($row);

} else {

    echo json_encode([
        "relay" => 0,
        "ventilador" => 0,
        "peltier1" => 0,
        "peltier2" => 0,
        "gases" => 0,
        "fecha" => "Sin datos"
    ]);
}

$conn->close();
?>