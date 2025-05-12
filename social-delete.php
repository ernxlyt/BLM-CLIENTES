<?php
include_once 'config/database.php';
include_once 'models/SocialNetwork.php';
include_once 'utils/session.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

$social_network = new SocialNetwork($db);

if(!isset($_GET['type']) || !isset($_GET['id']) || !isset($_GET['client_id'])) {
    header("Location: index.php");
    exit();
}

$type = $_GET['type'];
$id = $_GET['id'];
$client_id = $_GET['client_id'];

$success = false;

if($type === 'instagram') {
    $success = $social_network->deleteInstagram($id);
} else if($type === 'facebook') {
    $success = $social_network->deleteFacebook($id);
} else if($type === 'youtube') {
    $success = $social_network->deleteYoutube($id);
}

if($success) {
    header("Location: client-view.php?id=" . $client_id);
} else {
    header("Location: index.php");
}
exit();
?>
