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
                    $this->sendMessage("Hole deaktivierte Passes...");

                    $deleted=$this->getDeleted();
                    $del=count($deleted);
                    $count=0;
                    $this->sendMessage("Deaktivierte Passes: $del");
                    foreach ($deleted as $pass) {
                            if ($expunge) $this->deletePass($pass['external_id'], $pass['id']);
                            if ($expunge) $this->sendMessage("deleted ($count/$del): $pass[id] ($pass[external_id])");
                            else {
                                $stud=new CStudent($pass['external_id']);
                                if ($stud->getID()=="") $stud->lookupFormerStudent($pass['external_id']);
                                if ($stud->getID()!="") $details=$stud->getLastname().", ".$stud->getFirstname().", ".$stud->getClass();
                                else $details="nicht in Schülerdaten";
                                $msg.="to delete ($count/$del): $pass[id] ($pass[external_id])(".$details.")\n";
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
                    $passes=$this->getIDs();
                    $count=0;
                    $total=count($passes);
                    $logger = new Log('admin.log');

                    foreach ($passes as $passData) {
                        $this->sendMessage("$count/$total");
                        $count++;
                        $passID=$passData['id'];
                        $studID=$passData['external_id'];
                        $stud=new CStudent($studID);
                        $p=$stud->getPassID();
                        if ($p!=$passID)
                            if (!empty($p)) {
                                $logger->write($studID." hat PassID $p statt $passID. Datenbank wird korrigiert.");
                                $pass=new CStudentPass($stud);
                                $pass->setPassID($passID);
                            } else {
                                $logger->write($studID." hat API-Pass, fehlt in DB, wird ergänzt mit $passID.");
                                $pass=new CStudentPass($stud);
                                $pass->refetchPass($passID);
                            }
                    }
                    $this->endRun("IDs upgedated: $total.");
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
        $result = $this->walletApiRequest(WALLET_API_BASE . '/passes/' . $pass, 'DELETE');
        if ($result === null)
        {
            $logger->write("ERROR deleting pass: $pass");
            return false;
        }
        // delete from DB
        $this->db->exec('DELETE FROM "passes" WHERE studID="'.$stud.'"');
        $logger->write("Deleted pass: $pass (student: $stud)");
        return true;
    }

    function getIDs(): array
    {
        // TODO: requires GET /v1/passes list endpoint in WalletStudentID
        // For now, fetch all passes with pagination
        $allPasses = [];
        $offset = 0;
        $limit = 100;
        $logger = new Log('update.log');

        do {
            $data = $this->walletApiRequest(
                WALLET_API_BASE . '/passes?limit=' . $limit . '&offset=' . $offset,
                'GET', null, 12000
            );
            if ($data === null) {
                $logger->write("ERROR getting pass list at offset $offset");
                break;
            }
            $items = isset($data['items']) ? $data['items'] : $data;
            $allPasses = array_merge($allPasses, $items);
            $offset += $limit;
            // stop if we got fewer items than the limit (last page)
        } while (count($items) >= $limit);

        $logger->write("Read ID list: " . count($allPasses) . " passes");
        return $allPasses;
    }

    function getDeleted(): array
    {
        // TODO: requires GET /v1/passes?status=voided endpoint in WalletStudentID
        $logger = new Log('update.log');
        $logger->write("Getting voided passes list");
        $data = $this->walletApiRequest(WALLET_API_BASE . '/passes?status=voided');
        if ($data === null)
        {
            $logger->write("ERROR getting voided passes list");
            return [];
        }
        return isset($data['items']) ? $data['items'] : $data;
    }

    function updatePasses($passes): String
    {
        $msg="";
        $items = [];
        $renewed=0;

        foreach ($passes as $pass)
        {
            $stud=new CStudent($pass['studID']);
            if ($stud->getID()=="") continue;
            $renewed++;
            $items[] = [
                'id' => $pass['passID'],
                'student' => [
                    'first_name' => $stud->getFirstname(),
                    'last_name' => $stud->getLastname(),
                    'student_shortcode' => substr($stud->getID(), -4),
                    'birthdate' => $this->convertBirthdayToISO($stud->getBirthday()),
                    'valid_from' => date('c'),
                    'valid_until' => $this->validLong(),
                    'school_name' => SCHOOL,
                    'school_url' => SCHOOL_URL,
                    'verification_url' => VERIFY_BASE_URL . $stud->getID()
                ]
            ];
            $msg.="erneuert: $pass[studID]".PHP_EOL;
            $this->db->exec('UPDATE passes SET valid="'.$this->validShort().'" WHERE studID="'.$pass['studID'].'"');
        }

        $postdata = json_encode(['items' => $items]);
        $data = $this->walletApiRequest(WALLET_API_BASE . '/passes/bulk', 'PATCH', $postdata);
        $logger = new Log('update.log');
        if ($data === null) {
            $msg='- es sind Fehler aufgetreten, bitte das update.log prüfen.';
            $logger->write("ERROR bulk update failed");
        } else {
            $jobId = isset($data['jobId']) ? $data['jobId'] : 'unknown';
            $logger->write("Bulk update submitted, jobId: $jobId");
        }
        $logger->write("Update: $renewed passes submitted");
        $msg="$renewed Ausweise verlängert ".PHP_EOL.PHP_EOL.$msg;

        return $msg;
    }
}
?>
