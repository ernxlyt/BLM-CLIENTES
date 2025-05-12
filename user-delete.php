<?php
include_once 'config/database.php';
include_once 'models/User.php';
include_once 'utils/session.php';

requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$user->id_usuario = $_GET['id'];

if($user->delete()) {
    header("Location: users.php");
} else {
    header("Location: users.php");
}
exit();
?>
