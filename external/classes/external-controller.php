<?php
require_once('config.php');
require_once('student.php');
class ExternalController
{
    function check($f3, $stud)
    {
        if (!$stud->is_valid()) {
            $f3->set('valid', 'false');
            $f3->set('validity', 'Ungültiger Ausweis!');
            $f3->set('color', 'red');
            $f3->set('card_ID', '0000');
            echo $template->render('templates/verify.html');
            exit ();
        }

    }
    function v($f3, $args)
    {
        $stud = new CStudent($args['id']);
        $template=new Template;
        $this->check($f3, $stud);
        $f3->set('valid', 'true');
        $f3->set('validity', 'Gültiger Ausweis!');
        $f3->set('color', 'green');
        $f3->set('card_ID', $stud->get_short_id());
        echo $template->render('templates/verify.html');
                //echo "Gültiger Ausweis! Nachname: $data[lastname], Vorname: $data[firstname]";
    }
    function r($f3, $args)
    {
        $stud = new CStudent($args['id']);
        $template=new Template;
        if (!$stud->is_valid()) {
            $f3->set('valid', 'false');
            echo $template->render('templates/register.html');
            exit ();
        }
        $f3->set('valid', 'true');
        $f3->set('base', $_SERVER['REQUEST_URI']);
        $f3->set('deploy_error', '');
        
        echo $template->render('templates/register.html');
    }
    function deploy($f3, $args)
    {
        $stud = new CStudent($args['id']);
        $template=new Template;
        $f3->set('deploy_error', '');
        $this->check($f3, $stud);
        switch ($args['deploy']) {
            case 'google':
                // request Google wallet and forward
                echo "Google";
                break;
            case 'apple':
                // request Apple wallet and forward
                //$data = json_decode($stud->register_pass('apple'));
                //header ('Location: '.$data['apple']);
                //exit();
                break;
            case 'pdf':
                // request PDF wallet and forward
                echo "PDF";
                break;
            default:
                // unknown deploy type        
                $f3->set('valid', 'true');
                $f3->set('deploy_error', 'Fehler: Unbekannter Wallet-Typ.');
                echo $template->render('templates/head.html');
                echo $template->render('templates/register.html');
                echo $template->render('templates/foot.html');
                break;
            }        
    }
}
?>