<?php

class auto_construct {

    // Shared code to copy values from the
    protected function _copy_properties(
            array $array_data,  // an associative array with data
            $prefix=null,       // prefix that should be stripped off data keys
            $save_extras=false  // save fields not defined as properties
            ) {
        $rc = new ReflectionClass(get_class($this));
        foreach($array_data as $fld => $val) {
            try {
                $fld_name = $fld;
                if ($prefix) {
                    if (!preg_match("/^{$prefix}(.*)\$/", $fld, $matches))
                        continue;

                    $fld_name = $matches[1];
                }
                $p = $rc->getProperty($fld_name);
                $this->$fld_name = $array_data[$fld];
            }
            catch (ReflectionException $e) {
                if ($save_extras)
                    $this->extra_fields[$fld] = $array_data[$fld];
            }
        }
    }

}
