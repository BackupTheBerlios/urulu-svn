<?PHP
/*
   Installationsrutine für die XQDB
   URL: http://localhost/urulu/trunk/bytecode/setup/setup.php
 */
 
/* Festlegen, dass es sich um eine Installation handelt */ 
define("XQDB_Install", true);
 
/* Virtual machine laden */ 
ini_set("include_path", realpath("../../xqdb") . ";" . ini_get("include_path") . ";" . realpath("../../pear"));
require_once(realpath("../../xqdb/xqvm.php"));
$GLOBALS['XQDB_SOURCE_DIRS'] = array(realpath("../../cms") . "/", realpath("../../etat") . "/");

/* Alle Tabellen in der Datenbank erstellen */
__install();

/* Alle Module Kompilieren */
require_once("../../xqdb/compile.php");
?>