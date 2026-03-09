<?php
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$password = "";
$dbname = "prer_mi";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["error"=>"BD desconectada"]);
    exit;
}

$sql = "SELECT * FROM registros_vehiculares ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

if($result->num_rows > 0){
    $row = $result->fetch_assoc();
    echo json_encode($row);
} else {
    echo json_encode(["error"=>"Sin registros"]);
}

$conn->close();
?>