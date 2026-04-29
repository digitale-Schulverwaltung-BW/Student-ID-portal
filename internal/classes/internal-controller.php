<?php
require_once('config.php');
require_once('pass.php');
require_once('student.php');

class InternalController
{
    // route handler for verify. Returns JSON array with student name+birthday
    function verify($f3, $args): void
    {
        $id=(isset($args['id']))?$args['id']:"";
        $isTeacher = ($_GET['type'] ?? '') === 'teacher';
        $stud=new CStudent($id, $isTeacher);
        if (($stud->getID() !== "")/* && (!$stud->isBlacklisted())*/){
            // valid student ID
            $resp = array("id" => $stud->getID(), 
                          "lastname" => $stud->getLastname(), 
                          "firstname" => $stud->getFirstname(), 
                          "birthday" => $stud->getBirthday());
        } else {
            // invalid student ID
            sleep(invalid_wait); // rate limiting to keep brute forcing ID attempts at bay
            $resp = array("id" => 0, "lastname" => "", "firstname" => "");
        }
        header("Content-Type: application/json");
        echo json_encode($resp);
    }

    // route handler for deploy. Will create a pass if not already registered
    // or retrieve the remote download URLs for existing passes. Returns JSON
    // array with passID and the download URLs.
    function deploy($f3, $args): void
    {
        $id=(isset($args['id']))?$args['id']:"";
        $isTeacher = ($_GET['type'] ?? '') === 'teacher';
        $stud = new CStudent($id, $isTeacher);
        if ($stud->isBlacklisted()) {
            // Note: this error string is displayed verbatim to the end user via exit_with_error().
            // Keep it user-friendly and free of any sensitive system details.
            $data = array("error" => "Fehler: bereits Papierausweis ausgestellt, kein digitaler Ausweis möglich.");
            header("Content-Type: application/json");
            echo json_encode($data);
            return;
        }
        if ($stud->getID()=="") return;
        $isTeacher = ($_GET['type'] ?? '') === 'teacher';
        $pass   = new CStudentPass($stud, $isTeacher);
        $passID = $pass->getPassID();
        if (!empty($passID))
        {
            $data = array("id" => $passID,
                           "apple" => $pass->getAppleURL(),
                           "google" => $pass->getGoogleURL());
            header("Content-Type: application/json");
            echo json_encode($data);
        }
        else {
            header("Content-Type: application/json");
            echo "";
        }
    }

    // route handler for Apple pass download proxy.
    // Proxies .pkpass downloads from the WalletStudentID API.
    // Accepts the wallet API path after /apple/, e.g.:
    //   /apple/v1/passes/{id}/apple.pkpass?token=...
    // Reconstructs the full wallet URL and fetches the binary.
    function applePass($f3, $args): void
    {
        $path = $args['*'] ?? '';
        // Validate path matches expected pattern to prevent SSRF/path traversal
        if (!preg_match('#^v1/passes/[0-9a-f\-]+/apple\.pkpass$#i', $path)) return;

        // Reconstruct the wallet API URL from the configured base host
        $parsed = parse_url(WALLET_API_BASE);
        $host   = $parsed['host'] . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
        $walletUrl = $parsed['scheme'] . '://' . $host . '/' . $path;
        $params = [];
        parse_str($_SERVER['QUERY_STRING'] ?? '', $params);
        if (isset($params['token'])) $walletUrl .= '?token=' . urlencode($params['token']);

        // Fetch the .pkpass binary (token is in the URL, no auth header needed)
        $result = \Web::instance()->request($walletUrl, [
            'method' => 'GET',
            'follow_redirects' => TRUE
        ]);

        if (!isset($result) || empty($result) || !isset($result['body']) || empty($result['body'])) {
            http_response_code(502);
            echo 'Fehler beim Abruf des Apple Passes.';
            return;
        }

        $statusLine = $result['headers'][0] ?? '';
        if (!preg_match('/\b2\d{2}\b/', $statusLine)) {
            http_response_code(502);
            echo 'Fehler beim Abruf des Apple Passes.';
            return;
        }

        header('Content-Type: application/vnd.apple.pkpass');
        header('Content-Disposition: attachment; filename="studentid.pkpass"');
        echo $result['body'];
    }

    // route handler for verify. Returns JSON array with student name+birthday
    function lookup($f3, $args): void
    {
        $login=(isset($args['login']))?$args['login']:"";
        $stud   = new CStudent("");
        $stud->lookupStudent($login);
        echo $stud->getID();
    }

    // route handler for email-based ID lookup. Returns plain text student ID.
    function emailLookup($f3, $args): void
    {
        $email=(isset($args['email']))?$args['email']:"";
        if (empty($email)) return;

        $id = $this->lookup_csv($email);
        if ($id === null) {
            http_response_code(404);
            return;
        }
        header('Content-Type: text/plain');
        echo $id;
    }

    private function lookup_csv($email) {
        $csv_path  = STUDENTS_CSV;
        $email_col = (int) CSV_EMAIL_COL;
        $id_col    = (int) CSV_ID;

        if (empty($csv_path) || !file_exists($csv_path) || !is_readable($csv_path)) {
            return null;
        }

        $handle = fopen($csv_path, 'r');
        if ($handle === false) {
            return null;
        }

        $email = strtolower(trim($email));

        while (($row = fgetcsv($handle, 0, ';', '"')) !== false) {
            if (!isset($row[$email_col]) || !isset($row[$id_col])) {
                continue;
            }
            if (strtolower(trim($row[$email_col])) === $email) {
                fclose($handle);
                return trim($row[$id_col]);
            }
        }

        fclose($handle);
        return null;
    }

}