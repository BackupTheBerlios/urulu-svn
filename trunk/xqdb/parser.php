<?PHP
/**
 *  xQuery Parser
 *
 * @author  Lukas Gamper <mistral@pfadidunant.ch>
 */
 
/**
 * xQuery Parser
 *
 * @author  Lukas Gamper <mistral@pfadidunant.ch>
 * @access  public
 */
class Parser
{
  /* Anfrage, die geparsed werden soll */
  var $query;
  
  /* Position an der sich der Parser befindet */
  var $position;
  
/**
 * Überprüft ob ein String an der angegebenen Position vorkommt
 */
  function string($string, $expected = false, $stepForward = true) {
    
    /* Überprüfen ob die angegebene Zeichenktte am der angegenenen Position vorkommt */
    $match = false;
    if (strtolower(substr($this->query, $this->position, strlen($string))) == $string) {
    	$match = true;
    	if ($stepForward == true) {
    	  $this->position += strlen($string);
    	  for (; isset($this->query[$this->position]) and strpos(" \t\r\n", $this->query[$this->position]) !== false; $this->position++);
      }
    }

    /* Falls sie Vorkommen sollt aber nicht vorkommt, Fehler ausgeben */
    if ($expected == true and $match == false) {
      PEAR::raiseError("Syntaxfehler bei '" . substr($this->query, $this->position, 32) . "' <br />\nZeichenkette '" .  $string . "' erwartet!");
    }
    
    /* Resultat zurückgeben */
    return $match;
  }

  /* Token: QName */
  // QName      ::= (Prefix ':')? LocalPart*
  // Prefix     ::= NCName
  // LocalPart  ::= NCName
  // NCName 	  ::= (Letter | '_') (NCNameChar)*
  // NCNameChar ::= Letter | Digit | '.' | '-' | '_'
  function qname($type = "QName", $expected = false, $stepForward = true) {
    
    /* Erstes Zeichen kontrollieren */
    if ($this->position == strlen($this->query) or strpos("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_", $this->query[$this->position]) === false) {
      if ($expected == true) {
        PEAR::raiseError("Syntaxfehler bei '" . substr($this->query, $this->position, 32) . "' <br />\nQName erwartet!");
      }
      return false;
    }
    
    /* weitere Zeichen suchen */
    for ($loop = $this->position + 1; isset($this->query[$loop]) and strpos("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890.-_", $this->query[$loop]) !== false; $loop++);
    
    /* Qname mit Prefix */
    if (isset($this->query[$loop]) and $this->query[$loop] == ":" and $type == "QName") {
      if (strpos("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_", $this->query[$loop + 1]) === false) {
        PEAR::raiseError("Der angegebene QName '" . substr($this->query, $this->position, 32) . "' hat keinen LocalPart!");
      }
      for ($localLoop = $loop + 2; strpos("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890.-_", $this->query[$localLoop]) !== false; $localLoop++);
      $info = array('Prefix' => substr($this->query, $this->position, $loop - $this->position), 'LocalPart' => substr($this->query, $loop + 1, $localLoop - $loop - 1));

    /* Nur NCName */
    } else {
      $info = array('Prefix' => null, 'LocalPart' => substr($this->query, $this->position, $loop - $this->position));
      $localLoop = $loop;
    }

    /* Leerzeichen nachrücken */
    for (; isset($this->query[$localLoop]) and strpos(" \t\r\n", $this->query[$localLoop]) !== false; $localLoop++);
    $info['Position'] = $localLoop;
    
    /* Position nachrücken */
    if($stepForward == true) {
      $this->position = $localLoop;
    }
    
    /* Resultat ausgeben */
    return $info;
  }

