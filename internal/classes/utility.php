<?php

require_once('config.php');

class Utility{
    // generate validity date String (human readable)
    // adjust end date here
    function validShort(): String
    {
        $month=date("m");
        $year=date("Y");
        $m=(SCHOOLYEAR_START<10)?"0".SCHOOLYEAR_START:SCHOOLYEAR_START;
        if ($month>SCHOOLYEAR_START-1) return $m.'/'.$year+1;
        return $m."/$year";
    }

    // generate validity date String (machine readable).
    // adjust end date here
    function validLong(): String
    {
        $month=date("m");
        $year=date("Y");
        $m=(SCHOOLYEAR_START<10)?"0".SCHOOLYEAR_START:SCHOOLYEAR_START;
        if ($month>SCHOOLYEAR_START-1) return ($year+1)."-$m-30T12:00:00.000Z";
        return "$year-$m-30T12:00:00.000Z";
    }
    
    // convert DD.MM.YYYY to YYYY-MM-DD for WalletStudentID API
    function convertBirthdayToISO(String $birthday): String
    {
        $parts = explode('.', $birthday);
        if (count($parts) === 3) return sprintf('%s-%s-%s', $parts[2], $parts[1], $parts[0]);
        return $birthday;
    }

    // shared HTTP helper for WalletStudentID API calls
    function walletApiRequest(String $url, String $method = 'GET', ?String $content = null, int $timeout = 120): ?array
    {
        $options = [
            'method' => $method,
            'follow_redirects' => TRUE,
            'header' => ['Content-Type: application/json', 'Authorization: Bearer ' . WALLET_API_KEY]
        ];
        if ($content !== null) $options['content'] = $content;
        if ($timeout !== 120) $options['timeout'] = $timeout;
        $result = \Web::instance()->request($url, $options);
        if (!isset($result) || empty($result)) return null;

        // Log non-2xx responses for debugging
        $statusLine = $result['headers'][0] ?? '';
        if (!preg_match('/\b2\d{2}\b/', $statusLine)) {
            $logger = new \Log('api-error.log');
            $logger->write("$method $url => $statusLine");
            $logger->write("Response body: " . ($result['body'] ?? '(empty)'));
            if ($content !== null) $logger->write("Request body: $content");
        }

        // DELETE returns 204 No Content
        if ($method === 'DELETE') {
            return (strpos($statusLine, '204') !== false) ? [] : null;
        }
        if (!isset($result['body'])) return null;
        return json_decode($result['body'], true);
    }

    protected function getLineWithString($fileName, $str): String {
        $lines = file($fileName);
        foreach ($lines as $lineNumber => $line) {
            if (strpos($line, $str) !== false) {
                return $line;
            }
        }
        return "";
    }
}
?>