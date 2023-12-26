<?php
Flight::route('POST /registracija', function(){
    
    global $db;
    $full_name = Flight::request()->data->full_name;
    $username = Flight::request()->data->username;
    $email = Flight::request()->data->email;
    $phone_number = Flight::request()->data->phone_number;
    $password = Flight::request()->data->password;
    $repeat_password = Flight::request()->data->repeat_password;

    $temp_full_name=$full_name;
    $temp_username=$username;
    $temp_email=$email;
    $temp_phone_number=$phone_number;


    // Check username requirements
    if (strlen($username) <= 3) {
        echo '<script>alert("Username must have more than 3 characters.")</script>';
        include './html/register.html';
        return;
    }
    if (!ctype_alnum($username)) {
        echo '<script>alert("Username should contain only alphanumeric characters.")</script>';
        include './html/register.html';
        return;
    }
    $check_username_query = "SELECT * FROM account WHERE username='$username' LIMIT 1";
    $result_username = pg_query($db,$check_username_query);

    if (pg_num_rows($result_username) > 0) {
        echo '<script>alert("Username already exists. Choose a different username.")</script>';
        include './html/register.html';
        return;
    }

    // Username requirements checked




    //Check email requirements
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo '<script>alert("Invalid email format.")</script>';
        include './html/register.html';
        return;
    }

    // Extract domain from email
    $email_parts = explode('@', $email);
    $domain = end($email_parts);

    // Check MX records for the domain
    if (!checkdnsrr($domain, 'MX')) {
        echo '<script>alert("Invalid email domain.")</script>';
        include './html/register.html';
        return;
    }
    // Email requirements checked




    // Check password requirements
    if (strlen($password) <= 8) {
        echo '<script>alert("Password must have more than 8 characters.")</script>';
        include './html/register.html';
        return;
    }

    if($password != $repeat_password){
        echo '<script>alert("Repeated password does not match.")</script>';
        include './html/register.html';
        return;
    }

    $hashed_password = strtoupper(hash('sha1', $password));
    $prefix = substr($hashed_password, 0, 5);
    $suffix = substr($hashed_password, 5);

    $api_url = "https://api.pwnedpasswords.com/range/" . $prefix;
    $api_response = file_get_contents($api_url);

    if (strpos($api_response, $suffix) !== false) {
        echo '<script>alert("Password is commonly used and insecure. Choose a stronger password.")</script>';
        include './html/register.html';
        return;
    }

    //Password requirements checked




    //Check phone number requirements
    // Check if phone number is unique
    $check_phone_query = "SELECT * FROM account WHERE phone_number = '$phone_number'";
    $result = pg_query($db, $check_phone_query);

    if (pg_num_rows($result) > 0) {
        echo '<script>alert("Phone number is already registered.")</script>';
        include './html/register.html';
        return;
    }

    // Validate phone number using Google phone library
    $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
    try {
        $phoneNumberProto = $phoneUtil->parse($phone_number, "US"); 
        if (!$phoneUtil->isValidNumber($phoneNumberProto)) {
            echo '<script>alert("Invalid phone number format.")</script>';
            include './html/register.html';
        return;
        }
    } catch (\libphonenumber\NumberFormatException $e) {
        echo '<script>alert("Invalid phone number format.")</script>';
        include './html/register.html';
        return;
    }
    //Phone number requirements checked

    $email_verification_token = bin2hex(random_bytes(32));

    // Send email
        $mail = new PHPMailer(true);
        //Server settings
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'fedjapandzic1@gmail.com'; // Your Gmail username
        $mail->Password = 'quxu ussv kuaa nclg'; // Your Gmail password
        $mail->SMTPSecure = 'ssl'; // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 465; // TCP port to connect to
        //Recipients
        $mail->setFrom('fedjapandzic1@gmail.com', 'Fedja Pandzic');
        $mail->addAddress($email, $full_name);
    
        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Verification link for SSSD Project';
        $mail->Body    = 'Thank you for registering! Please click on the link to verify your email: <a href=' . "http://localhost/software-engineering-project/verify/$email_verification_token" . '>Verify Email</a>';
    
        $mail->send();
        echo 'Message has been sent';
    
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    

    // Inserting user into database
    $insert_user_query = "INSERT INTO account (full_name, username, email, phone_number, password_hashed,email_verification_token,is_verified) 
                        VALUES ('$full_name', '$username', '$email', '$phone_number', '$hashed_password','$email_verification_token', 0)";
    $find_added_user = "SELECT uid FROM account WHERE full_name='$full_name'";

    $result = pg_query($db, $insert_user_query);
    $user_id = pg_query($db, $find_added_user);
    $insert_cart_query = "INSERT INTO cart (account_id) VALUES ('$user_id')";
    $result_for_cart = pg_query($db, $insert_cart_query);
    unset($temp_full_name);
    unset($temp_username);
    unset($temp_email);
    unset($temp_phone_number);

    if ($result) {
        Flight::redirect('/checkyouremail');
    } else {
    // Query failed, handle the error
    echo '<script>alert("Error inserting user. Try again.")</script>';
    include './html/register.html';
    }

   
    
});