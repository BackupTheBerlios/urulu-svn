<?PHP
/* Gibt den Stringwert einer Sequenz zurück 
   string[] $sequence: Sequenz, dessen Stringvalue verlangt ist.
   string return: String-Wert der Sequenz
 */
function __toString($sequence) {
  $strVal = "";  
  if (count($sequence) > 0) {
    foreach ($sequence as $item) {
      $strVal .= $GLOBALS['XQDB_Storage']->toString($item);
    }  	
  }
  return $strVal;
}

/* Gibt den Integer Wert einer Sequenz zurück 
   string[] $sequence: Sequenz, dessen Integer Wert verlangt ist.
   string return: Boolscher-Wert der Sequenz
 */
function __toInteger($sequence) {
  return intval(__toString($sequence));
}

/* Gibt den Boolschen Wert einer Sequenz zurück 
   string[] $sequence: Sequenz, dessen Boolscher Wert verlangt ist.
   string return: Boolscher-Wert der Sequenz
 */
function __toBoolean($sequence) {
  $sequence = array_values($sequence);
  if (isset($sequence[1])) {
    $boolVal = true;
  } elseif (isset($sequence[0])) {
    $boolVal = $GLOBALS['XQDB_Storage']->toBoolean($sequence[0]);
  } else {
    $boolVal = false;
  }
  return $boolVal;
}

/* Gibt die XML-Repräsentation einer Sequenz zurück 
   string[] $sequence: Sequenz, dessen Stringvalue verlangt ist.
   string return: XML-Wert der Sequenz
 */
function __toXML($sequence) {
  $xmlVal = "";
  foreach ($sequence as $item) {
    $xmlVal .= $GLOBALS['XQDB_Storage']->toXML($item);
  }
  return $xmlVal;
}

/* Fügt die Angegebenen Knoten in den Zeilknoten ein
   string[] $source: Sequenz, die eingefügt werden soll
   string[] $dest: Sequenz, in die $source eingefügt werden soll
   string $mode: Gibt an, wo genau eingefügt werden soll:
                 into: Die Sequenz wir IN den Knoten eingefügt
                 before: Die Sequenz wird VOR dem Knoten eingefürt
                 after: Die Sequenz wird NACH dem Knoten eingefügt
   string[] return: Es wird immer eine leere Sequenz eingefügt
 */
function __insert($source, $dest, $mode) {

  /* über alle Knoten loopen */ 
  foreach ($dest as $destItem) {
    foreach ($source as $sourceItem) {
    	switch ($mode) {
    	  
    	  /* Bilder in den Knoten einfügen */
    	  case "into":
    	    $newSource = $GLOBALS['XQDB_Storage']->deepCopy($sourceItem, $GLOBALS['XQDB_Storage']->getURI($destItem));
    	    $GLOBALS['XQDB_Storage']->insertItem($newSource, $destItem, -1);
    	  break;
    	  
    	  /* Die anderen Mode werden noch nicht unterstützt */
    	  default:
    	    PEAR::raiseError("Der Moduls '" . $mode . "' wurde noch nicht implementiert!");
    	}
    }
  }
  return array();
}

/* Ersetzt eine Sequenz in einem File durch ein anderes
   string[] $source: Knoten, der eingefügz werden soll
   string[] $dest: Knoten, die durch source ersetzt werden sollen
   string[] return: Es wird immer eine leere Sequenz eingefügt
 */
function __replace($source, $dest) {
  if (count($source) != 1) {
    PEAR::raiseError("Ein Knoten kann nur durch genau einen Knoten ersetzt werden und es muss ein Ziel angegeben werden!");
  }
  
  /* Knoten ersetzen */
  if (count($dest) > 0) {
    foreach ($dest as $item) {
      $GLOBALS['XQDB_Storage']->update($item, $source[0]);
    }
  }
  return array();
}
  
/* Ruft eine Benuzterdefinierte Funktion auf
   string  $module: Name des Moduls, aus dem die Funktion aufgerufen werden soll
   string  $fnName: Name der Funktion, die aufgerufen werden soll
   mixed[] ...: Parameter, die übergeben werden sollen
   string[] return: Ausgabe der Funktion
 */
function __functionCall($module, $fnName) {

  /* Argumente auslesen */
  $arguments = func_get_args();
  
  /* Funktion laden falls nögig */
  if (isset($GLOBALS['XQDB_Fkts'][$fnName]) == false) {
    if (isset($GLOBALS['XQDB_declFkts'][$fnName])) {
    	include_once($GLOBALS['XQDB_declFkts'][$fnName]);
    } else {
      PEAR::raiseError("Die Funktion '" . $fnName . "' konnte nicht geladen werden!");
    }
  }
      
  /* Funktion ausgeben */
  return call_user_func_array($GLOBALS['XQDB_Fkts'][$fnName], array_slice($arguments, 2));
}

