<?PHP
/* Attributeknoten */
class Attribute
{ 
  /* Name des Attributes */
  var $nodeName;

  /* Name des Types des Attributes */
  var $typeName;
    
  /* Zeichenketten-Wert des Attributes */
  var $stringValue;
  
  /* Getypter Wert des Attrubutes */
  var $typedValue;
  
  /* Konstruktor 
     string $nodeName: Name des Attributes
     string $typedName: Type des Attributes
     string $stringValue: String-Wert des Attributes
     string $typedValue: Getypter Wert des Attributes
  */  
  function Attribute($nodeName, $typeName, $stringValue, $typedValue) {

    /* Variablen initialisieren */
    $this->nodeName = $nodeName;
    $this->typeName = $typeName;
    $this->stringValue = $stringValue;
    $this->typedValue = $typedValue;
  }
  
  /* Wert des Attribut-Knotens als String ausgeben */
  function toString() {
    return $this->stringValue;
  }

  /* Wert des Attribut-Knotens als Boolscher Wert ausgeben */
  function toBoolean() {
    return $this->typedValue != false;
  }

  /* Wert des Attribut-Knotens als XML ausgeben */
  function toXML() {
    return $this->nodeName . "=\"" . $this->stringValue . "\"";
  }
}
?>