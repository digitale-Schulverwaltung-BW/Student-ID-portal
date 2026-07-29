<?php
require_once('config.php');
require_once('utility.php');

class CAdminController extends Utility{
    protected Base $f3;

    private function ipInCidr(string $ip, string $cidr): bool {
        if (!str_contains($cidr, '/')) $cidr .= '/32';
        [$subnet, $bits] = explode('/', $cidr, 2);
        $bits       = (int)$bits;
        $ipLong     = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) return false;
        if ($bits === 0) return true;
        $mask = -1 << (32 - $bits);
        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    // Looks up an ID as a (current or former) student first, then as a teacher.
    private function findStudent(string $sid): CStudent {
        $stud = new CStudent($sid);
        if ($stud->getID() === "") $stud->lookupFormerStudent($sid);
        if ($stud->getID() === "" && defined('TEACHERS_CSV')) {
            $teacher = new CStudent($sid, true);
            if ($teacher->getID() !== "") return $teacher;
        }
        return $stud;
    }

    private function checkAdminAccess(): void {
        $remote = $_SERVER['REMOTE_ADDR'] ?? '';

        $blockList = defined('ADMIN_BLOCK_IP') ? array_filter(array_map('trim', explode(',', ADMIN_BLOCK_IP))) : [];
        foreach ($blockList as $cidr) {
            if ($this->ipInCidr($remote, $cidr)) {
                http_response_code(403);
                exit('Zugriff verweigert.');
            }
        }

        $allowList = defined('ADMIN_CIDR') ? array_filter(array_map('trim', explode(',', ADMIN_CIDR))) : [];
        if (!empty($allowList)) {
            foreach ($allowList as $cidr) {
                if ($this->ipInCidr($remote, $cidr)) return;
            }
            http_response_code(403);
            exit('Zugriff verweigert.');
        }
    }

    function main($f3, $args): void
    {
        $this->checkAdminAccess();
        if (!isset($this->f3)) $this->f3=$f3;

        $template=new Template;
        $f3->set('message', '');
        $action=(isset($args['action']))?$args['action']:"";

        $stats = $this->getWalletStats();
        $f3->set('stats', $stats);

        switch ($action) {
            case 'renew':
                $passes = $this->getIDs();
                if (empty($passes)) {
                    $msg="keine Ausweise gefunden";
                } else {
                    $msg=$this->updatePasses($passes);
                }
                $f3->set('message', $msg);
                break;
            case 'update':
                $sid=substr($f3->get('POST.searchID'), 0, 256);
                $stud=$this->findStudent($sid);
                if ($stud->getID()=="") {
                    $f3->set('message', "Schüler ID nicht gefunden");
                } else {
                    $passData = $this->walletApiRequest(
                        WALLET_API_BASE . '/passes/by-external-id/' . $stud->getID()
                    );
                    if ($passData === null || empty($passData['id'])) {
                        $f3->set('message', "Ausweis nicht gefunden");
                    } else {
                        $results = [['studID' => $stud->getID(), 'passID' => $passData['id']]];
                        $msg=$this->updatePasses($results);
                        $f3->set('message', $msg);
                    }
                }
                header ('Location: '.$f3->get('BASE').'/admin/');
                break;
            case 'reissue':
                $sid=substr($f3->get('POST.searchID'), 0, 256);
                $stud=$this->findStudent($sid);
                if ($stud->getID()=="") {
                    $f3->set('message', "Schüler ID nicht gefunden $sid");
                } else {
                    if ($stud->reissuePass())
                         $f3->set('message', "Ausweis neu erstellt. Schüler muss im Wallet ggf. aktualisieren.");
                    else $f3->set('message', "Fehler beim Pass-Update. Log-Dateien überprüfen!");
                }
                header ('Location: '.$f3->get('BASE').'/admin/');
                break;
            case 'delete':
                $sid=substr($f3->get('POST.dsearchID'), 0, 256);
                if (!isset($sid) || empty($sid))
                {
                    $f3->set('message', 'ID darf nicht leer sein.');
                    break;
                }
                $stud=$this->findStudent($sid);
                if ($stud->getID()=="") {
                    $f3->set('message', "Schüler ID $sid nicht gefunden");
                } else {
                    $pass=new CStudentPass($stud, $stud->isTeacher());
                    if ($this->deletePass($stud->getID(), $pass->getPassID()))
                         $f3->set('message', "Ausweis gelöscht.");
                    else $f3->set('message', "Fehler beim Pass-Update. Log-Dateien überprüfen!");
                }
                header ('Location: '.$f3->get('BASE').'/admin/');
                break;
        }
        echo $template->render('templates/admin.html');
    }