/* Vergleicht zwei Wert mit dem angegebenen Operator
   string[] $arg1: Erstes Argument
   string[] $arg2: Zweites Argument
   string  $operator: Vergleichsoperator
   string[] return: Boolscher Wert des Vergleichs
 */
function __comparison($arg1, $arg2, $operator) {
  switch ($operator) {
    case "=": if (__toString($arg1) == __toString($arg2)) { return $GLOBALS['XQDB_Storage']->boolTrue(); } break;
    case "!=": if (__toString($arg1) != __toString($arg2)) { return $GLOBALS['XQDB_Storage']->boolTrue(); } break;
    case "<": if (__toString($arg1) < __toString($arg2)) { return $GLOBALS['XQDB_Storage']->boolTrue(); } break;
    case "<=": if (__toString($arg1) <= __toString($arg2)) { return $GLOBALS['XQDB_Storage']->boolTrue(); } break;
    case ">": if (__toString($arg1) > __toString($arg2)) { return $GLOBALS['XQDB_Storage']->boolTrue(); } break;
    case ">=": if (__toString($arg1) >= __toString($arg2)) { return $GLOBALS['XQDB_Storage']->boolTrue(); } break; 
    default:
      PEAR::raiseError("Der Operator '" . $operator . "' wird nicht unterstützt!");
  }
  return $GLOBALS['XQDB_Storage']->boolFalse();
}

/* Importiert eine PHP-Variable in das System 
   mixed $value: Name der Veraiable 
   int $level: Tiefe in die das System noch absteigen soll
   string $parent: ID des Vaterelements
   string[] return ID's der Variablen 
*/
function __importPHPVar($value, $name, $level, $parent = null) {
  
  /* Level ist überschritten */
  if ($level == -1);
  	
  /* Erzeugen eines Dokumentsknotens */
  elseif ($parent == null) {
    if ($GLOBALS['XQDB_Storage']->isDoc("php://" . $name)) {
    	return $GLOBALS['XQDB_Storage']->doc("php://" . $name);
    } else {
     $docNode = $GLOBALS['XQDB_Storage']->createDoc("php://" . $name);
      __importPHPVar($value, $name, $level, $docNode[0]);
      return $docNode;
    }
  	
  /* Variable ist ein Array */
  } elseif (is_array($value)) {
    foreach ($value as $key => $child) {
      $id = $GLOBALS['XQDB_Storage']->registerItem(new Element($key), "php", $name);
      $GLOBALS['XQDB_Storage']->insertItem($id, $parent);	    	
      __importPHPVar($child, $name, $level - 1, $id);
    }
    
  /* Variable ist ein skalarer Wert */
  } else {
    $id = $GLOBALS['XQDB_Storage']->registerItem(new Text($value), "php", $name);
    $GLOBALS['XQDB_Storage']->insertItem($id, $parent);    
  }
}

/* Führt einen Step aus
   string[] $context: Sequenz, mit den Kontextknoten
   string $axis: Achse, die ausgeführt werden soll
   string $nodeTest: NodeTest, der ausgeführt werden soll
   string[] return Sequenz mit den neuen Knotextknoten
 */
function __axisStep($context, $axis, $nodeText) {
  $sequence = array();
  foreach ($context as $item) {
  	$sequence = array_merge($sequence, $GLOBALS['XQDB_Storage']->axisStep($item, $axis, $nodeText));
  }
  return $sequence;
}

/* Boolsches-und
   string[] $arg1: Erstes Argument
   string[] $arg2: Zeites Argument
   string[] return Boolscher Wert mit dem Ergebnis der Abfrage
 */
function __andExpr($arg1, $arg2) {
  if (__toBoolean($arg1) and __toBoolean($arg2)) {
  	return $GLOBALS['XQDB_Storage']->boolTrue(); 
  } else {
    return $GLOBALS['XQDB_Storage']->boolFalse();
  }
}

/* Boolsches-oder
   string[] $arg1: Erstes Argument
   string[] $arg2: Zeites Argument
   string[] return Boolscher Wert mit dem Ergebnis der Abfrage
 */
function __orExpr($arg1, $arg2) {
  if (__toBoolean($arg1) or __toBoolean($arg2)) {
  	return $GLOBALS['XQDB_Storage']->boolTrue(); 
  } else {
    return $GLOBALS['XQDB_Storage']->boolFalse();
  }
}

/* Führt einen IF-Ausdruck aus
   bool $condition: Abfrage des IF-Ausdrucks
   string[] $true: Ausgabe falls $condition wahr ist
   string[] $false: Ausgabe falls $condition fasch ist
   string[] return: Ausgabe, entweder $true oder $false
 */
function __ifExpr($condition, $true, $false) {
  if ($condition) {
  	return $true;
  } else {
    return $false;
  }
}
?>