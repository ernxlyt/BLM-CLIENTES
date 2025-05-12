<?php
include_once 'config/database.php';
include_once 'models/Plan.php';
include_once 'utils/session.php';

requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$plan = new Plan($db);

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: planes.php");
    exit();
}

$plan->id_plan = $_GET['id'];

if($plan->delete()) {
    header("Location: planes.php");
} else {
    header("Location: planes.php");
}
exit();
?>
