<?PHP
/* Elementknoten */
class Element 
{ 
  /* Name des Knotens */
  var $nodeName;
  
  /* Name des Knotens als Getypter Wert */
  var $typeName;
  
  /* String-Wert des Dokumentknotens */
  var $stringValue;
  
  /* Konstruktor 
     string $nodeName: Name des Knotens
     string $typeName: Type des Namens
     string $stringValue: String-Wert des Knotennamens
  */  
  function Element($nodeName, $typeName = "xdt:untyped", $stringValue = "") {

    /* Variablen initialisieren */
    $this->nodeName = $nodeName;
    $this->typeName = $typeName;
    $this->stringValue = $stringValue;
  }
  
  /* Wert des Text-Knotens als String ausgeben */
  function toString() {
    return strval($this->stringValue);
  }

  /* Wert des Text-Knotens als Boolscher Wert ausgeben */
  function toBoolean() {
    return $this->stringValue != false;
  }

  /* Wert des Text-Knotens als XML ausgeben */
  function toXML() {
    return $this->nodeName;
  }
}
?>