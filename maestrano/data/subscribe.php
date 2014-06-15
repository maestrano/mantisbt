<?php

//-----------------------------------------------
// Define root folder
//-----------------------------------------------
if (!defined('MAESTRANO_ROOT')) {
  define("MAESTRANO_ROOT", realpath(dirname(__FILE__) . '/../'));
}

require_once(MAESTRANO_ROOT . '/app/init/soa.php');

$maestrano = MaestranoService::getInstance();

if ($maestrano->isSoaEnabled() and $maestrano->getSoaUrl()) {
    $notification = json_decode(file_get_contents('php://input'), false);
    
    $mno_entity = new MnoSoaEntity();
    $mno_entity->process_notification($notification);
}

?>