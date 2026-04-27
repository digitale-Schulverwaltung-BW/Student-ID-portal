<?php

require_once 'classes/external-controller.php';

$f3 = require('lib/base.php');

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' " . SCHOOL_HOMEPAGE . "; img-src 'self' " . SCHOOL_HOMEPAGE . " data:; frame-ancestors 'none'");

// change this only temporarily for debugging. Leave at 0 for production!
$f3->set('DEBUG', 0);

$f3->route('GET  /v/@id','ExternalController->v');
$f3->route('GET  /r/@id','ExternalController->r');
$f3->route('POST /r/@id','ExternalController->authReg');
$f3->route('GET  /r/@id/@deploy','ExternalController->deploy');
$f3->route('GET  /r/@id/@hash/@deploy','ExternalController->deployAuth');
$f3->route('GET  /apple/*','ExternalController->appleProxy');

$f3->run();
?>