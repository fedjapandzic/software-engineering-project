<?php


Flight::route('/', function(){
    include './html/register.html';
});

Flight::route('/homeRoute', function(){
    if(isset($_SESSION['full_name']) && $_SESSION['is_verified'] == 1){
        include '../html/home.html';
    }
    else {
        Flight::redirect('/login');
    }
});

Flight::route('/login', function(){
    global $failed_attempts;
    include '../html/login.html';
});

Flight::route('/checkyouremail' , function(){
    include '../html/checkverification.html';
});

Flight::route('/changePassword', function(){
    include '../html/changepass.html';
});

Flight::route('/UserVerified', function(){
    echo '<script>alert("You have been verified!")</script>';
    global $failed_attempts;
    include '../html/login.html';
});

Flight::route('/twofactorauthenticator', function(){
    if (isset($_SESSION['phone_number'])) {
        include '../html/twofactorauth.html';
    } else {
        // Redirect to login or handle the case where phone_number is not set.
        Flight::redirect('/login');
    }

});

Flight::route('/forgotpassword',function(){
    include '../html/forgotpassword.html';
});

Flight::route('/logout', function(){
    session_unset();
    if(!isset($_SESSION['phone_number']) && !isset($_SESSION['full_name'])){
        Flight::redirect('/login');
    }
});

?>