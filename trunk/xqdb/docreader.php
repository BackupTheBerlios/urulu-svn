<?PHP
/* Parser Laden */
require_once("parser.php");

/*
   Klasse, die ein Dokument verwaltet
 */
class DocReader extends Parser 
{
  /* Pfad zum Dokument */
  var $path;
  
  /* Speicherort des Dokuments */
  var $storage;
  
  /* ID des Wurzelknotens, wird nur im modus $compile == true gebraucht */
  var $root;
    
  /* Dokument einlesen */
  function createDom($uri, $buffer) {
    
    /* Variablen initialisieren */
    $this->query = $buffer;
    $this->position = 0;
    $encodedURI = $GLOBALS['XQDB_Storage']->encodeURI($uri);
    $this->path = $encodedURI['URI'];
    $this->storage = $encodedURI['Storage'];

    /* Dokument parsern */
    $id = $GLOBALS['XQDB_Storage']->createDoc($uri);
    $this->dirElemContent($id[0], false);
    if (isset($this->query[$this->position]) and preg_match("/\s/", substr($this->query, $this->position)) == 0) {
    	PEAR::raiseError("Das Dokument konnte nicht zu Ende geparsed werden!");
    }
  }  
    
  /* Token: DirectConstructor */
  // DirectConstructor ::= DirElemConstructor
  //                     | DirCommentConstructor
  //                     | DirPIConstructor
  // DirCommentConstructor ::= "<!--" DirCommentContents "-->"
  // DirCommentContents ::= ((Char - '-') | '-' (Char - '-'))*
	function directConstructor($parent = null, $compile = true) {
	  
	  /* DirCommentConstructor */
    if ($this->string("<!--")) {
      for ($loop = $this->position; $this->query[$loop] != "-" or ($this->query[$loop - 1] == "-" and $this->query[$loop] != "-"); $loop++);
      if ($compile) {
        $id = "\$x" . substr(md5(uniqid(microtime()) . rand()), 0, 5);
        $constructor = CODE_SEP . $id . "=\$GLOBALS['XQDB_Storage']->registerItem(new Comment('" 
            . preg_replace("/'/", "\\'", substr($this->query, $this->position, $loop - $this->position)) . "));";
        if ($parent != null) {
          $constructor .= CODE_SEP . "\$GLOBALS['XQDB_Storage']->insertItem(" . $id . "," . $parent . ");";
        } else {
          $this->root = $id;
        }
        return $constructor;
      } else {
        $id = $GLOBALS['XQDB_Storage']->registerItem(new Comment(substr($this->query, $this->position, $loop - $this->position)), 
            $this->storage, $this->path);
        $GLOBALS['XQDB_Storage']->insertItem($id, $parent);
      }
      $this->position = $loop;
      $this->string("-->", true);
      $this->nodes[$node]['end'] = $this->position;
      
      
    /* DirPIConstructor */
    } elseif (substr($this->query, $this->position, 2) == "<?") {
      return $this->dirPIConstructor($parent, $compile);

    /* DirElemConstructor */
    } else {
      return $this->dirElemConstructor($parent, $compile);
    }
  }

  /* Token: DirPIConstructor */
  // DirPIConstructor ::= "<?" PITarget (S DirPIContents)? "? >"
  // PITarget         ::= Name - (('X' | 'x') ('M' | 'm') ('L' | 'l'))
  // Name             ::= (Letter | '_' | ':') (NameChar)*
  // NameChar         ::= Letter | Digit | '.' | '-' | '_' | ':' | CombiningChar | Extender
  // DirPIContents    ::= (Char* - (Char* '? >' Char*))
	function dirPIConstructor($parent, $compile) {
	  $this->string("<?", true);

    /* Erstes Zeichen kontrollieren */
    if (strpos("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_:", $this->query[$this->position]) === false) {
      PEAR::raiseError("Syntaxfehler bei '" . substr($this->query, $this->position, 32) . "' <br />\nQName erwartet!");
    }

    /* weitere Zeichen suchen */
    for ($loop = $this->position + 1; strpos("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890.-_:", $this->query[$loop]) !== false; $loop++);
    
    /* Qname anfügen */
    $target = substr($this->query, $this->position, $loop - $this->position);

    /* Leerzeichen nachrücken */
    for (; strpos(" \t\r\n", $this->query[$loop]) !== false; $loop++);

    /* Position nachrücken */
    $this->position = $loop;
    
    /* DirPIContents */
    for (; substr($this->query, $loop, 2) != "?>"; $loop++);
    $value = substr($this->query, $this->position, $loop - $this->position);
    $this->position = $loop;
    $this->string("?>", true);
    
    /* Knoten anfügen */
    if ($compile) {
      $id = "\$x" . substr(md5(uniqid(microtime()) . rand()), 0, 5);
      $constructor = CODE_SEP . $id . "=\$GLOBALS['XQDB_Storage']->registerItem(new PI('" . preg_replace("/'/", "\\'", $target) . "'));";
      if ($parent != null) {
        $constructor .= CODE_SEP . "\$GLOBALS['XQDB_Storage']->insertItem(" . $id . "," . $parent . ");";
      } else {
        $this->root = $id;
      }
      return $constructor;
    } else {
      $id = $GLOBALS['XQDB_Storage']->registerItem(new PI($target), $this->storage, $this->path);
      $GLOBALS['XQDB_Storage']->insertItem($id, $parent);
    }
	}
  