  /* Token: Literal */
  // Literal             ::= NumericLiteral | StringLiteral
  // NumericLiteral      ::= IntegerLiteral | DecimalLiteral | DoubleLiteral
  // IntegerLiteral      ::= Digits
  // DecimalLiteral      ::= ("." Digits) | (Digits "." [0-9]*)
  // DoubleLiteral 	     ::= (("." Digits) | (Digits ("." [0-9]*)?)) [eE] [+-]? Digits
  // StringLiteral 	     ::= ('"' (PredefinedEntityRef | CharRef | ('"' '"') | (Char - ["{}<&]))* '"') 
  //                       | ("'" (PredefinedEntityRef | CharRef | ("'" "'") | (Char - ['{}<&]))* "'")
  // PredefinedEntityRef ::= "&" ("lt" | "gt" | "amp" | "quot" | "apos") ";"
  // Digits 	           ::= [0-9]+
  // Char                ::= #x9 | #xA | #xD | [#x20-#xD7FF] | [#xE000-#xFFFD] | [#x10000-#x10FFFF]
  // CharRef             ::= ('&#' [0-9]+ ';')
	//                       | ('&#x' [0-9a-fA-F]+ ';')
	function literal($type = "Literal", $expected = false, $stepForward = true) {
		$literal = $match = $match2 = null;
		
		/* IntegerLiteral */
    if (($type == "Literal" or $type == "NumericLiteral" or $type == "IntegerLiteral")
        and preg_match('/^(\d+)/', substr($this->query, $this->position), $match)) {
      $literal = array('Name' => "IntegerLiteral", 'Type' => "xs:integer", 'Value' => $match[1], 'Length' => strlen($match[1]));
    }
    
		/* DecimalLiteral */
    if ($literal === null and ($type == "Literal" or $type == "NumericLiteral" or $type == "DecimalLiteral")
        and (preg_match('/^(\.\d+)/', substr($this->query, $this->position), $match) 
            or preg_match('/^(\d+(\.\d*)?)/', substr($this->query, $this->position), $match2))) {
      if ($match2 !== null) {
        $match = $match2;
      }
      $literal = array('Name' => "DecimalLiteral", 'Type' => "xs:decimal", 'Value' => $match[1], 'Length' => strlen($match[1]));
    }
    
		/* DoubleLiteral */
    if ($literal === null and ($type == "Literal" or $type == "NumericLiteral" or $type == "DoubleLiteral")
        and (preg_match('/^(\.\d+[eE][\+\-]?\d+)/', substr($this->query, $this->position), $match) 
            or preg_match('/^(\d+(\.\d*)?[eE][\+\-]?\d+)/', substr($this->query, $this->position), $match2))) {
      if ($match2 !== null) {
        $match = $match2;
      }
      $literal = array('Name' => "DoubleLiteral", 'Type' => "xs:double", 'Value' => $match[1], 'Length' => strlen($match[1]));
    }
    
		/* StringLiteral */
    if ($literal === null and ($type == "Literal" or $type == "StringLiteral") and ($this->query[$this->position] == '"' or $this->query[$this->position] == "'")) {
      for ($loop = $this->position + 1; $this->query[$loop] != $this->query[$this->position] or ($this->query[$loop] == $this->query[$this->position] 
          and ($this->query[$loop - 1] == $this->query[$this->position] or (isset($this->query[$loop + 1]) and $this->query[$loop + 1] == $this->query[$this->position]))); $loop++);
      $literal = array('Name' => "StringLiteral", 'Type' => "xs:string", 'Value' => preg_replace(array('/""/', "/''/", "/&lt;/", "/&gt;/", "/&amp;/", "/&quot;/", "/&apos;/"),
          array('"', "'", "<", ">", "&", '"', "'"), substr($this->query, $this->position + 1, $loop - 1 - $this->position)), 'Length' => $loop - $this->position + 1);
      if (strpos($literal['Value'], "&") !== false) {
        PEAR::raiseError("Syntaxfehler bei '" . substr($this->query, $this->position, $loop - $this->position + 12) . "' <br />\nEs sind nur folgende Entities erlaubt: &lt;, &gt;, &amp;, &quot;, &apos;!");
      }
    }
    
    /* Falls Literal erwartet, und nicht vorhanden, Fehler ausgeben */
    if ($literal == null and $expected == true) {
      PEAR::raiseError("Syntaxfehler bei '" . substr($this->query, 0, 128) . "' <br />\nLiteral erwartet");
    }
    
    /* Falls nur abgefragt wird, ob ein Literal da ist, oder wenn keines Gefunden wurde */
    if ($literal === null) {
      return false;
    }
    
    if($stepForward == true) {
      /* Position nachrücken */
      $this->position += $literal['Length'];
      
      /* Leerzeichen nachrücken */
      for (; strpos(" \t\r\n", $this->query[$this->position]) !== false; $this->position++);
    }
    
    return $literal;
  }
}
?>