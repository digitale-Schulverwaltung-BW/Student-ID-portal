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
    echo $twig->render('page.html', array('validity' => 'Ungültiger Ausweis!',
                                            'valid' => FALSE,
                                            'color'    => 'red',
                                            'card_ID' => "0000"));
    exit ();
}
echo $twig->render('page.html', array('validity' => 'Gültiger Ausweis!',
                                        'valid' => TRUE,
                                        'color'    => 'green',
                                        'card_ID' => $stud->get_short_id()));
        //echo "Gültiger Ausweis! Nachname: $data[lastname], Vorname: $data[firstname]";

?>
