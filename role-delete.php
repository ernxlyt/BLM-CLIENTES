<?php
include_once 'config/database.php';
include_once 'models/Role.php';
include_once 'utils/session.php';

requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$role = new Role($db);

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: roles.php");
    exit();
}

$role->id_rol = $_GET['id'];

if($role->delete()) {
    header("Location: roles.php");
} else {
    header("Location: roles.php");
}
exit();
?>
