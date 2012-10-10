<?php

class person_popup_params extends auto_construct {

    public $onsubmit;
    public $category_list;

    //------------------------------------------------------------------------
    function __construct(array $array_data, $prefix=null) {
        $this->_copy_properties($array_data, $prefix);
    }
}
