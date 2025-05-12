<?php
include_once 'config/database.php';
include_once 'models/Empresa.php';
include_once 'utils/session.php';

requireLogin();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$empresa = new Empresa($db);

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: empresas.php");
    exit();
}

$empresa->id_empresa = $_GET['id'];

if($empresa->delete()) {
    header("Location: empresas.php");
} else {
    header("Location: empresas.php");
}
exit();
?>
