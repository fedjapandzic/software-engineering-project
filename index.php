<?php
session_start();

require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$host = getenv('DB_HOST');
$user = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');
$port = getenv('DB_PORT');
$dbname = getenv('DB_NAME');
$failed_attempts = isset($_SESSION['failed_attempts']) ? $_SESSION['failed_attempts'] : 0;

$db=pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

include 'backend/RenderRoutes.php';
include 'backend/UserAuthenticationRoutes.php';

Flight::start();
?>
