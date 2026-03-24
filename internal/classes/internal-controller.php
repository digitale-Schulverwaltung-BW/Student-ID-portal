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
        $stud=new CStudent($id);
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
        $stud = new CStudent($id);
        if ($stud->isBlacklisted()) {
            $data = array("error" => "Fehler: bereits Papierausweis ausgestellt, kein digitaler Ausweis möglich.");
            header("Content-Type: application/json");
            echo json_encode($data);
            return;
        }
        if ($stud->getID()=="") return;
        $pass   = new CStudentPass($stud);
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
        if (empty($path)) return;

        // Reconstruct the wallet API URL from the configured base host
        $parsed = parse_url(WALLET_API_BASE);
        $walletUrl = $parsed['scheme'] . '://' . $parsed['host'] . '/' . $path;
        if (!empty($_SERVER['QUERY_STRING'])) {
            $walletUrl .= '?' . $_SERVER['QUERY_STRING'];
        }

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
        $stud.lookup($login);
        echo $stud->getID();
    }

}