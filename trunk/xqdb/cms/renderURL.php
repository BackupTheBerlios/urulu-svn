<?PHP
/* Funktion registrieren */
$GLOBALS['XQDB_ALLOWED_FUNCTIONS'][] = "renderURL";

/* Rendert den URL */
function renderURL() {

  // PathInfo suchen
  if (php_sapi_name() != "cgi"){
    if (getenv("PATH_INFO")) {
      $_PATH_INFO = getenv("PATH_INFO");
    } else {
      $_PATH_INFO = getenv("ORIG_PATH_INFO");
    }
    
  } else {
    $_substrScriptUrl = substr($_SERVER["SCRIPT_URL"], strlen($_SERVER['SCRIPT_NAME']));
    if ($_substrScriptUrl !== FALSE) {
      $_PATH_INFO = $_substrScriptUrl;
    }
  }
  
  /* PathInfo auswerten */
  if (isset($_PATH_INFO)) {
    
    /* Prüft, ob Extension angegeben wurden */
    if (strpos($_PATH_INFO,'.') !== false) {
      
      /* Seite und Extention suchen */
      $sequence = array($GLOBALS['XQDB_Storage']->registerItem(
          new AtomicValue(substr($_PATH_INFO, 1, strpos($_PATH_INFO, '.') - 1), "xs:string")));
      $extension = substr($_PATH_INFO, strpos($_PATH_INFO, '.') + 1);
      
      /* falls hinter der Extension noch weitere shlasches kommen */
      if ($subExt = strstr($extension,'/')) {
        $sequence[] = $GLOBALS['XQDB_Storage']->registerItem(new AtomicValue(substr($extension, 0, strpos($extension, '/')), "xs:string"));
        $subExtSplit = explode("/", substr($subExt, 1));
        foreach ($subExtSplit as $key => $value){
          if ($key % 2 == 0) { 
            $sequence[] = $parent = $GLOBALS['XQDB_Storage']->registerItem(new Element($key));
          } else {
            $GLOBALS['XQDB_Storage']->insertItem($GLOBALS['XQDB_Storage']->registerItem(new Text($value)), $parent);
          }
        }
      } else {
        $sequence[] = $GLOBALS['XQDB_Storage']->registerItem(new AtomicValue($extension, "xs:string"));
      }
      
      return $sequence;
    
    /* Es wurde keine extention angegeben */
    } else {
      return array(
        $GLOBALS['XQDB_Storage']->registerItem(new AtomicValue(substr($_PATH_INFO, 1), "xs:string")),
        $GLOBALS['XQDB_Storage']->registerItem(new AtomicValue("html", "xs:string"))
      );
    }

  /* Es wurden keine Pathinfos erstellt PathInfo */
  } else {
    return array(
      $GLOBALS['XQDB_Storage']->registerItem(new AtomicValue("home", "xs:string")),
      $GLOBALS['XQDB_Storage']->registerItem(new AtomicValue("html", "xs:string"))
    );
  }
}
?>