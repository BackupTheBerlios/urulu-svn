<?PHP
/* Funktion registrieren */
$GLOBALS['XQDB_ALLOWED_FUNCTIONS'][] = "binLogin";

/* Rendert den URL */
function binLogin($name) {
  
  /* Variablen setzen */
  $_SESSION['username'] = $name;
  $_SESSION['roles'] = array();
  foreach (__executeSQL("get_role_by_user", array($name), true) as $role) {
    $_SESSION['roles'][] = $role['name'];
  }
  
  /* Leeren Array zurückgeben */
  return array();
}
?>