  /* Token: DirElemConstructor */
  // DirElemConstructor   ::= "<" QName (S DirAttribute? )*  ("/>" | (">" DirElemContent* "</" QName S? ">"))
	function dirElemConstructor($parent, $compile) {
    $this->string("<", true);
    $name = $this->qname("QName", true);
    $constructor = "";

    /* Knoten einfügen */
    if ($compile) {
      $id = "\$x" . substr(md5(uniqid(microtime()) . rand()), 0, 5);
      $constructor = CODE_SEP . $id . "=\$GLOBALS['XQDB_Storage']->registerItem(new Element('" . preg_replace("/'/", "\\'", $name['LocalPart']) . "'));";
      if ($parent != null) {
        $constructor .= CODE_SEP . "\$GLOBALS['XQDB_Storage']->insertItem(" . $id . "," . $parent . ");";
      } else {
        $this->root = $id;
      }
    } else {
      $id = $GLOBALS['XQDB_Storage']->registerItem(new Element($name['LocalPart']), $this->storage, $this->path);
      $GLOBALS['XQDB_Storage']->insertItem($id, $parent);
    }
        
    /* Atribute suchen */
    while ($this->query[$this->position] != "/" and $this->query[$this->position] != ">") {
    	$constructor .= $this->dirAttribute($id, $compile);
    }
    
    /* Element besitz keine Body */
    if ($this->string("/>")) {
      $this->nodes[$parent]['end'] = $this->position;      
      return $constructor;
    }

    /* Body auswerten */
    $this->string(">", true);
    $constructor .= $this->dirElemContent($id, $compile);
    $this->string("</" . ($name['Prefix'] === null ? "" : $name['Prefix'] . ":" ) . $name['LocalPart'], true);
    $this->string(">", true);
    $this->nodes[$parent]['end'] = $this->position;      
    return $constructor;
	}
	
  /* Token: DirAttribute */
  // DirAttribute ::= QName S? "=" S? DirAttributeValue
  // DirAttributeValue ::= StringLiteral
	function dirAttribute($parent, $compile) {
    $name = $this->qname("QName", true);
    $this->string("=", true);
    $value = $this->literal("StringLiteral", true);
    
    /* Knoten einfügen */
    if ($compile) {
      $id = "\$x" . substr(md5(uniqid(microtime()) . rand()), 0, 5);
      $constructor = CODE_SEP . $id . "=\$GLOBALS['XQDB_Storage']->registerItem(new Attribute('" . $name['LocalPart'] . "','" 
          .  $value['Type'] . "','" . preg_replace("/'/", "\\'", $value['Value']) . "','" . preg_replace("/'/", "\\'", $value['Value']) . "'));";
      if ($parent != null) {
        $constructor .= CODE_SEP . "\$GLOBALS['XQDB_Storage']->insertItem(" . $id . "," . $parent . ");";
      }
      return $constructor;
    } else {
      $id = $GLOBALS['XQDB_Storage']->registerItem(new Attribute($name['LocalPart'], $value['Type'], 
          strval($value['Value']), $value['Value']), $this->storage, $this->path);
      $GLOBALS['XQDB_Storage']->insertItem($id, $parent);
    }
	}

