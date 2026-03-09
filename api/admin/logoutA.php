<?php
session_start();
session_destroy();
header("Location: /PRERMI/web/admin/loginA.php");
exit;
