<?php
require_once('config.php');
require_once('runner.php');

class CAdminController extends Runner{
    protected $db;
    protected Base $f3;

    function main($f3, $args): void
    {
        /*

        CREATE TABLE users ('user_id' VARCHAR, 'password' VARCHAR);
        INSERT INTO users VALUES ('admin', '123ausweis');
 
        */
        if (!isset($this->f3)) $this->f3=$f3;
        $this->db=new DB\SQL('sqlite:'.PASS_DB);
        $user = new \DB\SQL\Mapper($this->db, 'users');
        //$auth = new \Auth($user, array('id'=>'user_id', 'pw'=>'password'));
        //$auth->basic(); // a network login prompt will display to authenticate the user

        $template=new Template;
        $results = $this->db->exec('SELECT COUNT(passID) FROM passes');
        $f3->set('numPasses', $results[0]['COUNT(passID)'] );
        $f3->set('message', '');
        $action=(isset($args['action']))?$args['action']:"";

        $results = $this->db->exec('SELECT studID, passID FROM passes');
        if (empty($results)){
            $f3->set('message', "keine Ausweise in Datenbank");
        } else {
            $msg='';
            $deleted=0;
            $errors=0;
            switch ($action) {
                case 'check':
                    foreach ($results as $pass) {
                        $stud=new CStudent($pass['studID']);
                        if ($stud->getID()=="") {
                            $stud->lookupFormerStudent($pass['studID']);
                            if ($stud->getID()!="") $details=$stud->getLastname().", ".$stud->getFirstname().", ".$stud->getClass();
                            else $details="nicht in Schülerdaten";
                            $msg .= "to delete: $pass[studID] ($details)/$pass[passID]".PHP_EOL;
                                $deleted++;
                        }
                    }
                    $msg.="$deleted Ausweise zu löschen";                    
                    $f3->set('message', $msg);
                    break;
                case 'cleanup':
                    $this->startRun();
                    $msg='Lösche ausgetretene Passes.';
                    $this->sendMessage($msg);
                    
                    foreach ($results as $pass) {
                        $stud=new CStudent($pass['studID']);
                        if ($stud->getID()=="") {
                            if ($this->deletePass($pass['studID'], $pass['passID']))
                                $deleted++;
                            else
                                $errors++;
                            $msg .= ".";
                            $this->sendMessage($msg);
                        }
                    }
                    $msg.="$deleted Ausweise gelöscht.";
                    if ($errors>0) $msg.=", $errors Fehler beim Löschen";
                    $this->endRun($msg);
                    return;
                    break;
                case 'renew':
                    $results = $this->db->exec('SELECT studID, passID FROM passes WHERE valid<>"'.$this->validShort().'"');
                    if (empty($results)){
                        $msg="keine zu verlängernden Ausweise in Datenbank";
                    } else {      
                        $msg=$this->updatePasses($results);                  
                    }
                    $f3->set('message', $msg);
                    break;
                case 'update':
                    $sid=$f3->get('POST.searchID');
                    $stud=new CStudent($sid);
                    if ($stud->getID()=="") $stud->lookupFormerStudent($sid);
                    if ($stud->getID()=="") 
                        $msg="Schüler ID nicht gefunden $sid";

                    $results=$this->db->exec('SELECT studID, passID FROM passes WHERE studID="'.$stud->getID().'"');
                    if (empty($results)){
                        $msg="Ausweis in Datenbank nicht gefunden";
                    } else {      
                        $msg=$this->updatePasses($results);                  
                    }
                    $f3->set('message', $msg);
                    break;
                case 'double':
                    $delete=1;
                case 'doubleList':
                    if (!isset($delete)) $delete=0;
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
                            if ($delete && $this->deletePass($pass['studID'], $pass['passID']))
                                $deleted++;
                            else
                                $errors++;
                        }
                    }
                    $msg.="insg.: ".$count;
                    if ($delete) $msg.=", $deleted gelöscht, $errors Fehler beim Löschen";
                    $f3->set('message', $msg);
                    break;
                case 'expunge':
                    $expunge=1;
                case 'expungeList':
                    $msg='';
                    if (!isset($expunge)) { $expunge=0; $msg='';}
                    $this->startRun();
                    $this->sendMessage("Hole gelöschte Passes...");                    
                    
                    $deleted=$this->getDeleted();
                    $del=count($deleted['list']);
                    $count=0;
                    $this->sendMessage("Deleted in Kortpress: $del");                    
                    foreach ($deleted['list'] as $pass) {
                            if ($expunge) $this->deletePass($pass['thirdPartyId'], $pass['serialNumber']);
                            if ($expunge) $this->sendMessage("deleted ($count/$del): $pass[serialNumber] ($pass[thirdPartyId])");
                            else {
                                $stud=new CStudent($pass['thirdPartyId']);
                                if ($stud->getID()=="") $stud->lookupFormerStudent($pass['thirdPartyId']);
                                if ($stud->getID()!="") $details=$stud->getLastname().", ".$stud->getFirstname().", ".$stud->getClass();
                                else $details="nicht in Schülerdaten";
                                $msg.="to delete ($count/$del): $pass[serialNumber] ($pass[thirdPartyId])(".$details.")\n";
                            }

                            $count++;
                        }
                    if (!$expunge) $msg.= "";
                    $this->endRun($msg);
                    return;
                    break;
                case 'checkDBstart':
                    $this->startRun();
                    $this->sendMessage("Starte Abgleich...");
                    $IDs=$this->getIDs();
                    $lines = explode(PHP_EOL, $IDs); $count=0;
                    $linenum=count($lines);
                    array_shift($lines); // remove header line
                    $logger = new Log('admin.log');

                    foreach ($lines as $pass) {
                        $this->sendMessage("$count/$linenum");
                        $count++;
                        if (empty($pass)) continue;
                        $data = str_getcsv($pass, ";");
                        if (count($data)<2) continue;
                        $passID=$data[0];
                        $studID=$data[1];
                        $stud=new CStudent($studID);
                        $p=$stud->getPassID();
                        if ($p!=$passID)
                            if (!empty($p)) {
                                $logger->write($studID." hat PassID $p statt $passID. Datenbank wird korrigiert.");                                
                                $pass=new CStudentPass($stud);
                                $pass->setPassID($passID);
                            } else {
                                $logger->write($studID." hat Kortpress-Pass, fehlt in DB, wird ergänzt mit $passID.");
                                $pass=new CStudentPass($stud);
                                $pass->refetchPass($passID);
                            }
                    }
                    $this->endRun("IDs upgedated: $linenum.");
                    return;
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
                        $pass=new CStudentPass($stud);
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
        $logger = new Log('admin.log');
        // erase third party ID from Kortpress to prevent problems with duplicate IDs
        $url='https://cloud.kortpress.io/rest/v1/pass?templateId='.KORTPRESS_TEMPLATE_ID.'&serialNumber='.$pass;
        $this->f3->mset(array('id'=>'00000000-00000000-0000-00000000-0000',
                        'pass_name'=>'Deleted',
                        'short_id'=>'0000',
                        'valid_short'=>'0/2000',
                        'valid_long'=>'2000-01-01T12:00:00.118',
                        'verify_base'=> 'https://www.example.com/v/',
                        'school'=>'Deleted School',
                        'school_url'=>'https://www.example.com',
                        'img_base_url'=>IMG_BASE_URL,
                        'birthday'=> '01.01.2000', 
                        'firstname'=> 'Detlev', 
                        'lastname'=> 'Deleted',
                        'apple' => 0,
                        'google' => 0,
                        'pdf' => 0
                    ));
        $template=new Template;
        $postdata=$template->render('templates/pass.txt', 'application/json');
        $options = array(
            'method' => 'POST', 'follow_redirects' => TRUE, 'content' => $postdata,
            'header' => [ 'Content-Type: application/json', 'Authorization: Bearer '. KORTPRESS_TOKEN
            ]
        );
        $result = \Web::instance()->request($url, $options);
        if (!isset($result) || empty($result) || !isset($result['body'])) {
            $logger->write("ERROR overwriting ID result: $result, postdata: $postdata");            
        }

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
            $logger->write("ERROR deleting pass: ".print_r($result, true));
            return false;
        } else {
            // delete from DB
            $this->db->exec('DELETE FROM "passes" WHERE studID="'.$stud.'"');
            $logger->write("Deleted pass: ".print_r($result, true));
        }
        return true;
    }

    function getIDs(): String 
    {
        $url='https://cloud.kortpress.io/rest/v1/pass/csv?templateId='.KORTPRESS_TEMPLATE_ID.'&passItemCSV='.urlencode('"serialNumber";"thirdPartyIdentifier"'); 
        $options = array( 'method' => 'GET', 'follow_redirects' => TRUE,
                            'timeout' => 12000, // change request timeout, takes loooOoOoonnng
                            'header' => [
                            'Content-Type: application/json', 'Authorization: Bearer '. KORTPRESS_TOKEN
                        ]
        );
        $result = \Web::instance()->request($url, $options);
        if (!isset($result) || empty($result) || (!isset($result['body'])))
        {
            $logger = new Log('update.log');
            $logger->write("ERROR getting DB list: ".print_r($result, true));
            return "";
        }
        $logger = new Log('update.log');
        $logger->write("Read ID list");
        return $result['body'];
    }

    function getDeleted(): array
    {
        $url='https://cloud.kortpress.io/rest/v1/pass?templateId='.KORTPRESS_TEMPLATE_ID.'&isActive=false&includeVoided=false&onlyVoided=false';
        $options = array( 'method' => 'GET', 'follow_redirects' => TRUE,
                    'header' => [
                    'Content-Type: application/json', 'Authorization: Bearer '. KORTPRESS_TOKEN
                ]
        );
        $logger = new Log('update.log');
        $logger->write("Getting deleted list: ".print_r($url, true));
        $result = \Web::instance()->request($url, $options);
        if (!isset($result) || empty($result) || (!isset($result['body'])))
        {
            $logger->write("ERROR getting deleted list: ".print_r($result, true));
            return [];
        }
        return json_decode($result['body'], true);

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
            VERIFY_BASE_URL.$stud->getID().'";"'.$stud->getLogin().'"'.PHP_EOL;
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