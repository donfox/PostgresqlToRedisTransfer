<?php

class feedback {

    //-------------------------------------------------------------------
    static function process_post_data() {
        return self::leave_feedback($_POST['feedback_text']);
    }


    //-------------------------------------------------------------------
    static function leave_feedback($text) {
        $query = sprintf("SELECT leave_feedback('%s');", 
                pg_escape_string($text));

        app_session::pg_query($query);

        return new request(array('message'=>'Thank you for your feedback.'));
    }


    //-------------------------------------------------------------------
    static function gen_display() {

        $html = <<<FEEDBACK_HTML

<FORM name='feedback_form' METHOD='POST'>
<h2>Please leave feedback for the design team about this web site.</h2>
<p>
<input type='hidden' name='datatype' value='feedback'/>

<TEXTAREA name='feedback_text' rows='20' cols='80'>
</TEXTAREA>

<P>
<INPUT type='submit' value='Send Feedback'/>
</FORM>

FEEDBACK_HTML;
        
        return $html;
    }

}
