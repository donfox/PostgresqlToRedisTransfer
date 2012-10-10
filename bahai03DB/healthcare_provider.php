<?php

class healthcare_provider {
    public $healthcare_prov_id;
    public $person;
    public $person_id;
    public $npid;
    public $professional_type;
    public $practice_group;


    public function __construct(
$full_name, $npid,
            $phone, $email, $professional_type, $practice_group) {

        $this->full_name = $full_name;
        $this->phone = $phone;
        $this->email = $email;
        $this->npid = $npid;
        $this->professional_type = $professional_type;
        $this->practice_group = $practice_group;
    }


    public function __toString() {
        return $this->full_name;
    }


    static public function format_fields($prefix, $obj, $key_onchange) {

        if (!$obj) 
            $obj = new healthcare_provider('', '', '', '', '', '');

        $fmt_str = <<<HTML
<table>
<tr>
  <td colspan='4'>
    <input type='hidden' name='{$prefix}hp_changed'/>

    <label for='{$prefix}full_name' class='field_header'>Full Name</label>
    <br>
    <input maxlength='60' size='40' name='{$prefix}full_name'
      onchange="this.form.{$prefix}hp_changed.value = 'yes'; {$key_onchange}"
      value='%s'
    />
  </td>
</tr>

<tr>
  <td>
    <label for='{$prefix}phone' class='field_header'>Phone</label>
    <br>
    <input maxlength='15' size='12' name='{$prefix}phone'
      onchange="this.form.{$prefix}hp_changed.value = 'yes';"
      value='%s'
    />
  </td>

  <td>&nbsp;&nbsp;</td>

  <td>
    <label for='{$prefix}email' class='field_header'>Email</label>
    <br>
    <input maxlength='30' size='20' name='{$prefix}email'
      onchange="this.form.{$prefix}hp_changed.value = 'yes';"
      value='%s'
    />
  </td>

</tr>

<tr>
  <td>
    <label for='{$prefix}npid' class='field_header'>NPID</label>
    <br>
    <input maxlength='15' size='10' name='{$prefix}npid'
      onchange="this.form.{$prefix}hp_changed.value = 'yes';"
      value='%s'
    />
  </td>

  <td>&nbsp;&nbsp;</td>

  <td>
    <label for='{$prefix}professional_type'
      class='field_header'>Professional Type</label>
    <br>
    <input maxlength='30' size='20' name='{$prefix}professional_type'
      onchange="this.form.{$prefix}hp_changed.value = 'yes';"
      value='%s'
    />
  </td>

  <td>&nbsp;&nbsp;</td>

  <td>
    <label for='{$prefix}practice_group'
      class='field_header'>Practice Group</label>
    <br>
    <input maxlength='30' size='20' name='{$prefix}practice_group'
      onchange="this.form.{$prefix}hp_changed.value = 'yes';"
      value='%s'
    />
  </td>

</tr>

</table>

HTML;

        $html = sprintf($fmt_str, 
            htmlspecialchars($obj->full_name, ENT_QUOTES),
            htmlspecialchars($obj->phone, ENT_QUOTES),
            htmlspecialchars($obj->email, ENT_QUOTES),
            htmlspecialchars($obj->npid, ENT_QUOTES),
            htmlspecialchars($obj->professional_type, ENT_QUOTES),
            htmlspecialchars($obj->practice_group, ENT_QUOTES)
        );

        return $html;
    }

};

?>
