<?php

require_once('student.php');

class ExternalController
{
    protected Base $f3;
    protected Template $template;
    protected CStudent $stud;

    // constructor: save $f3 variable, create and save a template instance
    function __construct() {
        $f3 = Base::instance();
        $this->f3 = $f3;
        $this->template=new Template;
    } 

    // exit with error page if invalid student ID
    private function exit_if_invalid($stud): void
    {
        if (!$stud->is_valid()) {
            $this->f3->mset(array('valid'=>false, 'card_ID'=>$stud->get_short_id()));
            $this->f3->set('title', 'Ungültiger Schülerausweis');
            echo $this->template->render('templates/verify.html');
            exit ();
        }
    }
    // helper 
    private function is_valid($var): bool
    {
        if (isset($var) && $var!='null' &&
            !empty($var))
            return true;
        return false;
    }
    // display error message and quit
    private function exit_with_error($message): void
    {
        $this->f3->set('valid', true);
        $this->f3->set('deploy_error', $message);
        echo $this->template->render('templates/register.html');
        exit ();
    }

    // route handler for validate
    public function v(): void
    {
        $stud = new CStudent($this->f3->get('PARAMS.id'));
        $this->exit_if_invalid($stud);
        $this->f3->set('title', 'Gültiger Schülerausweis');
        $this->f3->mset(array('valid'=>true, 'card_ID'=>$stud->get_short_id(), 'school'=>'Heinrich-Hertz-Schule Karlsruhe',
                        'birthday'=>$stud->get_birthday(), 'fn'=>$stud->get_firstname(),'sn'=>$stud->get_lastname()));
        echo $this->template->render('templates/verify.html');
    }

    // route handler for registrations
    public function r(): void
    {
        $stud = new CStudent($this->f3->get('PARAMS.id'));
        if (!$stud->is_valid()) {
            $this->f3->set('valid', false);
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

    // route handler for POST requests to registration URL: submitted birthday
    public function authReg(): void
    {
        $stud = new CStudent($this->f3->get('PARAMS.id'));
        $bday_hash='';
        if (!$stud->is_valid()) {
            $this->f3->set('valid', false);
            echo $this->template->render('templates/register.html');
            exit ();
        }
        if (require_birthday) {
            $bday = str_pad($this->f3->get('POST.day'), 2, "0", STR_PAD_LEFT).".".
                    str_pad($this->f3->get('POST.month'), 2, "0", STR_PAD_LEFT).".".
                    $this->f3->get('POST.year');
            if ($stud->get_bday()!=$bday){
                $this->exit_with_error('Geburtsdatum nicht korrekt. Korrigieren Sie Ihre Eingabe und wenden Sie sich ggf. an unser Sekretariat.');
            }
            $bday_hash='/'.hash_hmac('sha256', $bday, 'bday-transfer');
        }
        $this->f3->set('valid', true);
        $this->f3->set('base', $_SERVER['REQUEST_URI'].$bday_hash);
        $this->f3->set('deploy_error', '');
        
        echo $this->template->render('templates/register.html');
    }

    // route handler to check birthday hash value before download redirects
    public function deployAuth(): void
    {
        $stud = new CStudent($this->f3->get('PARAMS.id'));
        // passing this hash as GET param is not the prettiest solution, but we want to keep this process session-less
        if ($this->f3->get('PARAMS.hash')==hash_hmac('sha256', $stud->get_bday(), 'bday-transfer'))
            $this->deploy_pass( $this->f3->get('PARAMS.id'), $this->f3->get('PARAMS.deploy'));
            $this->exit_with_error('Fehlende Authentifizierung. Bitte scannen Sie den QR-Code erneut.');
    }

    // route handler for download redirects
    public function deploy(): void
    {
        if (require_birthday) {
            $this->exit_with_error('Fehlende Authentifizierung. Bitte scannen Sie den QR-Code erneut.');            
        } else { 
            $this->deploy_pass($this->f3->get('PARAMS.id'), $this->f3->get('PARAMS.deploy'));
        }
    }

    // internal method to deploy download locations. Invoked from either deploy of deployAuth
    private function deploy_pass($id, $deploy): void
    {
        $stud = new CStudent($id);
        $this->f3->set('deploy_error', '');
        $this->exit_if_invalid($stud);
        $pass=$stud->register_pass();
        if (isset($pass) && !empty($pass)) $data=json_decode($pass, true);
        else $this->exit_with_error('Fehler beim Abruf der Ausweisdaten.');
        switch ($deploy) {
            case 'google':
                if ($this->is_valid($data['google']))
                    header ('Location: '.$data['google']);
                else $this->exit_with_error('Fehler beim Abruf der externen Wallet-Daten.');
                    exit();
                break;
            case 'apple':
                if ($this->is_valid($data['apple']))
                    header ('Location: '.$data['apple']);
                else $this->exit_with_error('Fehler beim Abruf der externen Wallet-Daten.');
                    exit();
                break;
            case 'pdf':
                if ($this->is_valid($data['pdf']))
                    header ('Location: '.$data['pdf']);
                else $this->exit_with_error('Fehler beim Abruf der externen Wallet-Daten.');                
                exit();
                break;
            default:
                // unknown deploy type        
                $this->exit_with_error('Fehler: Unbekannter Wallet-Typ oder Ausweis im gewählten Format nicht verfügbar.');
                break;
            }    
    }
}
?>