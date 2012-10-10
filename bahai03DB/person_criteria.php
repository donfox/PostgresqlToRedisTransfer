<?php

class person_criteria extends auto_construct {

    public $category_list;
    public $partial_name;

    function __construct(array $array_data=null) {
       if (is_null($array_data))
           return;

       $this->_copy_properties($array_data);
    }

}
