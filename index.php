<?php
session_start();

require 'vendor/autoload.php';
require_once __DIR__ . '/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$host=Config::$DB_HOST;
$user=Config::$DB_USERNAME;
$password=Config::$DB_PASSWORD;
$port=Config::$DB_PORT;
$dbname=Config::$DB_NAME;

$db=pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");


Flight::route('/', function(){
    include 'html/register.html';
});

Flight::route('/createTable', function(){

    global $db;
    $query="CREATE TABLE \"account\" (
        \"uid\" serial PRIMARY KEY,
        \"full_name\" varchar(256) NOT NULL,
        \"username\" varchar(256) NOT NULL,
        \"email\" varchar(256) NOT NULL,
        \"phone_number\" varchar(20) NOT NULL,
        \"password_hashed\" varchar(512) NOT NULL,
        \"email_verification_token\" varchar(64) DEFAULT NULL,
        \"is_verified\" smallint NOT NULL DEFAULT 0
    );";
      $result = pg_query($db, $query);
      if($result){
        echo "It works!";
      }
      else{
        echo "it don' work :(";
      }
});

Flight::route('/account', function(){
    global $db;
    $query = "SELECT * FROM account";
    $result = pg_query($db, $query);
    if($result){
        echo "it worked";
        return $result;
    }
    else{
        echo "didnt work";
    }
});



Flight::route('POST /registracija', function(){
    
    global $db;
    $full_name = Flight::request()->data->full_name;
    $username = Flight::request()->data->username;
    $email = Flight::request()->data->email;
    $phone_number = Flight::request()->data->phone_number;
    $password = Flight::request()->data->password;



    // Check username requirements
    if (strlen($username) <= 3) {
        Flight::json(['error' => 'Username must have more than 3 characters.']);
        return;
    }
    if (!ctype_alnum($username)) {
        Flight::json(['error' => 'Username should contain only alphanumeric characters.']);
        return;
    }
    $check_username_query = "SELECT * FROM account WHERE username='$username' LIMIT 1";
    $result_username = pg_query($db,$check_username_query);

    if (pg_num_rows($result_username) > 0) {
        Flight::json(['error' => 'Username already exists. Choose a different username.']);
        return;
    }
    // Username requirements checked


    // Check password requirements
    if (strlen($password) <= 8) {
        Flight::json(['error' => 'Password must have more than 8 characters.']);
        return;
    }

    $hashed_password = strtoupper(hash('sha1', $password));
    $prefix = substr($hashed_password, 0, 5);
    $suffix = substr($hashed_password, 5);

    $api_url = "https://api.pwnedpasswords.com/range/" . $prefix;
    $api_response = file_get_contents($api_url);

    if (strpos($api_response, $suffix) !== false) {
        Flight::json(['error' => 'Password is commonly used and insecure. Choose a stronger password.']);
        return;
    }

    //Password requirements checked




    //Check email requirements
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Flight::json(['error' => 'Invalid email format.']);
        return;
    }

    // Extract domain from email
    $email_parts = explode('@', $email);
    $domain = end($email_parts);

    // Check MX records for the domain
    if (!checkdnsrr($domain, 'MX')) {
        Flight::json(['error' => 'Invalid email domain.']);
        return;
    }
    // Email requirements checked

    $email_verification_token = bin2hex(random_bytes(32));

    // Send email

        // WORK IN PROGRESS
        $mail = new PHPMailer(true);

    
        //Server settings
        $mail->SMTPDebug = 1; // Enable verbose debug output
        $mail->isSMTP();
        $mail->Host = Config::$SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = Config::$SMTP_USERNAME; // Your Gmail username
        $mail->Password = Config::$SMTP_PASSWORD; // Your Gmail password
        $mail->SMTPSecure = 'ssl'; // Enable TLS encryption, `ssl` also accep   ted
        $mail->Port = Config::$SMTP_PORT; // TCP port to connect to
        //Recipients
        $mail->setFrom('fedjapandzic1@gmail.com', 'Fedja Pandzic');
        $mail->addAddress($email, $full_name);
    
        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Verification link for SSSD Project';
        $mail->Body    = 'Thank you for registering! Please click on the link to verify your email: <a href=' . "http://localhost:80/sssd-project/verify/$email_verification_token" . '>Verify Email</a>';
    
        $mail->send();
        echo 'Message has been sent';
    
    
    

    //Check phone number requirements
    // Check if phone number is unique
    $check_phone_query = "SELECT * FROM account WHERE phone_number = '$phone_number'";
    $result = pg_query($db, $check_phone_query);

    if (pg_num_rows($result) > 0) {
        Flight::json(['error' => 'Phone number is already registered.']);
        return;
    }

    // Validate phone number using Google phone library
    $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
    try {
        $phoneNumberProto = $phoneUtil->parse($phone_number, "US"); 
        if (!$phoneUtil->isValidNumber($phoneNumberProto)) {
            Flight::json(['error' => 'Invalid phone number format.']);
            return;
        }
    } catch (\libphonenumber\NumberFormatException $e) {
        Flight::json(['error' => 'Invalid phone number format.']);
        return;
    }
    //Phone number requirements checked

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    

    // Inserting user into database
    $insert_user_query = "INSERT INTO account (full_name, username, email, phone_number, password_hashed,email_verification_token,is_verified) 
                        VALUES ('$full_name', '$username', '$email', '$phone_number', '$hashed_password','$email_verification_token', 0)";

    $result = pg_query($db, $insert_user_query);

    if ($result) {
        echo '<script>alert("Check your email for verification purposes.")</script>';
        Flight::redirect('/login');
    } else {
    // Query failed, handle the error
        Flight::json(['error' => 'Error inserting user ']);
    }

   
    
});

