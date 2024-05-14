<?php
require_once('config.php');
require_once('student.php');
class ExternalController
{
    protected $template;
    protected $stud;

    function __construct() {
        $this->template=new Template;
    } 

    private function check($f3, $stud)
    {
        if (!$stud->is_valid()) {
            $f3->mset(array('valid'=>'false', 'color'=>'red', 'card_ID'=>'0000'));
            echo $this->template->render('templates/verify.html');
            exit ();
        }
    }

    function v($f3, $args)
    {
        $stud = new CStudent($args['id']);        
        $this->check($f3, $stud);
        $f3->mset(array('valid'=>'true', 'color'=>'green', 'card_ID'=>$stud->get_short_id()));
        echo $this->template->render('templates/verify.html');
    }

    function r($f3, $args)
    {
        $stud = new CStudent($args['id']);
        if (!$stud->is_valid()) {
            $f3->set('valid', 'false');
            echo $this->template->render('templates/register.html');
            exit ();
        }
        $f3->set('valid', 'true');
        $f3->set('base', $_SERVER['REQUEST_URI']);
        $f3->set('deploy_error', '');
        
        echo $this->template->render('templates/register.html');
    }

    function deploy($f3, $args)
    {
        $stud = new CStudent($args['id']);
        $f3->set('deploy_error', '');
        $this->check($f3, $stud);
        $pass=$stud->register_pass();
        $data=json_decode($pass, true);
        switch ($args['deploy']) {
            case 'google':
                if (isset($data['google']) && $data['google']!='null')
                    header ('Location: '.$data['google']);
                exit();
                break;
            case 'apple':
                if (isset($data['apple']) && $data['apple']!='null')
                    header ('Location: '.$data['apple']);
                exit();
                break;
            case 'pdf':
                if (isset($data['pdf']) && $data['pdf']!='null')
                    header ('Location: '.$data['pdf']);
                exit();
                break;
            default:
                // unknown deploy type        
                $f3->set('valid', 'true');
                $f3->set('deploy_error', 'Fehler: Unbekannter Wallet-Typ oder Ausweis im gewählten Format nicht verfügbar.');
                echo $this->template->render('templates/register.html');
                break;
            }        
    }
}
?>