<?PHP
/*
   Kompiliert alle Module im modules Ordner
   URL: http://localhost/urulu/trunk/compile.php
 */
/* Virtual machine laden */ 
require_once(realpath(dirname(__FILE__)) . "/xqvm.php");
echo "Dateien einlesen ...<br />\n";

/* Jedes Dokument einlesen und kompilieren */
foreach (__ls_r(BASE_DIR . "modules/") as $file) {
  if (is_file($file) and substr($file, -4) == ".xsc") {
    echo "Kompiliere: '" . $file . "' ...<br />\n";
    $fp = fopen($file, "r");
    $source = fread($fp, filesize($file));
    fclose($fp);
    
    /* Sequenz mit den Werten erstellen */
    __compile(array(), array($GLOBALS["XQDB_Storage"]->registerItem(new AtomicValue($source, "xs:string"))), substr($file, 0, -4));
  }
}
?>