    function getWalletStats(): array
    {
        $stats = ['active' => '?', 'voided' => '?', 'themes' => '?'];

        // Get JWT token from admin API
        $parsed = parse_url(WALLET_API_BASE);
        $adminBase = $parsed['scheme'] . '://' . $parsed['host'] . '/admin/api';

        $loginResult = \Web::instance()->request($adminBase . '/auth/login', [
            'method' => 'POST',
            'header' => ['Content-Type: application/json'],
            'content' => json_encode(['key' => WALLET_API_KEY])
        ]);
        if (empty($loginResult['body'])) return $stats;
        $loginData = json_decode($loginResult['body'], true);
        if (empty($loginData['token'])) return $stats;
        $jwt = $loginData['token'];

        $authHeader = ['Authorization: Bearer ' . $jwt];

        // Active passes count
        $result = \Web::instance()->request($adminBase . '/passes?limit=1&status=active', [
            'method' => 'GET', 'header' => $authHeader
        ]);
        if (!empty($result['body'])) {
            $data = json_decode($result['body'], true);
            if (isset($data['total'])) $stats['active'] = $data['total'];
        }

        // Voided passes count
        $result = \Web::instance()->request($adminBase . '/passes?limit=1&status=voided', [
            'method' => 'GET', 'header' => $authHeader
        ]);
        if (!empty($result['body'])) {
            $data = json_decode($result['body'], true);
            if (isset($data['total'])) $stats['voided'] = $data['total'];
        }

        // Theme count via tenant API
        $themes = $this->walletApiRequest(WALLET_API_BASE . '/themes');
        if ($themes !== null && isset($themes['themes'])) {
            $stats['themes'] = count($themes['themes']);
        }

        return $stats;
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
        $logger->write("Deleted pass: $pass (student: $stud)");
        return true;
    }

    function getIDs(): array
    {
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
        } while (count($items) >= $limit);

        $logger->write("Read ID list: " . count($allPasses) . " passes");
        return $allPasses;
    }

    function updatePasses($passes): String
    {
        $msg="";
        $items = [];
        $renewed=0;

        foreach ($passes as $pass)
        {
            $studID = $pass['studID'] ?? $pass['external_id'] ?? '';
            $passID = $pass['passID'] ?? $pass['id'] ?? '';
            $stud=$this->findStudent($studID);
            if ($stud->getID()=="") continue;
            $renewed++;
            $items[] = [
                'id' => $passID,
                'student' => [
                    'first_name' => $stud->getFirstname(),
                    'last_name' => $stud->getLastname(),
                    'student_shortcode' => substr($stud->getID(), -4),
                    'birthdate' => $this->convertBirthdayToISO($stud->getBirthday()),
                    'valid_from' => date('c'),
                    'valid_until' => $stud->isTeacher()
                        ? $this->teacherValidLong($stud->getBirthday())
                        : $this->validLong(),
                    'school_name' => SCHOOL,
                    'school_url' => SCHOOL_URL,
                    'verification_url' => VERIFY_BASE_URL . $stud->getID()
                ]
            ];
            $msg.="erneuert: $studID".PHP_EOL;
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
