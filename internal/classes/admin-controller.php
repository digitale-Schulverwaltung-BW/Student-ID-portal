<?php
require_once('config.php');


class CAdminController {
    protected $db;
    protected Base $f3;

    function main($f3, $args): void
    {
        /*

        CREATE TABLE users ('user_id' VARCHAR, 'password' VARCHAR);
        INSERT INTO users VALUES ('admin', '123ausweis');
 
        */
        $this->db=new DB\SQL('sqlite:'.PASS_DB);
        $user = new \DB\SQL\Mapper($this->db, 'users');
        $auth = new \Auth($user, array('id'=>'user_id', 'pw'=>'password'));
        $auth->basic(); // a network login prompt will display to authenticate the user

        $template=new Template;
        $results = $this->db->exec('SELECT COUNT(passID) FROM passes');
        $f3->set('numPasses', $results[0]['COUNT(passID)'] );
        $f3->set('message', '');
        $action=(isset($args['action']))?$args['action']:"";

        $results = $this->db->exec('SELECT studID, passID FROM passes');
        if (empty($results)){
            $f3->set('message', "keine Ausweise in Datenbank");
        } else {
            switch ($action) {
                case 'check':
                    $deleted=0;
                    $errors=0;
                    foreach ($results as $pass ) {
                        $stud=new CStudent($pass['studID']);
                        if ($stud->getID()=="") {
                            if ($this->deletePass($pass['studID'], $pass['passID']))
                                $deleted++;
                            else
                                $errors++;
                        }
                    }
                    $msg="$deleted Ausweise gelöscht";
                    if ($errors>0) $msg.=", $errors Fehler beim Löschen";
                    $f3->set('message', $msg);
                    break;
                case 'renew':
                    $renewed=0;
                    foreach ($results as $pass ) {
                        $stud=new CStudent($pass['studID']);
                        if ($stud->getID()!=="") {
                            $renewed++;
                            // update in Kortpress
                        }
                    }
                    $f3->set('message', "$renewed Ausweise verlängert");
                    break;
                }
            }
        echo $template->render('templates/admin.html');
    }

    function deletePass(String $stud, String $pass): bool
    {
        // delete from Kortpress
        $url='https://cloud.kortpress.io/rest/v1/pass/'.$pass;
        $options = array( 'method' => 'DELETE', 'follow_redirects' => TRUE,
                            'header' => [
                            'Content-Type: application/json', 'Authorization: Bearer '. KORTPRESS_TOKEN
                        ]
        );
        $result = \Web::instance()->request($url, $options);
        if (!isset($result) || empty($result) || (!isset($result['body'])))
        {
            $logger = new Log('admin.log');
            $logger->write("ERROR deleting pass: ".print_r($result, true));
            $errors++;
            return false;
        } else {
            // delete from DB
            $this->db->exec('DELETE FROM "passes" WHERE studID="'.$stud.'"');
        }
        return true;
    }

    function getExpiryDates(): array
    {
        
    }
}
?>