<?PHP
/*
   Installationsrutine für die XQDB
   URL: http://localhost/urulu/trunk/bytecode/setup/setup.php
 */
 
/* Festlegen, dass es sich um eine Installation handelt */ 
define("XQDB_Install", true);
 
/* Virtual machine laden */ 
require_once(realpath("../../xqvm.php"));

/* Alle Tabellen in der Datenbank erstellen */
__install();

/* Alle Module Kompilieren */
require_once(BASE_DIR . "compile.php");
?>