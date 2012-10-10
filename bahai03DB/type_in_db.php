<?php

interface type_in_db {
    function insert_to_db();
    function update_in_db();
    static function delete_from_db($key);
    static function read_from_db($key);
}
