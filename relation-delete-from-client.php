<?php
include_once 'config/database.php';
include_once 'models/Relation.php';
include_once 'utils/session.php';

requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$relation = new Relation($db);

if(!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['client_id']) || empty($_GET['client_id'])) {
    header("Location: index.php");
    exit();
}

$relation->id_relacion = $_GET['id'];
$client_id = $_GET['client_id'];

if($relation->delete()) {
    header("Location: client-view.php?id=" . $client_id);
} else {
    header("Location: client-view.php?id=" . $client_id);
}
exit();
?>