Flight::route('/verify/@EVT', function($EVT){
    global $db;
    $evt_exists_query = "SELECT * FROM account WHERE email_verification_token = '$EVT' LIMIT 1";
    $result = pg_query($db, $evt_exists_query);
    if($result){
        $update_verification_query = "UPDATE account SET is_verified = 1 WHERE email_verification_token = '$EVT'";
        pg_query($db, $update_verification_query);
        Flight::redirect("/UserVerified");
    }
    else{
        echo 'This link does not exist';
    }
});

Flight::route('/UserVerified', function(){
    echo 'user is verified';
});

Flight::route('/homeRoute', function(){
    if(isset($_SESSION['full_name'])){
        echo '<html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Home Page</title>
            <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
            <link rel="stylesheet" href="css/home.css">
        </head>
        <body>
        <div class="container">
        <div class="row">
        <div class="message-container">
        <h1>' . $_SESSION['full_name'] . ', you have successfully logged in, congrats!</h1>
    </div></div>';
        include 'html/home.html';
    }
    else {
        echo '<p>No full name found. Could there be an error?</p>';
    }
});

Flight::route('/login', function(){
    include 'html/login.html';
});

Flight::route('/changePassword', function(){
    include 'html/changepass.html';
});

Flight::route('POST /passwordChange', function(){
    global $db;
    $old_pass = Flight::request()->data->old_password;
    $new_pass = Flight::request()->data->new_password;
    $repeat_pass = Flight::request()->data->repeat_new_password;
    $old_hashed_pass = $_SESSION['password'];

    if(password_verify($old_pass,$old_hashed_pass) && $new_pass==$repeat_pass){
        $hashed_password = strtoupper(hash('sha1', $new_pass));
        $prefix = substr($hashed_password, 0, 5);
        $suffix = substr($hashed_password, 5);

        $api_url = "https://api.pwnedpasswords.com/range/" . $prefix;
        $api_response = file_get_contents($api_url);

        if (strpos($api_response, $suffix) !== false) {
            Flight::json(['error' => 'Password is commonly used and insecure. Choose a stronger password.']);
            return;
        }
        else{
            $hashed_password = password_hash($new_pass, PASSWORD_BCRYPT);
            $change_pass_query = "UPDATE account SET password_hashed = '$hashed_password' WHERE password_hashed='$old_hashed_pass'";
            pg_query($db, $change_pass_query);
            echo '<script>alert("Password successfully updated")</script>';
            sleep(3);
            Flight::redirect('/login');
        }
    }
});

Flight::route('/twofactorauthenticator', function(){
    if (isset($_SESSION['phone_number'])) {
        include 'html/twofactorauth.html';
    } else {
        // Redirect to login or handle the case where phone_number is not set.
        Flight::redirect('/login');
    }

});

Flight::route('/logout', function(){
    unset($_SESSION['phone_number']);
    unset($_SESSION['full_name']);
    if(!isset($_SESSION['phone_number']) && !isset($_SESSION['full_name'])){
        Flight::redirect('/login');
    }
});

Flight::route('POST /sendSMSCode', function(){
    $code = str_pad(rand(0, pow(10, 4)-1), 4, '0', STR_PAD_LEFT);
    $phone = $_SESSION['phone_number'];
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://rest.nexmo.com/sms/json");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
        'from' => 'Vonage APIs',
        'text' => "Your code: $code",
        'to' => "$phone",
        'api_key' => Config::$NEXMO_API_KEY,
        'api_secret' => Config::$NEXMO_API_SECRET
    )));

    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
    }

    curl_close($ch);

    echo $result;
    $_SESSION['sms_code'] = $code;
    Flight::redirect('/twofactorauthenticator');
});

Flight::route('POST /submitCode', function(){

    $submited_code = Flight::request()->data->code_input;
    if($submited_code == $_SESSION['sms_code']){
        Flight::redirect('/homeRoute');
    } else {
        echo '<script>alert("Incorrect code, try again!")</script>';
    }

});

Flight::route('POST /loginUser', function(){
    global $db;
    $username_or_email = Flight::request()->data->username_or_email;
    $password = Flight::request()->data->password;

    // Validate input (you may need to adjust this based on your login requirements)
    if (empty($username_or_email) || empty($password)) {
        Flight::json(['error' => 'Username/email and password are required.']);
        return;
    }

    // Check if the input is an email or username
    $field = filter_var($username_or_email, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

    // Fetch user from the database based on email/username
    $fetch_user_query = "SELECT * FROM account WHERE $field = '$username_or_email' LIMIT 1";
    $result = pg_query($db, $fetch_user_query);

    if (pg_num_rows($result) === 0) {
        Flight::json(['error' => 'Invalid username/email or password.']);
        return;
    }

    $user = pg_fetch_assoc($result);

    // Verify the password
    if (password_verify($password, $user['password_hashed']) && $user['is_verified']==1) {
        // Password is correct, log in the user
        $_SESSION['phone_number'] = $user['phone_number'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['password'] = $user['password_hashed'];
        Flight::redirect('/twofactorauthenticator');
    } else {
        // Password is incorrect
        echo '<script>alert("Invalid password or user may not be verified. Please check your email for verification.")</script>';
        include 'html/login.html';
    }
});

Flight::route('/delete', function(){
    global $db;
    $query= "DELETE FROM account WHERE email='fedjap01@gmail.com'";
    pg_query($db,$query);
});


Flight::start();
?>
