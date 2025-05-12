<?php
include_once 'utils/session.php';

clearUserSession();
header("Location: login.php");
exit();
?>
