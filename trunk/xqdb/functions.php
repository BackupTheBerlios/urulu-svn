<?PHP
/* Erstellt die nötigen Tabellen für die Datenbank */
function __install() {
  __executeSQL("create_Documents");
  __executeSQL("creeate_user");
  __executeSQL("create_userrole");
  __executeSQL("create_role");
  __executeSQL("create_sessions");
}

/* Wrapper für die PEAR:DB
   string $query: Name der Query, die ausgeführt werden soll
   mixed[] $params: Parameter, die die Query noch braucht
   bool $return: Gibt an ob ausgeben als Array ausgegeben werden sollen
   mixed[] return: Ausgabe der Datenbankanfrage
 */
function &__executeSQL($query, $params = array(), $return = false) {
  if (count($params) != $GLOBALS['XQDB_Queries'][$GLOBALS['XQDB_DNS']['phptype']][$query][1]) {
  	PEAR::raiseError("Die Query '" . $query . "' hat nicht die richtige Parameteranzahl");
  }
  $queryParts = explode('?', $GLOBALS['XQDB_Queries'][$GLOBALS['XQDB_DNS']['phptype']][$query][0]);
  $realQuery = $queryParts[0];
  for ($loop = 0; $loop < count($queryParts) - 1; $loop++) {
  	$realQuery .= $GLOBALS['XQDB_DB']->escapeSimple($params[$loop]) . $queryParts[$loop + 1];
  }
  $rs =& $GLOBALS['XQDB_DB']->query($realQuery);
  if (DB::isError($rs)) {
    PEAR::raiseError("Datenbankfehler: " . $rs->getMessage());
  } elseif ($return) {
    $result = array();
    $tupel  = null;
    while ($rs->fetchInto($tupel)) {
      $result[] = $tupel;
    }
    $rs->free();
    return $result;
  }
  return array();
}

/* Sucht alle unterordner und Dateien
   string $dir: Pfad des Ordner, dessen Unterverzeichnisse aufgelistet werden sollen
   string[] return: Liste mit allen Dateien
 */
function __ls_r($path) {

  /* Dateien Suchen */
  $files = array();
  $dir = dir($path);
  while (false !== ($file = $dir->read())) {
    if ($file != "." and $file != ".." and $file != ".svn") {
      $files[] = $file;
    }
  }
  $dir->close();
  
  /* Liste erstellen */
  $list = array();
  foreach ($files as $file) {
    if (is_dir($path . $file)) {
      $list = array_merge($list, __ls_r($path . $file . "/"), array($path . $file));
    } else {
      $list[] = $path . $file;
    }
  }
  return array_merge($list, array($path));
}

/* Überprüft ob die Session noch gültig ist */
function checkSession() {

    /* Hash erstellen */
    $hash = md5(uniqid(microtime() + rand()));
    $newSession = false;

    /* Überprüfen ob bereits eine Session existiert */
    if (isset($_COOKIE['xqdbSessHash']) == false) {
      $newSession = true;
    } else {
      __executeSQL("session_update", array($hash, session_id(), $_COOKIE['xqdbSessHash']));
      if ($GLOBALS['XQDB_DB']->affectedRows() == 0) {
        __executeSQL("session_delete", array(session_id()));
        $newSession = true;
      }
    }
    
    /* Cookie setzen */
	  setcookie("xqdbSessHash", $hash, 0, "/");
	  
	  /* Neues Session erstellen, falls nötig */
    if ($newSession == true) {
      __executeSQL("session_insert", array(session_id(), $hash));    	 
      $_SESSION['username'] = "guest";
      $_SESSION['roles'] = array("GUEST");
      $_SESSION['variables'] = array();
    }
  }
?>