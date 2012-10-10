<?php
class event_type {

    static private $optgroups = array('BH','FT','SC');

    static private $raw_data = array(
       array( 1, "AM", "Assembly Meeting", ""),
       array( 2, "AP", "Awards Presentation", ""),
       array( 3, "BH", "Ascension of 'Abdul-Baha", ""),
       array( 4, "BH", "Ascension of Baha'u'llah", ""),
       array( 5, "BH", "Ayyam-i-ha or Intercalary Days", ""),
       array( 6, "BH", "Birth of Baha'u'llah", ""),
       array( 7, "BH", "Birth of the Bab", ""),
       array( 8, "BH", "Day of the Covenant", ""),
       array( 9, "BH", "Declaration of the Bab", ""),
       array(10, "BH", "Festival of Ridvan", ""),
       array(11, "BH", "Martyrdom of the Bab", ""),
       array(12, "BH", "Naw Ruz", ""),
       array(13, "CC", "Childrens Class", ""),
       array(14, "CM", "Committee Meeting", ""),
       array(15, "CV", "Convention", ""),
       array(16, "DP", "Deeping", ""),
       array(17, "DV", "Devotional", ""),
       array(18, "EM", "Email", ""),
       array(19, "FS", "Fireside", ""),
       array(20, "FT", "Feast", "Baha - Splendour - March 21", ""),
       array(21, "FT", "Feast", "Jalal - Glory - April 9", ""),
       array(22, "FT", "Feast", "Jamal - Beauty - April 28", ""),
       array(23, "FT", "Feast", "'Azamat - Grandeur - May 17", ""),
       array(24, "FT", "Feast", "Nur - Light - June 5", ""),
       array(25, "FT", "Feast", "Rahmat - Mercy - June 24", ""),
       array(26, "FT", "Feast", "Kalimat - Words - July 13", ""),
       array(27, "FT", "Feast", "Kamal - Perfection - August 1", ""),
       array(28, "FT", "Feast", "Asma' - Names - August 20", ""),
       array(29, "FT", "Feast", "'Izzat - Might - September 8", ""),
       array(30, "FT", "Feast", "Mashiyyat - Will - September 27", ""),
       array(31, "FT", "Feast", "'Ilm - Knowledge - October 16", ""),
       array(32, "FT", "Feast", "Qudrat - Power - November 4", ""),
       array(33, "FT", "Feast", "Qawl - Speech - November 23", ""),
       array(34, "FT", "Feast", "Masail - Questions - December 12", ""),
       array(35, "FT", "Feast", "Sharaf - Honour - December 31", ""),
       array(36, "FT", "Feast", "Sultan - Sovereignty - January 19", ""),
       array(37, "FT", "Feast", "Mulk - Dominion - February 7", ""),
       array(38, "FT", "Feast", "'Ala - Loftiness - March 2", ""),
       array(39, "HV", "Home Visit", ""),
       array(40, "PC", "Phone Call", ""),
       array(41, "RG", "Reflections Gathering", ""),
       array(42, "RU", "Race Unity Day", ""),
       array(43, "SC", "Study Circle", ""),
       array(44, "SC", "Study Circle", "Ruhi Book 1"),
       array(45, "SC", "Study Circle", "Ruhi Book 2"),
       array(46, "SC", "Study Circle", "Ruhi Book 3"),
       array(47, "SC", "Study Circle", "Ruhi Book 3A"),
       array(48, "SC", "Study Circle", "Ruhi Book 4"),
       array(50, "SC", "Study Circle", "Ruhi Book 5"),
       array(51, "SC", "Study Circle", "Ruhi Book 6"),
       array(52, "SC", "Study Circle", "Ruhi Book 7"),
       array(53, "TF", "Task Force Meeting", ""),
       array(54, "WR", "World Religion Day", ""),
       array(55, "O", "Other", "")
       );


    // Database is assumed to be open before calling this.
    static public function populate_database() {
        foreach (self::$raw_data as $row) {
            $query = sprintf("INSERT into event_type(%d,'%s','%s','%s');",
                    $row[0],
                    pg_escape_string($row[1]),
                    pg_escape_string($row[2]),
                    pg_escape_string($row[3]),
                    pg_escape_string($row[4]) 
                    );
            pg_query($query);
        }
    }


    static public function format_selector($fld_name, $selected_value) {

        $in_optgroup = false;
        $html = "<SELECT name='$fld_name'>\n";

        foreach (self::$raw_data as $row) {
            if ($in_optgroup) {
                if (!in_array($row[1], self::$optgroups)) {
                    $in_optgroup = false;
                    $html .= "</OPTGROUP>\n";
                }
            }
            else if (in_array($row[1], self::$optgroups)) {
                $in_optgroup = true;
                $html .= "<OPTGROUP label='{$row[1]}:'>\n";
            }

            $label = $in_optgroup ? '' : "{$row[1]}: ";
            $label .= $row[2];
            if ($row[3]) {
                $label .= " ($row[3])";
            }

            $html .= sprintf("<OPTION value='%s' label='%s' %s>%s</OPTION>\n",
                $row[0], 
                addcslashes($label, "'"),
                (($row[0] == $selected_value) ? 'SELECTED' : ''),
                htmlspecialchars($label)
                );
        }

        if ($in_optgroup) {
            $html .= "</OPTGROUP>\n";
        }
 
        $html .= "</SELECT>\n";

        return $html;
    }

}
