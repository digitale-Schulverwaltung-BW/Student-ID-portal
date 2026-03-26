<?php

require_once 'classes/internal-controller.php';
require_once 'classes/admin-controller.php';

ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', 0);

$f3 = require('lib/base.php');

$f3->route('GET /verify/@id','InternalController->verify');
$f3->route('GET /lookup/@login','InternalController->lookup');
$f3->route('GET /register/@id','InternalController->deploy');
$f3->route('GET /apple/*','InternalController->applePass');
$f3->route('GET /ID/@email','InternalController->emailLookup');
$f3->route('GET /admin', 'CAdminController->main');
$f3->route('GET /admin/@action', 'CAdminController->main');
$f3->route('POST /admin/@action', 'CAdminController->main');

//$f3->route('GET /progress', function($f3) {

$f3->run();

?>