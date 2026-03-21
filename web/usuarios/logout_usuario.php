<?php
session_start();
session_destroy();
header("Location: /PRERMI/api/usuarios/logout.php");
exit();
