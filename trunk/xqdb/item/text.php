<?PHP
/* Textknoten */
class Text 
{ 
  /* Wert des Textes */
  var $content;
  
  /* Konstruktor 
     string $content: Wert des Text-Knotens
  */  
  function Text($content) {

    /* Variablen initialisieren */
    $this->content = $content;
  }
  
  /* Wert des Text-Knotens als String ausgeben */
  function toString() {
    return strval($this->content);
  }

  /* Wert des Text-Knotens als Boolscher Wert ausgeben */
  function toBoolean() {
    return $this->content != false;
  }

  /* Wert des Text-Knotens als XML ausgeben */
  function toXML() {
    return strval($this->content);
  }
}
?>