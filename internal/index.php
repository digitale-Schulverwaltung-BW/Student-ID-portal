<?php

require_once 'classes/internal-controller.php';
require_once 'classes/admin-controller.php';

$f3 = require('lib/base.php');

$f3->route('GET /verify/@id','InternalController->verify');
$f3->route('GET /register/@id','InternalController->deploy');

$f3->run();

?>