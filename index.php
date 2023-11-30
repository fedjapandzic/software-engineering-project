<?php
require 'vendor/autoload.php';

Flight::register('db', 'mysqli', array('localhost', 'root', '', 'sssd'));
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

Flight::route('/', function(){
    echo 'Hello World!';
});

Flight::route('POST /register', function(){
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
    $result_username = Flight::db()->query($check_username_query);

    if ($result_username->num_rows > 0) {
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

    
    
    

    //Check phone number requirements
    // Check if phone number is unique
    $check_phone_query = "SELECT * FROM account WHERE phone_number = $phone_number";
    $result = Flight::db()->query($check_phone_query);

    if ($result->num_rows > 0) {
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


    //Generating unique token for email verification
    $email_verification_token = bin2hex(random_bytes(32));



    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Inserting user into database
    $insert_user_query = "INSERT INTO account (full_name, username, email, phone_number, password_hashed,email_verification_token,is_verified) 
                        VALUES ('$full_name', '$username', '$email', $phone_number, '$hashed_password','$email_verification_token', 0)";

    
    
});

Flight::start();
?>
