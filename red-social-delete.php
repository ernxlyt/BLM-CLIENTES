<?php
// Include database and required files
include_once 'config/database.php';
include_once 'models/SocialNetwork.php';
include_once 'utils/session.php';

// Require login
requireLogin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize social network object
$social_network = new SocialNetwork($db);

// Check if ID is set
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: redes-sociales.php");
    exit();
}

// Set social network ID
$social_network->id_red = $_GET['id'];

// Check if social network exists
if(!$social_network->readOne()) {
    header("Location: redes-sociales.php");
    exit();
}

// Get client ID for redirect if needed
$id_cliente = $social_network->id_cliente;

// Check if we should redirect to client view
$redirect_to_client = isset($_GET['client']) && $_GET['client'] == 'true';

// Delete social network
if($social_network->delete()) {
    if($redirect_to_client) {
        header("Location: client-view.php?id=" . $id_cliente);
    } else {
        header("Location: redes-sociales.php");
    }
} else {
    if($redirect_to_client) {
        header("Location: client-view.php?id=" . $id_cliente);
    } else {
        header("Location: redes-sociales.php");
    }
}
exit();
?>
