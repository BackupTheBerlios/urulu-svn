<?PHP

/* Repr�sentiert ein Objekt im Cache */
class Cache
{
  /* ID des Objekts */
  var $id;
  
  /* Type des Elements */
  var $type;
  
  /* Type des Speicherungsortes */
  var $storage;
  
  /* Name des Dokuments */
  var $uri;
  
  /* Werte der Achsen */
  var $axis;
  
  /* Objekt des Elements selbst */
  var $properties;
  
  /* Konstruktor der Klasse 
     string      $uri: URI des Knotens
     string[]    $axis: Achsen des Knotens
     AtomicValue $properties: Wert des Knotens
   */
  function Cache($id, $type, $storage = null, $uri = null, $axis = array(), $properties = null) {
    $this->id = $id;
    $this->type = $type;
    $this->storage = $storage;
    $this->uri = $uri;
    $this->axis = $axis;
    $this->properties = $properties;
  }
  
  /* Gibt den Speicherort des Dokuments zur�ck 
     string return: Speicherort des Dokuments
   */
  function storage() {
    return $this->storage;
  }
  
  /* Gibt den URI des Dokuments zur�ck 
     string return: URI des Dokuments
   */
  function uri() {
    return $this->uri;
  }
  
  /* Gibt den Type des Items zur�ck 
     string return: Type des Items
   */
  function type() {
    return $this->type;
  }
  
  /* Aktualisiert den Wert einer Achse
     string $type: Type der Achse
     string/string[] $newValue: neuer Wert der Achse
   */
  function updateAxis($type, $newValue) {
    $this->axis[$type] = $newValue;
  }
  
  /* Achse ausf�hren 
     string $axis: Name der Achse, die ausgef�hrt werden soll
     string[] return: Knoten, die in der Achse enthalten sind
   */
  function axis($axis) {
    
    /* Falls die Achse noch nicht im Cache ist */
    if (isset($this->axis[$axis]) == false) {
      
      /* Nur die Datenbankressource kann nachgeladen werden */
      if ($this->storage != "db") {
        PEAR::raiseError("Der Storage '" . $this->storage . "' wird nicht unterst�tzt!");
      }
      
      /* Achse auswerten */
      switch ($axis) {
        case "attribute":
        case "child":
        case "descendant":
          $this->axis[$axis] = array();
          foreach (__executeSQL("node_select_" . $axis, array($this->uri, $this->id), true) as $tupel) {
            $this->axis[$axis][] = $tupel[$axis];
            $GLOBALS['XQDB_Storage']->cacheItem($tupel[$axis], $tupel['type'], $this->storage, $this->uri);
          }
        break;
        case "parent":
          $this->properties();
        break;
        default:
          PEAR::raiseError("Die Achse '" . $axis . "' kann nicht verarbeitet werden!"); 
      }
    }
      
    /* Achse zur�ckgeben */
    return $this->axis[$axis];
  }
  
  /* Wert des Knotens �ndern
     AtomicValue $item: Neuer Wert des Knotens
   */
  function updateProperties($item) {
    $this->properties = $item;
  }
  
  /* Werte des Knotens zur�ckgeben 
     AtomicValue return: Werte des Knoten
  */
  function properties() {
    
    /* Falls das Item noch nicht im Cache ist */
    if ($this->properties == null) {

      /* Nur die Datenbankressource kann nachgeladen werden */
      if ($this->storage != "db") {
        PEAR::raiseError("Der Storage '" . $this->storage . "' wird nicht unterst�tzt!");
      }
      
      /* Objekt aus DB laden */
      $tupel = __executeSQL("node_select_node", array($this->uri, $this->type, $this->id), true);
      $this->axis['parent'] = $tupel[0]['parent'];
      switch ($this->type) {
        case "element": 
          $this->properties = new Element($tupel[0]['nodeName'], $tupel[0]['typeName'], $tupel[0]['stringValue']);
        break;
        case "pi": 
          $this->properties = new PI($tupel[0]['target']);
        break;
        case "attribute": 
          $this->properties = new Attribute($tupel[0]['nodeName'], $tupel[0]['typeName'], $tupel[0]['stringValue'], $tupel[0]['typedValue']);
        break;
        case "text": 
          $this->properties = new Text($tupel[0]['content']); 
        break;
        case "comment": 
          $this->properties = new Comment($tupel[0]['content']); 
        break;
        default:
          PEAR::raiseError("Der Knotentype '" . $this->type . "' kann nicht geladen werden!");
      }
    }
    
    /* Werte zur�ckgeben */
    return $this->properties;    
  }
  
  /* Setzt den Knoten auf den Ursprungswert zur�ck zur�ck */
  function rollback() {

    /* Tempor�re Knoten k�nnen nicht zur�ckgesetzt werden! */
    if ($this->storage != "db") {
    	PEAR::raiseError("Nur Dokumente in der Datenbank k�nnen zur�ckgesetzt werden!");
    }
    
    /* Daten zur�cksetzen */
    $this->axis = array();
    $this->properties = null;
  }
}