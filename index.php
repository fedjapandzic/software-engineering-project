<?php
session_start();


include 'backend/RenderRoutes.php';
include 'backend/UserAuthenticationRoutes.php';

Flight::start();
?>
