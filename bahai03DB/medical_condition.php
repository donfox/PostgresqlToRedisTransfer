<?php

class medical_condition  extends auto_construct implements type_in_db {

    public $person_id;
    public $disease_condition;
    public $medicine;
    public $equipment;
    public $require_power;


    //-------------------------------------------------------------------
    function __construct(array $array_data) {
        parent::__init_props($array_data);
    }


    //-------------------------------------------------------------------
    function __toString() {
        return $this->disease_condition;
    }


    //-------------------------------------------------------------------
    static function type_long_name() {
        return "Medical Condition";
    }


    static function format_fields($prefix, $obj, $key_onchange) {

        if (!$obj) 
            $obj = new medical_condition('', '', '', '', '');

        $req_power = $obj->require_power ? 'checked' : '';

        $html = <<<HTML
<table>
<tr>
  <td colspan='4'>
    <input type='hidden' name='{$prefix}mc_changed'/>

    <label for='{$prefix}disease_condition'
      class='field_header'>Disease Condition</label>
    <br>
    <input type='text' maxlength='40' size='30'
      name='{$prefix}disease_condition'
      onchange="this.form.{$prefix}mc_changed.value = 'yes'; {$key_onchange}"
      value='{$obj->disease_condition}'
    />
  </td>
</tr>

<tr>
  <td>
    <label for='{$prefix}medicine' class='field_header'>Medicine</label>
    <br>
    <input type='text' maxlength='30' size='20'
      name='{$prefix}medicine'
      onchange="this.form.{$prefix}mc_changed.value = 'yes';"
      value='{$obj->medicine}'
    />
  </td>

  <td>&nbsp;&nbsp;</td>

  <td>
    <label for='{$prefix}equipment' class='field_header'>Equipment</label>
    <br>
    <input type='text' maxlength='30' size='20'
      name='{$prefix}equipment'
      onchange="this.form.{$prefix}mc_changed.value = 'yes';"
      value='{$obj->equipment}'
    />
  </td>
  <td>&nbsp;&nbsp;</td>

  <td>
    <label for='{$prefix}require_power'
      class='field_header'>Require Power?</label>
    <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <input type='checkbox'
      name='{$prefix}require_power' {$req_power}
      onchange="this.form.{$prefix}mc_changed.value = 'yes';"
    />
  </td>

</tr>

</table>

HTML;

        return $html;
    }


};

?>