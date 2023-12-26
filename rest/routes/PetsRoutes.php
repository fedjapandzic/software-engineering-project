<?php

Flight::route('GET /pets' , function(){
    Flight::json(Flight::petsService()->get_all());
});

Flight::route('GET /pets/@id' , function($id){
    Flight::json(Flight::petsService()->get_by_id($id));
});

Flight::route('GET /pets/@full_name' , function($name){
    Flight::json(Flight::petsService()->getOwnerByFullName($name));
});

Flight::route('POST /pets' , function(){
    $data = Flight::request()->data->getData();
    Flight::json(Flight::petsService()->add($data));
});

Flight::route('PUT /pets/@id' , function($id){
    $data = Flight::request()->data->getData();
    Flight::petsService()->update($id,$data);
    Flight::json(Flight::petsService()->get_by_id($id));
});

Flight::route('DELETE /pets/@id' , function($id){
    Flight::petsService()->delete($id);
    Flight::json(["message" => "deleted"]);
});

// Flight::route('GET /addToCart', function(){
//     echo 'nice';
//     // $pet_name = Flight::request()->data->pet_name;
//     // $cart_id = $_SESSION['cart_id'];
//     // Flight::petsService()->addPetToCart($pet_name,$cart_id);
// });
?>