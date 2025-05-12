<?php
include_once 'config/database.php';
include_once 'models/Client.php';
include_once 'utils/session.php';

requireLogin();
requireAdmin(); 

$database = new Database();
$db = $database->getConnection();

$client = new Client($db);

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$client->id_cliente = $_GET['id'];

if(!$client->readOne(getCurrentUserId(), isAdmin())) {
    header("Location: index.php");
    exit();
}

if($client->delete()) {
    header("Location: index.php");
} else {
    header("Location: index.php");
}
exit();
?>
