<?php
include_once 'config/database.php';
include_once 'models/Relation.php';
include_once 'utils/session.php';

requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$relation = new Relation($db);

if(!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['user_id']) || empty($_GET['user_id'])) {
    header("Location: users.php");
    exit();
}

$relation->id_relacion = $_GET['id'];
$user_id = $_GET['user_id'];

if($relation->delete()) {
    header("Location: user-view.php?id=" . $user_id);
} else {
    header("Location: user-view.php?id=" . $user_id);
}
exit();
?>
