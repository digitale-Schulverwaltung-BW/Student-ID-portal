<?php
require_once('config.php');
require_once('student.php');
class ExternalController
{
    function v($f3, $args)
    {
        $stud = new CStudent($args['id']);
        $template=new Template;
        if (!$stud->is_valid()) {
            $f3->set('valid', 'false');
            $f3->set('validity', 'Ungültiger Ausweis!');
            $f3->set('color', 'red');
            $f3->set('card_ID', '0000');
            echo $template->render('templates/head.html');
            echo $template->render('templates/verify.html');
            echo $template->render('templates/foot.html');
            exit ();
        }
        $f3->set('valid', 'true');
        $f3->set('validity', 'Gültiger Ausweis!');
        $f3->set('color', 'green');
        $f3->set('card_ID', $stud->get_short_id());
        echo $template->render('templates/head.html');
        echo $template->render('templates/verify.html');
        echo $template->render('templates/foot.html');
                //echo "Gültiger Ausweis! Nachname: $data[lastname], Vorname: $data[firstname]";
    }
    function r($f3, $args)
    {
        
    }

}
?>