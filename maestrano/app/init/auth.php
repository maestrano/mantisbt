<?php
//-----------------------------------------------
// Define root folder and load base
//-----------------------------------------------
if (!defined('MAESTRANO_ROOT')) {
  define("MAESTRANO_ROOT", realpath(dirname(__FILE__) . '/../../'));
}
require_once MAESTRANO_ROOT . '/app/init/base.php';

//-----------------------------------------------
// Require your app specific files here
//-----------------------------------------------
if (!defined('APP_DIR')) {
	define('APP_DIR', realpath(MAESTRANO_ROOT . '/../'));
}
chdir(APP_DIR);
require_once APP_DIR . '/core.php';

//-----------------------------------------------
// Perform your custom preparation code
//-----------------------------------------------
// If you define the $opts variable then it will
// automatically be passed to the MnoSsoUser object
// for construction
// e.g:
// $opts = array();
// if (!empty($db_name) and !empty($db_user)) {
//     $conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
//     $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
//     
//     $opts['db_connection'] = $conn;
// }


