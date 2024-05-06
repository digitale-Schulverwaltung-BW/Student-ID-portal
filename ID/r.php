<?php

require_once 'Twig/autoload.php';
require_once 'student.php';
$loader = new \Twig\Loader\FilesystemLoader('templates');
$options = array(
    'name' => 'HHS',
);
$twig = new \Twig\Environment($loader, $options);
if (isset($_REQUEST['id'])) $id=$_REQUEST['id']; else $id="";
$stud = new CStudent($id);
if (!$stud->is_valid()) {
    echo $twig->render('register.html.twig', array('valid' => FALSE));
    exit ();
}
if (isset($_REQUEST['deploy'])){
    switch ($_REQUEST['deploy']) {
    case 'google':
        // request Google wallet and forward
        echo "Google";
        break;
    case 'apple':
        // request Apple wallet and forward
        echo "Apple";
        break;
    case 'pdf':
        // request PDF wallet and forward
        echo "PDF";
        break;
    default:
        // unknown deploy type        
        echo $twig->render('register.html.twig', array('valid' => TRUE, 
                'base' => $_SERVER['REQUEST_URI'],
                'deploy_error'=>'Fehler: Unbekannter Wallet-Typ.'));
        break;
    }
} else {
    echo $twig->render('register.html.twig', array('valid' => TRUE, 'base' => $_SERVER['REQUEST_URI']));
}