	/* Token: DirElemContent */
  // DirElemContent       ::= DirectConstructor
  //                        | ElementContentChar
  //                        | CDataSection
  //                        | CommonContent
  // ElementContentChar   ::= Char - [{}<&]
	// CommonContent        ::= PredefinedEntityRef | CharRef | "{{" | "}}" | EnclosedExpr
	// PredefinedEntityRef  ::= "&" ("lt" | "gt" | "amp" | "quot" | "apos") ";"
  // CDataSection         ::= "<![CDATA[" CDataSectionContents "]]>"
  // CDataSectionContents ::= (Char* - (Char* ']]>' Char*))
  // S                    ::= (#x20 | #x9 | #xD | #xA)+
  // Char                 ::= #x9 | #xA | #xD | [#x20-#xD7FF] | [#xE000-#xFFFD] | [#x10000-#x10FFFF]
  // CharRef              ::= ('&#' [0-9]+ ';')
	//                        | ('&#x' [0-9a-fA-F]+ ';')
	function dirElemContent($parent, $compile) {
	  $constructor = "";
	  
	  /* Inhalt suchen, solange es das Element nicht geschlossen wird */
	  while (true) {
	    /* Ende des Strings angeben */
	    if ($this->position == strlen($this->query)) {
	    	return;
	    
  	  /* CDataSection */
	    } elseif ($this->string("<![CDATA[")) {
        for ($loop = $this->position; substr($this->query, $loop, 3) != "]]>"; $loop++);
        
        /* Knoten einfügen */
        if ($compile) {
          $id = "\$x" . substr(md5(uniqid(microtime()) . rand()), 0, 5);
          $constructor .= CODE_SEP . $id . "=\$GLOBALS['XQDB_Storage']->registerItem(new Text('"
              . preg_replace("/'/", "\\'", substr($this->query, $this->position + 9, $loop - $this->position - 1)) . "'));";
          if ($parent != null) {
            $constructor .= CODE_SEP . "\$GLOBALS['XQDB_Storage']->insertItem(" . $id . "," . $parent . ");";
          }
        } else {
          $id = $GLOBALS['XQDB_Storage']->registerItem(new Text(substr($this->query, $this->position + 9, $loop - $this->position - 1)), 
              $this->storage, $this->path);
          $GLOBALS['XQDB_Storage']->insertItem($id, $parent);
        }
       	$this->position = $loop + 2;
        
      /* Ende des Tokens */ 
      } elseif ($this->query[$this->position] == "<" and $this->query[$this->position + 1] == "/") {
        return $constructor;
  
      /* DirectConstructor */ 
      } elseif ($this->query[$this->position] == "<") {
        $constructor .= $this->dirElemConstructor($parent, $compile);
  
      /* EnclosedExpr */
      } elseif ($compile and ($this->query[$this->position] == "{" and isset($this->query[$this->position + 1]) and $this->query[$this->position + 1] != "{")) {
        $constructor .= CODE_SEP . "foreach (" . $this->enclosedExpr("array(" . $parent . ")") . " as \$item) {"
                     .  CODE_SEP . "\$GLOBALS['XQDB_Storage']->insertItem(\$item," . $parent . ");" . CODE_SEP . "}";
        
      /* Normaler Text */ 
      } else {
        for ($loop = $this->position; isset($this->query[$loop + 1]) and $this->query[$loop] != "<"; $loop++);
        $text = preg_replace(array('/""/', "/''/", "/&lt;/", "/&gt;/", "/&amp;/", "/&quot;/", "/&apos;/"),
            array('"', "'", "<", ">", "&", '"', "'"), substr($this->query, $this->position, $loop - $this->position));
        if (strpos($text, "&") !== false) {
          PEAR::raiseError("Syntaxfehler bei '" . substr($this->query, $this->position, $loop - $this->position + 12) 
              . "' <br />\nEs sind nur folgende Entities erlaubt: &lt;, &gt;, &amp;, &quot;, &apos;!");
        }

        /* Knoten einfügen */
        if ($compile) {
          $id = "\$x" . substr(md5(uniqid(microtime()) . rand()), 0, 5);
          $constructor .= CODE_SEP . $id . "=\$GLOBALS['XQDB_Storage']->registerItem(new Text('" . preg_replace("/'/", "\\'", $text) . "'));";
          if ($parent != null) {
            $constructor .= CODE_SEP . "\$GLOBALS['XQDB_Storage']->insertItem(" . $id . "," . $parent . ");";
          }
        } else {
          $id = $GLOBALS['XQDB_Storage']->registerItem(new Text($text), $this->storage, $this->path);
          $GLOBALS['XQDB_Storage']->insertItem($id, $parent);
        }
       	$this->position = $loop;
      }
	  }
	  return $constructor;
	}
}
?>