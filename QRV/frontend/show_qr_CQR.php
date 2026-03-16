<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>QR Contenedor</title>
    <script src="../assets/qrcode.min.js"></script>
</head>
<body>
    <h2>Código QR</h2>
    <div id="qrcode"></div>

    <?php
    require_once "../includes/db_connect.php";
    $idCont = $_GET['id'] ?? 1;

// Generar token
$token = bin2hex(random_bytes(4));
$expira = date("Y-m-d H:i:s", time() + 300);

$stmt = $conn->prepare("UPDATE contenedores_registrados 
                        SET ultimo_token = ?, token_generado_en = NOW(), token_expira_en = ?
                        WHERE id = ?");
$stmt->execute([$token, $expira, $idCont]);

$urlQR = "https://prermi.duckdns.org/PRERMI/QRV/frontend/login_CQR.php?contenedor=$idCont&token=$token";
?>

<script>
    new QRCode(document.getElementById("qrcode"), {
        text: "<?php echo $urlQR ?>",
        width: 240,
        height: 240
    });
</script>

</body>
</html>
