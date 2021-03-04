<?php

use DB\SQL as SQL;
use DB\SQL\Mapper as Mapper;

namespace App\Models;

class Model extends \DB\SQL\Mapper
{
    protected $f3;
    protected $database_instance;
    private $table_name = '';

    public function __construct($table_name)
    {
        $this->f3 = \Base::instance();
        $this->database_instance = $this->f3->get('database');
        $this->table_name = $table_name;
        
        parent::__construct($this->database_instance, $this->table_name);
    }
}
