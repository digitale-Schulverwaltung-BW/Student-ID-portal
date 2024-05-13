<?php

require_once 'classes/external-controller.php';
$f3 = require('lib/base.php');
$f3->set('AUTOLOAD','classes/');
$f3->set('DEBUG',3);
$f3->route('GET /@action/@id','ExternalController->@action');
$f3->route('GET /@action/@id/@deploy','ExternalController->deploy');
$f3->run();
?>