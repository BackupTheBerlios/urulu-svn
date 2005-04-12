<?PHP

define("CODE_SEP", "\n");
define("IS_XSLT", true);
define("RUN_STATE", "debug");
define("BIN_DIR", realpath("../public_html") . "/");
define("BASE_DIR", realpath("../xqdb") . "/");

$GLOBALS['XQDB_SOURCE_DIRS'] = array(realpath("../cms") . "/", realpath("../etat") . "/");

$GLOBALS['XQDB_ALLOWED_VARIABLE'] = array("_POST", "_GET", "_SERVER");
$GLOBALS['XQDB_ALLOWED_FUNCTIONS'] = array("trim", "header");

$GLOBALS['XQDB_DNS'] = array(
  'phptype'  => "mysql",
  'username' => "root",
  'password' => "",
  'hostspec' => 'localhost',
  'database' => 'xqdb'
);

$GLOBALS['XQDB_declFkts'] = array();
$GLOBALS['XQDB_Fkts'] = array(
  // XQuery Standard Funktionen
  'doc' => '__doc',
  'exists' => '__exists',
  'subsequence' => '__subsequence',
  'tokenize' => '__tokenize',
  'count' => '__count',
  'position' => '__position',
  
  // Spezifische PHP Funktionen
  'session' => "__session",
  'sql' => '__sql',
  'compile' => '__compile',
  'createDoc' => '__createDoc',
  'rollback' => '__rollback',
  'commit' => '__commit',
);
?>