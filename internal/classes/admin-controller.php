<?php
require_once('config.php');
require_once('utility.php');

class CAdminController extends Utility{
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
                    $msg='';
                    $deleted=0;
                    $errors=0;
                    foreach ($results as $pass) {
                        $stud=new CStudent($pass['studID']);
                        if ($stud->getID()=="") {
                            $msg .= "deleted: $pass[studID]".PHP_EOL;
                            if ($this->deletePass($pass['studID'], $pass['passID']))
                                $deleted++;
                            else
                                $errors++;
                        }
                    }
                    $msg.="$deleted Ausweise gelöscht";
                    if ($errors>0) $msg.=", $errors Fehler beim Löschen";
                    $f3->set('message', $msg);
                    break;
                case 'renew':
                    $results = $this->db->exec('SELECT studID, passID FROM passes WHERE valid<>'.$this->validShort());
                    if (empty($results)){
                        $msg="keine zu verlängernden Ausweise in Datenbank";
                    } else {      
                        $msg=$this->updatePasses($results);                  
                    }
                    $f3->set('message', $msg);
                    break;
                case 'update':
                    $sid=$f3->get('POST.searchID');
                    $results=$this->db->exec('SELECT studID, passID FROM passes WHERE studID="'.$sid.'"');
                    if (empty($results)){
                        $msg="Ausweis in Datenbank nicht gefunden";
                    } else {      
                        $msg=$this->updatePasses($results);                  
                    }
                    $f3->set('message', $msg);
                    break;
                case 'double':
                    $msg="Doppelt ausgestellte zu löschen: ".PHP_EOL;
                    $count=0;
                    $deleted=0;
                    $errors=0;
                    foreach ($results as $pass ) {
                        $double='';
                        $stud=new CStudent($pass['studID']);
                        $double=$this->getLineWithString(BLACKLIST, $pass['studID']);
                        if ($double) {
                            $dbl=explode(';', $double);
                            $msg=$msg.trim($dbl[1]).'->'.$dbl[0].PHP_EOL; //$pass['studID'];
                            $count++;
                            if ($this->deletePass($pass['studID'], $pass['passID']))
                                $deleted++;
                            else
                                $errors++;
                        }
                    }
                    $msg.="insg.: ".$count.", $deleted gelöscht, $errors Fehler beim Löschen";
                    $f3->set('message', $msg);
                    break;
                case 'reissue':
                    $sid=$f3->get('POST.searchID');
                    $stud=new CStudent($sid);
                    if ($stud->getID()=="") $stud->lookupFormerStudent($sid);
                    if ($stud->getID()=="") {
                        $f3->set('message', "Schüler ID nicht gefunden $sid");
                    } else {
                        if ($stud->reissuePass())
                             $f3->set('message', "Ausweis neu erstellt. Schüler muss im Wallet ggf. aktualisieren.");
                        else $f3->set('message', "Fehler beim Pass-Update. Log-Dateien überprüfen!");
                    }
                    header ('Location: '.$f3->get('BASE').'/admin/'); // Bug: template render does not work here? Redirect as workaround
                    break;
                case 'reissue-all':
                    $msg=".";
                    $results = $this->db->exec('SELECT studID, passID FROM passes WHERE valid!="09/2025" AND created>"2024-09-01" LIMIT 100');
                    foreach ($results as $pass) {
                        $stud=new CStudent($pass['studID']);
                        if ($stud->getID()!="") {
                            if ($stud->reissuePass()) { 
                                $msg.=$pass['studID'].PHP_EOL; 
                                $this->db->exec('UPDATE passes SET valid="'.$this->validShort().'" WHERE studID="'.$pass['studID'].'"');
                            }
                            else 
                            {
                                $msg.="Error: $pass[studID]".PHP_EOL;
                            }
                        }
                    }
                    $f3->set('message', $msg);
                    break;
                case 'delete':
                    $sid=$f3->get('POST.dsearchID');
                    if (!isset($sid) || empty($sid))
                    {
                        $f3->set('message', 'ID darf nicht leer sein.');
                        break;
                    }
                    $stud=new CStudent($sid);
                    if ($stud->getID()=="") $stud->lookupFormerStudent($sid);
                    if ($stud->getID()=="") {
                        $f3->set('message', "Schüler ID $sid nicht gefunden");
                    } else {
                        $pass=new CStudentPass($f3, $stud);
                        if ($this->deletePass($stud->getID(), $pass->getPassID()))
                             $f3->set('message', "Ausweis gelöscht.");
                        else $f3->set('message', "Fehler beim Pass-Update. Log-Dateien überprüfen!");
                    }
                    //header ('Location: '.$f3->get('BASE').'/admin/'); // Bug: template render does not work here? Redirect as workaround
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
            return false;
        } else {
            // delete from DB
            $this->db->exec('DELETE FROM "passes" WHERE studID="'.$stud.'"');
            $logger = new Log('admin.log');
            $logger->write("Deleted pass: ".print_r($result, true));
        }
        return true;
    }

    function getExpiryDates(): String // deprecation warning: this method times out when retrieving a large amount of passes.
    {
        $url='https://cloud.kortpress.io/rest/v1/pass/csv?templateId='.KORTPRESS_TEMPLATE_ID.'&passItemCSV='.urlencode('"serialNumber";"thirdPartyIdentifier";"expirationDate";"object.balance-value";"backField2-value"');
        $options = array( 'method' => 'GET', 'follow_redirects' => TRUE,
                            'header' => [
                            'Content-Type: application/json', 'Authorization: Bearer '. KORTPRESS_TOKEN
                        ]
        );
        $result = \Web::instance()->request($url, $options);
        if (!isset($result) || empty($result) || (!isset($result['body'])))
        {
            $logger = new Log('update.log');
            $logger->write("ERROR getting expiry date list: ".print_r($result, true));
            return "";
        }
        $logger = new Log('update.log');
        $logger->write("Update status: ".print_r($result, true));
        return $result['body'];
    }

    function getExpiryDate($pass): String
    {
        $url='https://cloud.kortpress.io/rest/v1/pass/csv?templateId='.KORTPRESS_TEMPLATE_ID.'&serialNumber='.$pass['passID'].
             '&passItemCSV='.urlencode('"serialNumber";"thirdPartyIdentifier";"expirationDate";"object.balance-value";"backField2-value"');
        $options = array( 'method' => 'GET', 'follow_redirects' => TRUE,
                            'header' => [
                            'Content-Type: application/json', 'Authorization: Bearer '. KORTPRESS_TOKEN
                        ]
        );
        $result = \Web::instance()->request($url, $options);
        if (!isset($result) || empty($result) || (!isset($result['body'])))
        {
            $logger = new Log('update.log');
            $logger->write("ERROR getting expiry date for single pass: ".print_r($result, true));
            return "";
        }
        $logger = new Log('update.log');
        $logger->write("Update status: ".print_r($result, true));
        return $result['body'];

    }
    function updatePasses($passes): String
    {
        $msg="";
        $csv='"thirdPartyIdentifier";"backField2-value";"object.balance-value";"expirationDate";"class.accountName-value";"class.accountId-value";"class.rewardsTier-value";"class.programName-value";"barcode1-message";"name"'.PHP_EOL;
        $renewed=0;

        foreach ($passes as $pass)
        {
            $stud=new CStudent($pass['studID']);
            if ($stud->getID()=="") continue;                        
            $renewed++;
            $csv.='"'.$pass['studID'].'";"'.$this->validShort().'";"'.$this->validShort().'";"'.$this->validLong().'";"'.
            $stud->getFirstname().' '.$stud->getLastname().'";"'.$stud->getBirthday().'";"'.
            substr($stud->getID(),-4).'";"'.$stud->getFirstname().' '.$stud->getLastname().'";"'.
            VERIFY_BASE_URL.$stud->getID().'";"'.$stud->getID().'"'.PHP_EOL;
            $msg.="erneuert: $pass[studID]".PHP_EOL;
            $this->db->exec('UPDATE passes SET valid="'.$this->validShort().'" WHERE studID="'.$pass['studID'].'"');
        }

        $url='https://cloud.kortpress.io/rest/v1/pass/csv/upload?templateId='.KORTPRESS_TEMPLATE_ID;
        $options = array( 'method' => 'POST', 'follow_redirects' => TRUE,
                    'header' => [
                    'Content-Type: text/csv', 'Authorization: Bearer '. KORTPRESS_TOKEN,
                    ],
                    'content' => $csv
        );
        $result = \Web::instance()->request($url, $options);
        if (!isset($result) || empty($result) || (!isset($result['body'])))
        $msg='- es sind Fehler aufgetreten, bitte das update.log prüfen.';
        $logger = new Log('update.log');
        $logger->write("CSV: ".PHP_EOL.$csv);
        $logger->write("Update status after POST: ".print_r($result, true));
        $msg="$renewed Ausweise verlängert ".PHP_EOL.PHP_EOL.$msg;
        
        return $msg;
    }
}
?>