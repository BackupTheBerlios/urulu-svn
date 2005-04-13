<?PHP
/* Funktion registrieren */
$GLOBALS['XQDB_ALLOWED_FUNCTIONS'][] = "getCurrentUser";

/* Rendert den URL */
function getCurrentUser() {
  return array($GLOBALS['XQDB_Storage']->registerItem(new AtomicValue($_SESSION['username'], "xs:string")));
}
?>