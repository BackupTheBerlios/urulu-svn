<?PHP
/* Knoten mit atomarem Werte */
class AtomicValue
{
  /* Type des Knotens */
  var $type;

  /* Wert des Knotens */
  var $value;
  
  /* Konstruktor 
     mixed  $value: Wert des Knotens
     string $type: Type des Knotens
  */  
  function AtomicValue($value, $type = "xdt:untyped") {

    /* Variablen initialisieren */
    $this->value = $value;
    $this->type = $type;
  }
  
  /* Wert als String ausgeben */
  function toString() {
    return strval($this->value);
  }

  /* Wert als Boolscher Wert ausgeben */
  function toBoolean() {
    return $this->value != false;
  }

  /* Wert als XML ausgeben */
  function toXML() {
    return strval($this->value);
  }
}
?>