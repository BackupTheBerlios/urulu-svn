<?PHP
/* Wrapper für die PEAR:DB
   string[] $context: Kontextknoten in der Funktion
   string[] $query: Name der Query, die ausgeführt werden soll
   string[] $params: Parameter, die die Query noch braucht
   string[] return: ID des Dokumentknotens des neu erstellten Dokuments
 */
function __sql($context, $query, $params = array()) {
  
  /* Parameter erstellen */
  $paramsStr = array();
  foreach ($params as $item) {
    $paramsStr[] = $GLOBALS['XQDB_Storage']->toString($item);
  }
  
  /* Query auswerten */
  $result = __executeSQL(__toString($query), $paramsStr, true);
  
  /* Dokument mit den Lösungen erstellen */
  $document = $GLOBALS['XQDB_Storage']->createDoc("tmp://" . md5(uniqid(microtime()) . rand()));
  foreach ($result as $key => $tupel) {
    $element = $GLOBALS['XQDB_Storage']->registerItem(new Element("tupel_" . $key));
    $GLOBALS['XQDB_Storage']->insertItem($element, $document[0]);
    foreach ($tupel as $name => $value) {
      $id = $GLOBALS['XQDB_Storage']->registerItem(new Element($name));
      $GLOBALS['XQDB_Storage']->insertItem($id, $element);
      $text = $GLOBALS['XQDB_Storage']->registerItem(new Text($value));
      $GLOBALS['XQDB_Storage']->insertItem($text, $id);
    }
  }

  /* Neu erstelltes Dokument zurückgeben */
  return $document;
}
 
/* Modul Kompilieren und niederschreiben
   string[] $context: Kontextknoten in der Funktion
   string $source: Sequenz mit den Sourcen des Moduls
   string $name: Sequenz mit dem Namen des Modules, wird nur bei
          Mainmodulen gebraucht
   string[]  return: ID des Dokumentknotens des neu erstellten Dokuments
 */
function __compile($context, $source, $name) {
  $sourceStr = __toString($source);
  
  /* Compiler initialisieren */
  static $compiler;
  if ($compiler == null) {
    include_once("compiler.php");
    $compiler = new Compiler();
  }
  
  /* Modul Kompilieren */
  $code = $compiler->compile($sourceStr);

  if (isset($code['Module'])) {

    /* Libarymodule bearbeiten */
  	$dir = BIN_DIR . preg_replace("/\W/", "_", $code['Module']);
  	$name = $dir . ".php";
    if (is_dir($dir)) {
      foreach (__ls_r($dir . "/") as $file) {
      	if (is_dir($file)) {
      		rmdir($file);
      	} else {
      	  unlink($file);
      	}
      }
    }
    
    /* Ordner neu anlegen */
    mkdir($dir);
    chmod($dir, 0777);

    /* Funktionen niederschreiben */ 
    foreach ($code['Functions'] as $fnName => $body) {
      $fp = fopen($dir  . "/" . $fnName . ".php", 'w');
      fwrite($fp, $body);
      fclose($fp);
      chmod($dir  . "/" . $fnName . ".php", 0777);
    }

  /* Name von Mainmodulen anpassen */
  } else {
    $name = BIN_DIR . preg_replace("/\W/", "_", substr($name, strrpos($name, "/") + 1)) . ".php";
  }

  /* Hauptteil niederschreiben */
  if (($fp = @fopen($name, 'w')) == false) {
    PEAR::raiseError("Die Datei '" . $name . "' kann nicht geöffnet werden!");
  }
  fwrite($fp, $code['Main']);
  fclose($fp);
  chmod($name, 0777);
  
  /* Leere Sequenz zurückgeben */
  return array();
}

/* Erstellt ein Dokument mit dem angegebenen URI
   string[] $context: Kontextknoten in der Funktion
   string[] $uri: ID des Namens des Dokuments
   string[]  return: ID des Dokumentknotens des neu erstellten Dokuments
 */
function __createDoc($context, $uri) {
  return $GLOBALS['XQDB_Storage']->createDoc(__toString($uri));
}

/* Speichert die Änderungen, die an Ressource vorgenommen wurden in die Ressource hinein
   string[] $context: Kontextknoten in der Funktion
   string[] $uri: ID des Dokuments, das definitiv gespeichert werden soll
   string[] return: Die Funktion gib immer eine leere Sequenz zurück
 */
function __commit($context, $uri) {
  
  /* Kardinalität überprüfen */
  if (count($uri) != 1) {
    PEAR::raiseError("Es kann nur ein Dokument gleichzeitig gespeichert werden!");  	
  }
  
  /* Interne Funktion aufrufen */
  $GLOBALS['XQDB_Storage']->commit($uri[0]);
  return array();
}

/* Macht alle noch nicht committeten Änderungen an der Ressource rückgängig
   string[] $context: Kontextknoten in der Funktion
   string[] $uri: ID des Dokuments, dessen Änderungen rückgängig gemacht werden sollen
   string[] return: Die Funktion gib immer eine leere Sequenz zurück
 */
function __rollback($context, $uri) {

  /* Kardinalität überprüfen */
  if (count($uri) != 1) {
    PEAR::raiseError("Es kann nur ein Dokument gleichzeitig zurückgesetzt werden!");  	
  }
  
  /* Interne Funktion aufrufen */
  $GLOBALS['XQDB_Storage']->rollback($uri[0]);
  return array();
}

/* Funktion um auf die Variablen, die in der Session gespeichert sind zuzugreifen
   string[] $context: Kontextknoten in der Funktion
   string[] return: Gibt eine Sequenz mit den Dokumentknoten zurück
 */
function __session($context) {
  return __importPHPVar($_SESSION['variables'], "_SESSION/variables", -2);
}


/* Setzt ein Zugriffsrecht auf einen Knoten
   string[] $target: Knoten auf den das Zugriffsrecht gesetzt werden soll
   string[] $role: Rolle für die das Recht gelten soll
   string[] $action: Aktion, für die das Zugriffsrecht gelten soll. Folgende Rechte sind erlaubt: "select", "insert", "update", "rename"
   string[] $node: Welche Knotentypen sollen vom Zugriffsrecht betroffen sein. Folgende Knotentypen sind erlaubt: "element", "attribute", "text", "comment". 
            Mit der Angabe "node" sind nur Element-, Text-, und Kommentarknoten erlaubt. Mit der Angabe "item" sind alle Knoten betroffen
   string[] $type: Type des Rechtes, folgende Werte sind erlaubt: "execute", "grant"
   string[] $instance: Name der Instanz die Betroffen ist. Dieser Wert hat nur Auswirkungen, wenn $node auf "element" gesetzt ist. Folgende werte sind erlaubt: "*", qName
   string[] $descendant: Auf wieviele Hierarchiestufen Abwärts soll das recht gelten? Folgede Werte sind zugelassen: "inf", int
 */
function __grant($target, $role, $action = null, $node = null, $instance = null, $descendant = null) {

//$action = "select", $node = "item", $type = "execute", $instance = "*", $descendant = "int"


}

/* Entfernt ein Zugriffsrecht von einem Knoten
   string[] $target: Knoten von dem das Recht entfernt werden soll
   string[] $role: Rolle für die das Zugriffsrecht entfernt werden soll
 */
function __revoke($target, $role) {
  
  
}
?>