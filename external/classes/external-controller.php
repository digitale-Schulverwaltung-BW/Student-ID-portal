<?php
require_once('config.php');
require_once('student.php');

class ExternalController
{
    protected $f3;
    protected $template;
    protected $stud;

    function __construct() {
        $f3 = Base::instance();
        $this->f3 = $f3;
        $this->template=new Template;
    } 

    private function check($stud)
    {
        if (!$stud->is_valid()) {
            $this->f3->mset(array('valid'=>'false', 'color'=>'red', 'card_ID'=>$stud->get_short_id()));
            $this->f3->set('title', 'Ungültiger Schülerausweis');
            echo $this->template->render('templates/verify.html');
            exit ();
        }
    }

    function v()
    {
        $stud = new CStudent($this->f3->get('PARAMS.id'));
        $this->check($stud);
        $this->f3->set('title', 'Gültiger Schülerausweis');
        $this->f3->mset(array('valid'=>'true', 'color'=>'green', 'card_ID'=>$stud->get_short_id()));
        echo $this->template->render('templates/verify.html');
    }

    function r()
    {
        $stud = new CStudent($this->f3->get('PARAMS.id'));
        if (!$stud->is_valid()) {
            $this->f3->set('valid', 'false');
            echo $this->template->render('templates/register.html');
            exit ();
        }
        if (require_birthday) {
            $this->f3->set('auth', $_SERVER['REQUEST_URI']);
            $this->f3->set('shortid', $stud->get_short_id());
            echo $this->template->render('templates/register-auth.html');
            exit ();
        }
        // no auth requested, proceed:
        $this->authReg();
    }

    function authReg()
    {
        $stud = new CStudent($this->f3->get('PARAMS.id'));
        $bday_hash='';
        if (!$stud->is_valid()) {
            $this->f3->set('valid', 'false');
            echo $this->template->render('templates/register.html');
            exit ();
        }
        if (require_birthday) {
            $bday = str_pad($this->f3->get('POST.day'), 2, "0", STR_PAD_LEFT).".".
                    str_pad($this->f3->get('POST.month'), 2, "0", STR_PAD_LEFT).".".
                    $this->f3->get('POST.year');
            if ($stud->get_bday()!=$bday){
                $this->f3->set('valid', 'true');
                $this->f3->set('deploy_error', 'Geburtsdatum nicht korrekt. Korrigieren Sie Ihre Eingabe und wenden Sie sich ggf. an unser Sekretariat.');
                echo $this->template->render('templates/register.html');
            }
            $bday_hash='/'.hash_hmac('sha256', $bday, 'bday-transfer');
        }
        $this->f3->set('valid', 'true');
        $this->f3->set('base', $_SERVER['REQUEST_URI'].$bday_hash);
        $this->f3->set('deploy_error', '');
        
        echo $this->template->render('templates/register.html');
    }
    function deployAuth()
    {
        $stud = new CStudent($this->f3->get('PARAMS.id'));
        // passing this hash as GET param is not the prettiest solution, but we want to keep this process session-less
        if ($this->f3->get('PARAMS.hash')==hash_hmac('sha256', $stud->get_bday(), 'bday-transfer'))
            return $this->deploy_pass($this->f3, $this->f3->get('PARAMS.id'), $this->f3->get('PARAMS.deploy'));
        $this->f3->set('valid', 'true');
        $this->f3->set('deploy_error', 'Fehlende Authentifizierung. Bitte scannen Sie den QR-Code erneut.');
        echo $this->template->render('templates/register.html');
    }

    function deploy()
    {
        if (require_birthday) {
            $this->f3->set('valid', 'true');
            $this->f3->set('deploy_error', 'Fehlende Authentifizierung. Bitte scannen Sie den QR-Code erneut.');
            echo $this->template->render('templates/register.html');
            
        } else { 
            $this->deploy_pass($f3, $this->f3->get('PARAMS.id'), $this->f3->get('PARAMS.deploy'));
        }
    }

    function deploy_pass($f3, $id, $deploy)
    {
        $stud = new CStudent($id);
        $f3->set('deploy_error', '');
        $this->check($f3, $stud);
        $pass=$stud->register_pass();
        $data=json_decode($pass, true);
        switch ($deploy) {
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