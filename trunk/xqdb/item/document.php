<?PHP
/* Dokumentknoten */
class Document 
{ 
  /* URI des Dokuments */
  var $documentURI;
  
  /* String-Wert des Dokumentknotens */
  var $stringValue;
  
  /* Type des Dokumentknoten */
  var $typeName;

  /* Wert des Dokumentknotens */
  var $typedValue;
  
  /* Konstruktor 
     string $documentURI: URI des Dokuments
     string $stringValue: String-Wert des Dokuments
     string $typeName: Type des Dokuments
     string $typedValue: Getypter Wert des Dokuments
  */  
  function Document($documentURI = null, $stringValue = "", $typeName = "xdt:untyped", $typedValue = "") {

    /* Variablen initialisieren */
    $this->documentURI = $documentURI;
    $this->stringValue =$stringValue;
    $this->typeName = $typeName;
    $this->typedValue = $typedValue;
  }

  /* Wert des Text-Knotens als String ausgeben */
  function toString() {
    return strval($this->stringValue);
  }

  /* Wert des Text-Knotens als Boolscher Wert ausgeben */
  function toBoolean() {
    return $this->typedValue != false;
  }

  /* Wert des Text-Knotens als XML ausgeben */
  function toXML() {
    return "";
  }
}
?>