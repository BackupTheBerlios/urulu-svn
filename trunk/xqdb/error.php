<?PHP
/* Fehlerbehandlungsrutine f�r Pear */
function __error_pear($error){
  echo "<pre>";
  var_dump($error);
  exit();
}

/* Fehlerbehandlungsrutine f�r php */
function __error_php(){
  exit();
}
?>