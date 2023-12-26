<?php
require_once __DIR__. '/BaseService.class.php';
require_once __DIR__ . '/../dao/PetsDao.class.php';

class PetsService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new PetsDao);
    }

    function getPetByName($name)
    {
        return $this->dao->getPetByName($name);
    }
    
}

?>