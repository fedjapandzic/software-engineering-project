<?php

require './vendor/autoload.php';
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
        $mail->Host = getenv('SMTP_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USERNAME'); // Your Gmail username
        $mail->Password = getenv('SMTP_PASSWORD'); // Your Gmail password
        $mail->SMTPSecure = 'ssl'; // Enable TLS encryption, `ssl` also accepted
        $mail->Port = getenv('SMTP_PORT'); // TCP port to connect to
        //Recipients
        $mail->setFrom('fedjapandzic1@gmail.com', 'Fedja Pandzic');
        $mail->addAddress($email, $full_name);
    
        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Verification link for SSSD Project';
        $mail->Body    = 'Thank you for registering! Please click on the link to verify your email: <a href=' . "https://se-project-vcc8.onrender.com/verify/$email_verification_token" . '>Verify Email</a>';
    
        $mail->send();
        echo 'Message has been sent';
    
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    

    // Inserting user into database
    $insert_user_query = "INSERT INTO account (full_name, username, email, phone_number, password_hashed,email_verification_token,is_verified) 
                        VALUES ('$full_name', '$username', '$email', '$phone_number', '$hashed_password','$email_verification_token', 0)";
    $find_added_user = "SELECT uid FROM account WHERE full_name='$full_name'";

    $result = pg_query($db, $insert_user_query);
    $found_user = pg_query($db, $find_added_user);
    $user_data = pg_fetch_assoc($found_user);
    $user_id = (int)$user_data['uid'];
    $insert_cart_query = "INSERT INTO cart (account_id) VALUES ('$user_id')";
    $result_for_cart = pg_query($db, $insert_cart_query);
    unset($temp_full_name);
    unset($temp_username);
    unset($temp_email);
    unset($temp_phone_number);

    if ($result && $result_for_cart) {
        Flight::redirect('/checkyouremail');
    } else {
    // Query failed, handle the error
    echo '<script>alert("Error inserting user. Try again.")</script>';
    include './html/register.html';
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
        echo '<script>alert("This link does not exist.")</script>';
        include './html/register.html';
    }
});

Flight::route('POST /passwordChange', function(){
    global $db;
    global $failed_attempts;
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
            echo '<script>alert("Password is commonly used and insecure. Choose a stronger password.")</script>';
            include './html/changepass.html';
            return;
        }
        else{
            $hashed_password = password_hash($new_pass, PASSWORD_BCRYPT);
            $change_pass_query = "UPDATE account SET password_hashed = '$hashed_password' WHERE password_hashed='$old_hashed_pass'";
            pg_query($db, $change_pass_query);
            echo '<script>alert("Password successfully updated")</script>';
            sleep(3);
            include './html/login.html';
        }
    }
});

Flight::route('POST /sendNewPass',function(){
    global $db;
    global $failed_attempts;
    $email = Flight::request()->data->email;

    $fetch_user_by_email_query = "SELECT * FROM account WHERE email = '$email' LIMIT 1";
    $result = pg_query($db, $fetch_user_by_email_query);
    if($result){
        $full_name_query= "SELECT full_name FROM account WHERE email = '$email' LIMIT 1";
        $full_name=pg_query($db, $fetch_user_by_email_query);
        $mail = new PHPMailer(true);
        $recovery_pass=bin2hex(random_bytes(5));

        //Server settings
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USERNAME'); // Your Gmail username
        $mail->Password = getenv('SMTP_PASSWORD'); // Your Gmail password
        $mail->SMTPSecure = 'ssl'; // Enable TLS encryption, `ssl` also accepted
        $mail->Port = getenv('SMTP_PORT'); // TCP port to connect to
        //Recipients
        $mail->setFrom('fedjapandzic1@gmail.com', 'Fedja Pandzic');
        $mail->addAddress($email, $full_name);
    
        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Recovery password for SSSD Project';
        $mail->Body    = 'You requested a recovery password. This is your new password: <strong>' . $recovery_pass . '</strong> . <br>Please be sure to change the password as soon as you use it.';
    
        $mail->send();
    
        $hashed_password = password_hash($recovery_pass, PASSWORD_BCRYPT);

        $change_pass_query = "UPDATE account SET password_hashed = '$hashed_password' WHERE email='$email'";
        pg_query($db, $change_pass_query);
        echo '<script>alert("Check your email for the new password")</script>';
        include './html/login.html';

        
    }

    else{
        echo '<script>alert("Account with provided email address does not exist.")</script>';
        include './html/forgotpassword.html';

    }
});

