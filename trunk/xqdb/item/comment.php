<?PHP
/* Kommentarknoten */
class Comment 
{ 
  /* Wert des Kommentars */
  var $content;
  
  /* Konstruktor 
     string $content: Wert des Kommentars
  */  
  function Comment($content) {

    /* Variablen initialisieren */
    $this->content = $content;
  }
  
  /* Wert des Kommentars als String ausgeben */
  function toString() {
    return strval($this->content);
  }

  /* Wert des Kommentars als Boolscher Wert ausgeben */
  function toBoolean() {
    return $this->content != false;
  }

  /* Wert des Kommentars als XML ausgeben */
  function toXML() {
    return "<!-- " . strval($this->content) . "-->";
  }
}
?>