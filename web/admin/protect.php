<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: /PRERMI/web/admin/loginA.php");
    exit;
}