Flight::route('POST /sendSMSCode', function(){
    // $code = str_pad(rand(0, pow(10, 4)-1), 4, '0', STR_PAD_LEFT);
    $code = 1234;
    $_SESSION['code']= $code;
    $phone = $_SESSION['phone_number'];
    // $ch = curl_init();

    // curl_setopt($ch, CURLOPT_URL, "https://rest.nexmo.com/sms/json");
    // curl_setopt($ch, CURLOPT_POST, 1);
    // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
    //     'from' => 'Vonage APIs',
    //     'text' => "Your code: $code",
    //     'to' => "$phone",
    //     'api_key' => getenv('NEXMO_API_KEY'),
    //     'api_secret' => getenv('NEXMO_API_SECRET')
    // )));

    // $result = curl_exec($ch);

    // if (curl_errno($ch)) {
    //     echo 'cURL error: ' . curl_error($ch);
    // }

    // curl_close($ch);

    // echo $result;
    Flight::redirect('/twofactorauthenticator');
});

Flight::route('POST /submitCode', function(){

    $submited_code = Flight::request()->data->code_input;
    if($submited_code == $_SESSION['code']){
        Flight::redirect('/homeRoute');
    } else {
        echo '<script>alert("Incorrect code, try again!")</script>';
        include './html/twofactorauth.html';
    }

});

Flight::route('POST /loginUser', function(){
    global $db;
    $username_or_email = Flight::request()->data->username_or_email;
    $password = Flight::request()->data->password;
    global $failed_attempts;

    // Validate input (you may need to adjust this based on your login requirements)
    if (empty($username_or_email) || empty($password)) {
        echo '<script>alert("Username/email and password are required.")</script>';
        include './html/login.html';
        return;
    }

    // Check if the input is an email or username
    $field = filter_var($username_or_email, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

    // Fetch user from the database based on email/username
    $fetch_user_query = "SELECT * FROM account WHERE $field = '$username_or_email' LIMIT 1";
    $result = pg_query($db, $fetch_user_query);

    if (pg_num_rows($result) === 0) {
        echo '<script>alert("Invalid username/email or password.")</script>';
        include './html/login.html';
        return;
    }

    $user = pg_fetch_assoc($result);

    if ($failed_attempts >= 3) {
        // Check if the captcha is successfully completed
        $captcha_response = Flight::request()->data['h-captcha-response'];
        $captcha_data = array(
            'secret' => getenv('CAPTCHA_SECRET'),
            'response' => $captcha_response
        );
        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, "https://hcaptcha.com/siteverify");
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($captcha_data));
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
        $captcha_result = curl_exec($verify);
        $captcha_response_data = json_decode($captcha_result);

        if (!$captcha_response_data->success) {
            echo '<script>alert("Captcha verification failed.")</script>';
            include './html/login.html';
            return;
        }
    }

    // Verify the password
    if (password_verify($password, $user['password_hashed']) && $user['is_verified']==1) {
        // Password is correct, log in the user
        $_SESSION['phone_number'] = $user['phone_number'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['password'] = $user['password_hashed'];
        $_SESSION['is_verified'] = $user['is_verified'];
        $user_id= $user['uid'];
        $_SESSION['id']= $user_id;
        $user_cart_query = "SELECT uid FROM cart WHERE account_id = $user_id";
        $cart_id_result = pg_query($db, $user_cart_query);
        $cart_id_array = pg_fetch_assoc($cart_id_result);
        $_SESSION['cart_id'] = (int)$cart_id_array['uid'];
        unset($_SESSION['failed_attempts']);
        Flight::redirect('/twofactorauthenticator');
    } else {
        // Password is incorrect
        $_SESSION['failed_attempts'] = ++$failed_attempts;

        // If failed attempts exceed the limit, show captcha
        if ($failed_attempts >= 3) {
            include './html/login.html';
            echo '<script>alert("Please complete the captcha.")</script>';
            return;
        }

        echo '<script>alert("Invalid password or user may not be verified. Please check your email for verification.")</script>';
        include './html/login.html';
    }
});

Flight::route('POST /addToCart', function(){
    global $db;
    $pet_name = Flight::request()->data->pet_name;
    $cart_id = $_SESSION['cart_id'];
    $update_pet_query = "UPDATE pets SET cart_id = '$cart_id', is_reserved = 1 WHERE name= '$pet_name'";
    pg_query($db, $update_pet_query);
    Flight::redirect('/petshop');
});

Flight::route('GET /getPetsInCart', function(){
    global $db;
    $cart_id = $_SESSION['cart_id'];
    $get_pets_query = "SELECT pets.name, pets.price, pets.image_link
                       FROM pets
                       JOIN cart ON pets.cart_id = cart.uid
                       WHERE cart.uid = $cart_id";
    $result = pg_query($db,$get_pets_query);
    
    if ($result) {
        $pets = array();
        while ($row = pg_fetch_assoc($result)) {
            $pets[] = $row;
        }
        Flight::json($pets);
    } else {
        echo pg_last_error($db);
    }


});

Flight::start();

?>