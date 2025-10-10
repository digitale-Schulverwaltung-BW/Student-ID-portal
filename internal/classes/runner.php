<?php
require_once('config.php');
require_once('utility.php');

class Runner extends Utility {
    public function startRun() {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        set_time_limit(0);
        while (ob_get_level() > 0) ob_end_flush();
    }
    public function sendMessage($msg) {
        $data=json_encode(['done' => 0,'msg' => $msg]);
        echo "data: $data\n\n";
        flush();
    }
    public function endRun($msg="") {
        $data=json_encode(['done' => 1, 'msg' => $msg]);
        echo "data: $data\n\n";
        flush();
    }
}
?>