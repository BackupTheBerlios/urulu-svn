<?PHP
/* Prozessor-Instruktion */
class PI 
{ 
  /* Name der Prozessorinstruktion */
  var $target;
  
  /* Konstruktor 
     string $target: Name der Prozessorinstruktion
  */  
  function PI($target) {

    /* Variablen initialisieren */
    $this->target = $target;
  }
  
  /* Wert des Text-Knotens als XML ausgeben */
  function toXML() {
    return $this->target;
  }
}
?>