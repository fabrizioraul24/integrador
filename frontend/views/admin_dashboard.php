<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 1) {
    header("Location: login.php");
    exit;
}
?>
<?php include_once 'dashboard_template.php'; ?>


