<?PHP
/* Mehrfaches laden verhindern */
if (defined("XQDB_VM")) {
  PEAR::raiseError("Die Virtual Machine wurde bereits geladen!");
}
define("XQDB_VM", true);

/* Includepfad setzen */
if (defined("XQDB_Install") == false) {
  ini_set("include_path", realpath("../xqdb") . ";" . ini_get("include_path") . ";" . realpath("../pear"));
}
 
/* Pear laden */
require_once("PEAR.php");
require_once("DB.php");

/* Verschidene Pointer laden */
require_once("item/atomicvalue.php");
require_once("item/comment.php");
require_once("item/document.php");
require_once("item/element.php");
require_once("item/attribute.php");
require_once("item/pi.php");
require_once("item/text.php");

/* Virtual Machine laden */
require_once("config.php");
require_once("extlib.php");
require_once("stdlib.php");
require_once("cache.php");
require_once("storage.php");
require_once("queries.php");
require_once("error.php");
require_once("tokens.php");
require_once("functions.php");
require_once("output.php");

/* CMS Bibliothek laden */
require_once("cms/include.php");

/* Fehlerbehandlung festlegen */
PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, "__error_pear");
if (RUN_STATE == "productive") {
  set_error_handler("__error_php");
}

/* Datenbankobjekt erstellen */
$GLOBALS['XQDB_DB'] =& DB::connect($GLOBALS['XQDB_DNS']);
$GLOBALS['XQDB_DB']->setFetchMode(DB_FETCHMODE_ASSOC);
if (DB::isError($GLOBALS['XQDB_DB'])) {
  PEAR::raiseError($db->getMessage());
}

/* Benuztername und Passwort für DB Löschen */
unset($GLOBALS['XQDB_DNS']['username']);
unset($GLOBALS['XQDB_DNS']['password']);

/* Session erstellen */
session_start();
if (defined("XQDB_Install") == false) {
  checkSession();
}
?>