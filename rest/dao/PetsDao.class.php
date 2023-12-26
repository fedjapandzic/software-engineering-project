<?php
require_once __DIR__. '/BaseDao.class.php';

class PetsDao extends BaseDao { 
    
  public function __construct(){
    parent::__construct("pets");
  }

  function getPetByName($name)
  {
    return $this->query_unique("SELECT * FROM pets WHERE name = :name", ["name" =>$name]);
    
  }

}

?>