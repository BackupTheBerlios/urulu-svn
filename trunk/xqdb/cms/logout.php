<?PHP
/* Funktion registrieren */
$GLOBALS['XQDB_ALLOWED_FUNCTIONS'][] = "logout";

/* Rendert den URL */
function logout() {
  
  /* Variablen setzen */
  $_SESSION['username'] = "guest";
  $_SESSION['roles'] = array("GUEST");
  
  /* Leeren Array zurückgeben */
  return array();
}
?>