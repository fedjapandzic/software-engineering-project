<?php
session_start();
require './vendor/autoload.php';
require_once __DIR__ . '/rest/services/PetsService.class.php';

Flight::register('petsService', 'PetsService');

include 'rest/routes/RenderRoutes.php';
include 'rest/UserAuthenticationRoutes.php';
include 'rest/routes/PetsRoutes.php';


?>
