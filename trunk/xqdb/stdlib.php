<?PHP

/* Gibt den Dokumentknoten eines Dokuments zurück
   string[] $context: Kontextknoten in der Funktion
   string[] $uri: URI des Dokuments
   string[]  return: Dokumentknoten des Dokuments
*/
// fn:doc($uri as xs:string?) as document-node()?
function __doc($context, $uri) {
  return $GLOBALS['XQDB_Storage']->doc(__toString($uri));
}

/* Gibt true zurück wenn die Sequenz mindestens ein Item enthällt, sonst false 
   string[] $context: Kontextknoten in der Funktion
   string[] $sequence: Sequenz, die überprüft werden soll
   string[]  return: Ergebnis des Test
 */
// fn:exists($arg as item()*) as xs:boolean
function __exists($context, $sequence) {
  if (count($sequence) > 0) {
  	return $GLOBALS['XQDB_Storage']->boolTrue();
  } else {
  	return $GLOBALS['XQDB_Storage']->boolFalse();
  }
}

/* Dreht die Rehenfolge einer Sequenz
   string[] $context: Kontextknoten in der Funktion
   string[] $arg: Eingabesequenz
   string[]  return: Gedrehte Sequenz
 */
// fn:reverse($arg as item()*) as item()*
function __reverse($context, $arg) {
  return array_reverse($arg);
}

/* Gibt eine Telsequenz der übergebenen Sequenz zurück
   string[] $context: Kontextknoten in der Funktion
   string[] $sourceSeq: Sequenz die geteilt werden soll
   string[] $startiengLoc: Punkt ab dem die Tokens extrahiert werden sollen
   string[] $length: Anzahl der Items, die ausgegeben werden sollen. Falls
            der Parameter nicht übergeben wird, wird die Sequenz bis am Schluss übergeben
   strint[] return Angegebene Teilsequenz
 */
// fn:subsequence($sourceSeq as item()*, $startingLoc as xs:double, $length as xs:double) as item()*
function __subsequence($context, $sourceSeq, $startiengLoc, $length = null) {
  return array_slice($sourceSeq, __toInteger($startiengLoc), ($length === null ? null : __toInteger($length)));
}

/* Teilt den String in Zeile auf
   string[] $context: Kontextknoten in der Funktion
   string[] $input: Sequenz die geteilt werden soll
   string[] $pattern: Punkt ab dem die Tokens extrahiert werden sollen
   strint[] return Angegebene Teilsequenz
 */
// fn:tokenize($input as xs:string?, $pattern as xs:string) as xs:string+
function __tokenize($context, $input, $pattern) {
  $sequence = array();
  foreach (preg_split("#" . preg_replace("/#/", "\\#", __toString($pattern)) . "#", __toString($input)) as $item) {
    $sequence[] = $GLOBALS['XQDB_Storage']->registerItem(new AtomicValue($item, "xs:string"));
  }
  return $sequence;
}

/* Zählt die Anzahl Elemente einer Sequenz
   string[] $context: Kontextknoten in der Funktion
   string[] $arg: Sequenz die gezählt werden soll
   strint[] return Anzahl der Elemente, die in der Sequenz enthalten waren
 */
// fn:count($arg as item()*) as xs:integer
function __count($context, $arg) {
  return array($GLOBALS['XQDB_Storage']->registerItem(new AtomicValue(count($arg), "xs:integer")));
}

/* Gibt die Position des Items in der Sequenz zurück
   string[] $context: Kontextknoten in der Funktion
   strint[] return Position an der sich das Item befindet
 */
// fn:position() as xs:integer
function __position($context) {
  $keys = array_keys($context);
  if (count($keys) != 1) {
  	PEAR::raiseError("Die Funktion Position kann nur mit Sequnzen mit einem Item aufgerufen werden!");
  }
  return array($GLOBALS['XQDB_Storage']->registerItem(new AtomicValue($keys[0], "xs:integer")));
}
?>