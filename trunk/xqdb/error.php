<?PHP
/* Fehlerbehandlungsrutine für Pear */
function __error_pear($error){
  echo "<pre>";
  var_dump($error);
  exit();
}

/* Fehlerbehandlungsrutine für php */
function __error_php(){
  exit();
}
?>