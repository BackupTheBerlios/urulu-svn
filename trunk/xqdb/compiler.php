<?PHP
/**
 *  xQuery Compiler
 *
 * @author  Lukas Gamper <mistral@pfadidunant.ch>
 */
 
/* Parser Laden */
require_once("docreader.php");
 
/**
 * xQuery Compiler
 *
 * @author  Lukas Gamper <mistral@pfadidunant.ch>
 * @access  public
 */
class Compiler extends DocReader 
{
  /* Informationen über das Modul. */
  var $infos;

  /* Liste mit allen Modulen, die gebraucht werden. */
  var $modules;

  /* Liste mit allen Funktionen, die im Modul deklariert wurden. */
  var $functions;

  /* Instruktionen */
  var $instructions;
  
  /* Deklarierte Variablen */
  var $variables;
    
/**
 * Kompiliert einen String als xQuery-Ausdruck und liefert die 
 * entsprechendent Tokens zurück.
 */
  function compile($query) {
    /* Variablen initialisieren */
    $this->query = $query;
    $this->position = 0;
    $this->infos = array();
    $this->modules = array();
    $this->functions = array();
    $this->instructions = "";
    $this->variables = array();
    
    /* Kommentare entfernen */
    $this->removeComments();

    /* Anfrage Parser */
    $moduleInstructions = $this->module();

    /* Module suchen, die Geladen werden sollten */
    $moduleInclude = "";
    foreach ($this->modules as $module) {
      $moduleInclude .= CODE_SEP . "require_once(BIN_DIR . '" . preg_replace("/\W/", "_", $module['Value']) . ".php');";
    }

    /* Main-Module */
    if ($this->infos['Type'] == "Main") {
      $instructions = "<?PHP"
         . CODE_SEP . "require_once ('../xqvm.php');"
         . $moduleInclude;
         foreach ($this->functions as $function) {
           $instructions .= CODE_SEP . $function['Body'];
           $instructions .= CODE_SEP . "\$GLOBALS['XQDB_Fkts']['" . $function['Name'] . "']='__userFN_" . $function['Name'] . "';";
         }
         $instructions .= $this->instructions . $moduleInstructions . CODE_SEP . "?>";
      return array('Main' => $instructions);
    
    /* Libarymodule */
    } else {
      $files = array('Module' => $this->infos['Value'],
                     'Functions' => array());
      $defindedFunctions = array();
      foreach ($this->functions as $function) {
      	$files['Functions'][$function['Name']] = "<?PHP"
           . CODE_SEP . "\$GLOBALS['XQDB_Fkts']['" . $function['Name'] . "']='__userFN_" . $function['Name'] . "';"
           . $function['Body'] . CODE_SEP . "?>";
        $defindedFunctions[] = CODE_SEP . "'" . $function['Name'] . "'=>BIN_DIR.'" . preg_replace("/\W/", "_", $this->infos['Value']) . "/" 
           . $function['Name'] . ".php'";
      }
      $files['Main'] = "<?PHP" . $moduleInclude;
      if (isset($defindedFunctions[0])) {
      	$files['Main'] .= CODE_SEP . "\$GLOBALS['XQDB_declFkts']=array_merge(\$GLOBALS['XQDB_Fkts'], array(" 
      	   . implode(",", $defindedFunctions) . "));";
      }
      $files['Main'] .= ($this->instructions ? CODE_SEP . $this->instructions : "") . CODE_SEP . "?>";
      return $files;
    }
  }
  
  /* Kommentare entfernen */
  function removeComments() {
    $position = 0;
    do { 
      $targetPos = strpos($this->query, "(:", $position);
      if ($targetPos !== false) {
      	$position = $targetPos;
      	if (substr_count(substr($this->query, 0, $position), '"') % 2 == 0 and substr_count(substr($this->query, 0, $position), "'") % 2 == 0) {
          $endPos = strpos($this->query, ":)", $position) + 2;
          $this->query = substr($this->query, 0, $position) . substr($this->query, $endPos);
      	} else {
      	  $position += 2;
      	}
      }
    } while ($targetPos !== false);
    
    /* Allfällige Leerzeichen am Anfang einfügen */
    $this->query = ltrim($this->query);
  }

  /* Token: Module */
	// Module ::= MainModule | LibraryModule
	// LibraryModule ::= "module" "namespace" NCName "=" StringLiteral ";" Prolog
	// MainModule ::= Prolog Expr
	function module() {

	  /* LibraryModule parsen */
    if ($this->string("module")) {
      $this->string("namespace", true);
      $this->qname("NCName", true);
      $this->string("=", true);
      $value = $this->literal("StringLiteral", true);
      $this->string(";", true);
      $this->infos = array('Type' => "Libary", 'Value' => $value['Value']);
      
    /* MainModule parsen */
    } else {
      $this->infos = array('Type' => "Main");
    }
      
    /* Prolog? */
    if ($this->string("import", false, false) or $this->string("declare", false, false)) {
      $this->prolog();
    }
      
    /* Expr */
    if ($this->infos['Type'] == "Main") {
      return CODE_SEP . "output(__toXML(" . $this->expr() . CODE_SEP . "));";
    }
	}
  
  /* Token: Prolog */
	// Prolog ::= ( ModuleImport ";" )* (( VarDecl | FunctionDecl ) ";" )*
	// ModuleImport ::= "import" "module" ( "namespace" NCName "=" )? StringLiteral
	function prolog() {

	  /* Module Includen */
    while ($this->string("import")) {
      $this->string("module", true);      
      if ($this->string("namespace")) {
        $name = $this->qname("NCName", true);
        $this->string("=", true);
      } else {
        $name = array('LocalPart' => "");
      }
      $value = $this->literal("StringLiteral", true);
      $this->string(";", true);
      $this->modules[] = array("Name" => $name['LocalPart'], "Value" => $value['Value']);
    }

    /* Funktionen und Variablen deklarieren */
    while ($this->string("declare")) {
      if ($this->string("variable")) {
        $this->VarDecl();
      } else {
        $this->string("function", true);
        $this->FunctionDecl();
      }
      $this->string(";", true);
    }
	}

  /* Token: VarDecl */
  // VarDecl ::= "declare" "variable" "$" QName ( "as" SequenceType )? (( ":=" ExprSingle ) | "external" NCName "maxlevel" IntegerLiteral )
	function varDecl() {

	  /* Name der Variablen suchen */
    $this->string("$", true);
    $name = $this->qname("QName", true);
    $this->variables[$name['LocalPart']] = "\$GLOBALS['__userVAR_" . $name['LocalPart'] . "']";
    
    /* Der Code kann noch nicht mit Type umgehen */
    if ($this->string("as")) {
      $this->sequenceType();
    }

    /* Body suchen */
    if ($this->string(":=")) {
      $this->instructions .= CODE_SEP . $this->variables[$name['LocalPart']] . "=" . $this->exprSingle() . ";";
    } else {
      $this->string("external", true);
      $value = $this->qname("NCName", true);
      $this->string("maxlevel", true);
      $maxlevel = $this->literal("IntegerLiteral", true);
      if (in_array($value['LocalPart'], $GLOBALS['XQDB_ALLOWED_VARIABLE']) == false) {
        PEAR::raiseError("Es ist nicht erlaubt die Variable '" . $value['LocalPart'] . "' einzubinden!");
      }
      $this->instructions .= CODE_SEP . $this->variables[$name['LocalPart']] . "=__importPHPVar($" . $value['LocalPart'] . ", '" . $value['LocalPart'] . "'," . $maxlevel['Value'] . ");";
    }
  }
	
  /* Token: SequenceType */
  // SequenceType        ::= ( ItemType OccurrenceIndicator? ) | "empty" "(" ")"
  // ItemType            ::= KindTest | "item" "(" ")" | AtomicType
  // OccurrenceIndicator ::= "?" | "*" | "+"
	function sequenceType() {
	  
	  /* leerer Type */
    if ($this->string("empty")) {
      $this->string("(");
      $this->string(")");
    
    /* Knotentypen */
    } else {
      if ($this->string("item")) {
        $this->string("(");
        $this->string(")");
      } elseif ($this->string("document-node", false, false) or $this->string("element", false, false)
          or $this->string("attribute", false, false) or $this->string("processing-instruction", false, false)
          or $this->string("comment", false, false) or $this->string("text", false, false)
          or $this->string("node", false, false)) {
        $this->kindTest();
        
      /* Atomare Typen */
      } else {
        $this->atomicType();
      }
      if (strpos("?*+", $this->query[$this->position]) !== false) {
        $this->string($this->query[$this->position], true);
      }
    }
	}

  /* Token: KindTest */
  // KindTest ::= "document-node" "(" ")"
  //            | "element" "(" ( QName | "*" ( "," QName "?"? )? )? ")" 
  //            | "attribute" "(" (QName | "*" ("," QName)?)? ")"
  //            | "processing-instruction" "(" ")"
  //            | "comment" "(" ")"
  //            | "text" "(" ")"
  //            | "node" "(" ")"
	function kindTest() {
    foreach (array("document-node", "element", "attribute", "comment", "text", "node") as $type) {
      if ($this->string($type)) {
        $kindTest = array("Name" => $type);
        break;
      }
    }
    if (isset($kindTest) == false) {
      PEAR::raiseError("Syntaxfehler bei '" . substr($this->query, $this->position, 32) . "' <br />\nKindTest erwartet!");
    }
    $this->string("(", true);
    if (in_array($kindTest['Name'], array("element", "attribute"))) {
      if ($this->string("*")) {
        $kindTest['Specialisation'] = array("*");
      } elseif (($name = $this->qname("QName")) !== false) {
        $kindTest['Specialisation'] = array($name['LocalPart']);
      } else {
        $this->string(")", true);
        return $kindTest;
      }
      if ($this->string(",")) {
        $qname = $this->qname("QName", true);
        $kindTest['Specialisation'][] = $qname['LocalPart'];
        if ($kindTest['Name'] == "element" and $this->string("?")) {
          $kindTest['Specialisation'][] = "?";
        }
      }
    }
    $this->string(")", true);
    return $kindTest;
	}
          
  /* Token: AtomicType */
  // AtomicType ::= ( "xdt:anyAtomicType" | "xs:string" | "xs:float" | "xs:double" | "xs:decimal" | "xs:integer" | "xs:boolean" )
	function atomicType() {
    foreach (array("xdt:anyAtomicType", "xs:string", "xs:float", "xs:double", "xs:decimal", "xs:integer", "xs:boolean") as $type) {
      if ($this->string($type)) {
        return;
      }
    }
    PEAR::raiseError("Syntaxfehler bei '" . substr($this->query, $this->position, 32) . "' <br />\nAtomicType erwartet!");
	}
  
  /* Token: FunctionDecl */
  // FunctionDecl ::= "declare" "function" QName "(" ( Param ("," Param)* )? ( ")" | ( ")" "as" SequenceType )) ( EnclosedExpr | "external" NCName )
  // Param ::= "$" QName ( "as" SequenceType )?
	function functionDecl() {

	  /* Name suchen */
	  $name = $this->qname("QName", true);
	  $functionHead = CODE_SEP . "function __userFN_" . $name['LocalPart'] . "(\$context";
	  $function = "";

    /* Argumente auswerten */
	  $variables = $this->variables;
	  $instructions = $this->instructions;
	  $this->instructions = "";
    $arguments = array();
    $this->string("(", true);
    if ($this->string("$", false, false)) {
      do {
        $this->string("$", true);
        $argument = $this->qname("QName", true);
    	  $arguments[] = "\$" . $argument['LocalPart'];
    	  $function .= CODE_SEP . "\$GLOBALS['__userVAR_" . $name['LocalPart'] . "_" . $argument['LocalPart'] . "']=\$" . $argument['LocalPart'] . ";";
        $this->variables[$argument['LocalPart']] = "\$GLOBALS['__userVAR_" . $name['LocalPart'] . "_" . $argument['LocalPart'] . "']";
        if ($this->string("as")) {
          $this->sequenceType();
        }
      } while ($this->string(","));
    }
    $this->string(")", true);

    /* Rückgabetype bestimmen */
    if ($this->string("as")) {
      $this->sequenceType();
    }

    /* Funktionsbody auswerten */
    $function = $functionHead . (isset($arguments[0]) ? "," . implode(",", $arguments) : "") . ")" . "{" . $function .  CODE_SEP;
    if ($this->string("external")) {
      $binding = $this->qname("NCName", true);
      if (in_array($binding['LocalPart'], $GLOBALS['XQDB_ALLOWED_FUNCTIONS']) == false) {
        PEAR::raiseError("Es ist nicht erlaubt die Funktion '" . $binding['LocalPart'] . "' zu benützen!");
      }
      $function .= "return " . $binding['LocalPart'] . "(" . (isset($arguments[0]) ? "__toString(" 
                .  implode("), __toSrring(", $arguments) . ")" : "") . ");" . CODE_SEP . "}";
    } else {
      $function .= "return " . $this->enclosedExpr() . ";" . CODE_SEP . "}";
      $function = $this->instructions . CODE_SEP . $function;
    }

    /* Funktion registrieren */
	  $this->variables = $variables;
	  $this->instructions = $instructions;
    $this->functions[] = array("Name" => $name['LocalPart'], "Body" => $function);
	}

  /* Token: EnclosedExpr */
  // EnclosedExpr ::= "{" Expr "}"
	function enclosedExpr() {
	  $this->string("{", true);
    $expr = $this->expr();
	  $this->string("}", true);
	  return $expr;
	}
  
  /* Token: Expr */
  // Expr ::=  ExprSingle ( "," ExprSingle )*
	function expr($context = "array()") {
    $instruction = $this->exprSingle($context);
  	if ($this->string(",", false, false)) {
      $expr = array($instruction);
      while ($this->string(",")) {
        $expr[] = $this->exprSingle($context);
    	}
  	  return "array_merge(" . implode(", ", $expr) . CODE_SEP . ")";
  	} else {
  	  return $instruction;
  	}
	}
	
  /* Token: ExprSingle */
  // ExprSingle ::= FLWORExpr
  //              | IfExpr
  //              | InsertExpr
  //              | ReplaceExpr
  //              | OrExpr
	function exprSingle($context = "array()") {
    if ($this->string("for", false, false) or $this->string("let", false, false)) {
      return $this->FLWORExpr($context);
    } elseif ($this->string("if")) {
      return $this->ifExpr($context);
    } elseif ($this->string("insert")) {
      return $this->insertExpr($context);
    } elseif ($this->string("replace")) {
      return $this->replaceExpr($context);
    } else {
      return $this->orExpr($context);
    }
	}
  
  /* Token: FLWORExpr */
  // FLWORExpr ::= ( ForClause | LetClause )+ ( "where" Expr   )? "return" ExprSingle
	function FLWORExpr($context) {
	  
	  /* Variablen initialisieren */
 	  $scopes = 0;
 	  $variables = $this->variables;
 	  $hash = md5(uniqid(microtime()) . rand());
	  $expr = CODE_SEP . "\$x" . substr($hash, 0, 5) . "=array();";

	  
	  /* For und Let Ausdrücke auswerten */
	  if ($this->string("for", false, false) == false and $this->string("let", false, false) == false) {
      PEAR::raiseError("Der FLWORExpr-Ausdruck muss mit 'for' oder 'let' beginnen!");
	  }
	  
	  /* for und let klauseln auswerten */
	  while ($this->string("for", false, false) or $this->string("let", false, false)) {
      if ($this->string("for")) {
        $expr .= $this->forClause($scopes, $context);
      } elseif ($this->string("let", true)) {
        $expr .= $this->letClause($context);
      }
	  }
	  
	  /* Where-Klausel */
    if ($this->string("where")) {
      $expr .= CODE_SEP . "if(__toBoolean(" . $this->exprSingle("array()") . ")) {";
      $scopes ++;
    }

    /* Return-Expr */
    $this->string("return", true);
    $expr .= CODE_SEP . "\$x" . substr($hash, 0, 5) . "=array_merge(\$x" . substr($hash, 0, 5) . "," . $this->exprSingle("array()") . ")";
    
    /* Klammern schliessen */
    for (; $scopes > 0; $scopes --) {
      $expr .= CODE_SEP . "}";
    }

    /* Funktion erstellen */
    $this->instructions .= CODE_SEP . "function __autoFN_" . $hash . "(){" . $expr . ";" . CODE_SEP . "return \$x" . substr($hash, 0, 5) . ";" . CODE_SEP . "}";
    
    /* Item erstellen und zurückgeben */
 	  $this->variables = $variables;
    return "__autoFN_" . $hash . "()";
	}
	
  /* Token: ForClause */
  // ForClause ::= "for" "$" QName "in" ExprSingle ( "," "$" QName "in" ExprSingle )*
	function forClause(&$scopes, $context) {
	  $for = "";
	  
	  do {
	    /* am Schluss muss eine Klammer mehr zugemacht werden. */
	    $scopes ++;
	    
	    /* Variable finden */
      $this->string("$", true);
      $name = $this->qname("QName", true);
      $this->string("in", true);
      
      /* Schleife erstellen */
      $this->variables[$name['LocalPart']] = "\$GLOBALS['__userVAR_" . $name['LocalPart'] . "']";
      $variable = "\$x" . substr(md5(uniqid(microtime()) . rand()), 0, 5) . "=array();";
      $for .= CODE_SEP . "foreach (" . $this->exprSingle($context) . " as " . $variable . ") {"
           .  CODE_SEP . "\$GLOBALS['__userVAR_" . $name['LocalPart'] . "']=" . $variable . ";";
	  } while ($this->string(","));
	  
	  /* Aufrufe zurückgeben */
	  return $for;
	}
    
  /* Token: LetClause */
  // LetClause ::= "let" "$" QName ":=" ExprSingle ( "," "$" QName ":=" ExprSingle )*
	function letClause($context) {
	  $let = "";
	  do {
	    /* Variable finden */
      $this->string("$", true);
      $name = $this->qname("QName", true);
      $this->string(":=", true);
      $this->variables[$name['LocalPart']] = "\$GLOBALS['__userVAR_" . $name['LocalPart'] . "']";
      $let .= CODE_SEP . "\$GLOBALS['__userVAR_" . $name['LocalPart'] . "']=" . $this->exprSingle($context) . ";";
	  } while ($this->string(","));
	  return $let;
	}

  /* Token: IfExpr */
  // IfExpr ::= "if" "(" ExprSingle ")" "then" ExprSingle "else" ExprSingle
	function ifExpr($context) {
	  
	  /* Parameter finden */
    $this->string("(", true);
    $test = $this->exprSingle($context);
    $this->string(")", true);
    $this->string("then", true);
    $true = $this->exprSingle($context);
    $this->string("else", true);
    $false = $this->exprSingle($context);

    /* Code erstellen */
	  $hash = md5(uniqid(microtime()) . rand());
    $this->instructions .= CODE_SEP . "function __autoFN_" . $hash . "(\$test){" . CODE_SEP . "if(\$test){" 
                        .  CODE_SEP . "return " . $true . ";" . CODE_SEP . "}else{" . CODE_SEP . "return " . $false . ";" . CODE_SEP . "}}";
    return "__autoFN_" . $hash . "(__toBoolean(" . $test . "))";
	}
	
  /* Token: InsertExpr */
  // InsertExpr ::= "insert" Expr ( "into" | "before" | "after" ) ExprSingle
	function InsertExpr($context) {
	  
	  /* Parameter finden */
    $source = $this->expr($context);
    if ($this->string("into")) {
      $mode = "into";
    } elseif ($this->string("before")) {
      $mode = "before";
    } elseif ($this->string("after", true)) {
      $mode = "after";
    }
    $dest = $this->exprSingle($context);

    /* Auswertung erstellen und zurückgeben */
    return CODE_SEP . "__insert(" . $source . "," . $dest . "," . CODE_SEP . "'" . $mode . "')";
	}
	
  /* Token: ReplaceExpr */
  // ReplaceExpr ::= "replace" Expr "with" ExprSingle
	function replaceExpr($context) {
	  
	  /* Parameter finden */
    $dest = $this->expr($context);
    $this->string("with", true);
    $source = $this->exprSingle($context);

    /* Auswertung erstellen und zurückgeben */
    return CODE_SEP . "__replace(" . $source . "," . $dest . ")";
	}
  
  /* Token: OrExpr */
  // OrExpr ::=  AndExpr ( "or" AndExpr )*
	function orExpr($context) {
	  return $this->andExpr($context);
    if ($this->string("or")) {
      $arg2 = $this->andExpr();
      return "__token_or(" . $arg1 . "," . $arg2 . ")";
    } else {
      return $arg1;
    }
	}
	
  /* Token: AndExpr */
  // AndExpr ::= ComparisonExpr ( "and" ComparisonExpr )*
	function andExpr($context) {
	  $arg1 = $this->comparisonExpr($context);
    if ($this->string("and")) {
      return "__andExpr(" . $arg1 . "," . $this->comparisonExpr($context) . ")";
    } else {
      return $arg1;
    }
	}

  /* Token: ComparisonExpr */
  // ComparisonExpr ::= RangeExpr ( (ValueComp | GeneralComp | NodeComp) RangeExpr )?
  // GeneralComp 	  ::= "=" | "!=" | "<" | "<=" | ">" | ">="
  // ValueComp 	    ::= "eq" | "ne" | "lt" | "le" | "gt" | "ge"
  // NodeComp 	    ::= "is" | "<<" | ">>"
	function comparisonExpr($context) {
	  
	  /* 1. Argument suchen */
	  $firstArg = $this->rangeExpr($context);

	  /* Operatoren abchecken */
    if (isset($this->query[$this->position + 1]) and in_array(substr($this->query, $this->position, 2), 
        array("!=", "<=", ">=", "eq", "ne", "lt", "le", "gt", "ge", "is", "<<", ">>"))) {
      $operator = substr($this->query, $this->position, 2);
    } elseif (isset($this->query[$this->position]) and strpos("=<>", $this->query[$this->position]) !== false) {
      $operator = $this->query[$this->position];
    } else {
      return $firstArg;
    }
    $this->string($operator, true);

    /* 2. Argument suchen und Resultat zurückgeben */
    return CODE_SEP . "__comparison(" . $firstArg . "," . $this->rangeExpr($context). ",'" . $operator . "')";
	}

  /* Token: RangeExpr */
  // RangeExpr ::= AdditiveExpr ( "to"  AdditiveExpr )?
	function rangeExpr($context) {
	  return $this->additiveExpr($context);
    if ($this->string("to")) {
      return "__token_rangeExpr(" . $arg1 . "," . $this->additiveExpr() . ")";
    }
	}

  /* Token: AdditiveExpr */
  // AdditiveExpr ::= MultiplicativeExpr ( ("+" | "-") MultiplicativeExpr )*
	function additiveExpr($context) {
	  return $this->multiplicativeExpr($context);

    /* Weitere Argumente suchen */
    if (isset($this->query[$this->position]) and strpos("+-", $this->query[$this->position]) !== false) {
  	  while (strpos("+-", $this->query[$this->position]) !== false) {
        $args[] = "'" . $this->query[$this->position] . "'";
        $this->string($this->query[$this->position]);
        $args[] = $this->multiplicativeExpr();
        return "__token_additiveExpr(" . implode(",", $args) . ")";
  	  }
    } 
    return $args[0];
	}
        
  /* Token: MultiplicativeExpr */
  // MultiplicativeExpr ::= UnionExpr ( ("*" | "div" | "idiv" | "mod") UnionExpr )*
	function multiplicativeExpr($context) {

	  /* erstes Argument suchen */
	  return $this->unionExpr($context);
	  
    /* Weitere Argumente suchen */
    $firstRun = true;
    while (true) {
      if ($this->string("*")) {
        $args[] = "'*'";
      } elseif ($this->string("div")) {
        $args[] = "'div'";
      } elseif ($this->string("idiv")) {
        $args[] = "'idiv'";
      } elseif ($this->string("mod")) {
        $args[] = "'mod'";
      } elseif ($firstRun) {
        return $args[0];
      } else {
        break;
      }
      $args[] = $this->unionExpr();
      $firstRun = false;
    }
    return "__token_multiplicativeExpr(" . implode(",", $args) . ")";
	}
    
  /* Token: UnionExpr */
  // UnionExpr ::= IntersectExceptExpr ( ("union" | "|") IntersectExceptExpr )*
	function unionExpr($context) {

	  /* erstes Argument suchen */
	  return $this->intersectExceptExpr($context);
	  	  
    /* Weitere Argumente suchen */
    if ($this->string("|", false, false) or $this->string("union", false, false)) {
      while ($this->string("|") or $this->string("union")) {
        $args[] = $this->intersectExceptExpr();
      }
      return "__token_unionExpr(" . implode(",", $args) . ")";
    } else {
      return $args[0];
    }
	}
        
  /* Token: IntersectExceptExpr */
  // IntersectExceptExpr ::= InstanceofExpr ( ("intersect" | "except") InstanceofExpr )*
	function intersectExceptExpr($context) {
	  
	  /* erstes Argument suchen */
	  return $this->instanceofExpr($context);

    /* Weitere Argumente suchen */
    $firstRun = true;
    while (true) {
      if ($this->string("intersect")) {
        $args[] = "'intersect'";
      } elseif ($this->string("except")) {
        $args[] = "'except'";
      } elseif ($firstRun) {
        return $args[0];
      } else {
        break;
      }
      $args[] = $this->instanceofExpr();
      $firstRun = false;
    }
    return "__token_intersectExceptExpr(" . implode(",", $args) . ")";
	}

  /* Token: InstanceofExpr */
  // InstanceofExpr ::= TreatExpr ( "instance" "of" SequenceType )?
	function instanceofExpr($context) {
	  return $this->treatExpr($context);
	  
    if ($this->string("instance")) {
      $this->string("of", true);
      $this->sequenceType();
    }

    return $arg;
	}
        
  /* Token: TreatExpr */
  // TreatExpr ::= CastableExpr ( "treat" "as" SequenceType )?
	function treatExpr($context) {
	  return $this->castableExpr($context);
	  
    if ($this->string("treat")) {
      $this->string("as", true);
      $this->sequenceType();
    }

    return $arg;
	}
        
  /* Token: CastableExpr */
  // CastableExpr ::= CastExpr ( "castable" "as" SingleType )?
  // SingleType   ::= AtomicType "?"?
	function castableExpr($context) {
	  return $this->castExpr($context);
	  
    if ($this->string("castable")) {
      $this->string("as", true);
      $this->atomicType();
      $this->string("?");
    }

    return $arg;
	}        

	/* Token: CastExpr */
  // CastExpr   ::= UnaryExpr ( "cast" "as" SingleType )?
  // SingleType ::= AtomicType "?"?
	function castExpr($context) {
	  return $this->unaryExpr($context);
	  
    if ($this->string("cast")) {
      $this->string("as", true);
      $this->atomicType();
      $this->string("?");
    }

    return $arg;
	}
	
  /* Token: UnaryExpr */
  // UnaryExpr ::= ("-" | "+")* PathExpr
	function unaryExpr($context) {
	  return $this->pathExpr($context);
	  
	  
    $sign = 1;
    while (strpos("+-", $this->query[$this->position]) !== false) {
      if ($this->string("-")) {
        $sign *= -1;  
      } elseif ($this->string("+", true));
    }

    /* Auwertung */
    if ($sign == -1) {
      return "__token_unaryExpr(" . $this->pathExpr() . ")";
    } else {
      return $this->pathExpr();
    }
	}
	
  /* Token: PathExpr */
  // PathExpr ::= ("/" RelativePathExpr?)
  //            | ("//" RelativePathExpr)
  //            | RelativePathExpr
	function pathExpr($context) {
    if ($this->string("//")) {
      return "__token_pathExpr('//', " . $this->relativePathExpr($context) . ")";
    } elseif ($this->string("/")) {
      return "__token_pathExpr('/', " . $this->relativePathExpr($context) . ")";
    } else {
      return $this->relativePathExpr($context);
    }
	}

  /* Token: RelativePathExpr */
  // RelativePathExpr ::= StepExpr (("/" | "//") StepExpr)*
	function relativePathExpr($context) {
	  $step = $this->stepExpr($context);
	  if (isset($this->query[$this->position]) and $this->query[$this->position] == "/") {
      while (isset($this->query[$this->position]) and $this->query[$this->position] == "/") {
        if ($this->string("//")) {
          $step = CODE_SEP . "__axisStep(" . $step . ",'descendant-or-self','node()')";
        } elseif ($this->string("/", true));
        $step = $this->stepExpr($step);
  	  }
	  }
    return $step;
	}
	
  /* Token: StepExpr */
  // StepExpr        ::= ( AxisStep | PrimaryExpr ) PredicateList
  // PrimaryExpr     ::= Literal | VarRef | ParenthesizedExpr | ContextItemExpr | FunctionCall | Constructor
  // VarRef          ::= "$" VarName
  // VarName         ::= QName
  // ContextItemExpr ::= "."
  // PredicateList ::= Predicate*
  // Predicate     ::= "[" Expr "]"
	function stepExpr($context = "array()") {

	  /* Literal */
    if (($argument = $this->literal()) !== false) {
      $step = CODE_SEP . "array(\$GLOBALS['XQDB_Storage']->registerItem(new AtomicValue('" . preg_replace("/'/", "\\'", $argument['Value']) 
          . "','" . $argument['Type'] . "')))";

    /* VarRef */
    } elseif ($this->string("$")) {
      $name = $this->qname("QName", true);
      if (isset($this->variables[$name['LocalPart']]) == false) {
      	PEAR::raiseError("Die Variable '" . $name['LocalPart'] . "' wurde nicht definiert!");
      }
      $step = $this->variables[$name['LocalPart']];
      
    /* ParenthesizedExpr */
    } elseif ($this->query[$this->position] == "(") {
      $step = $this->parenthesizedExpr($context);

    /* ContextItemExpr */
    } elseif ($this->query[$this->position] == "." and $this->query[$this->position + 1] != ".");

    /* DirectConstructor */
    elseif ($this->query[$this->position] == "<") {
      $step = $this->constructor("direct");

    /* AxisStep */
    } elseif (substr($this->query, $this->position, 2) == ".." or $this->query[$this->position] == "@" or $this->query[$this->position] == "*"){
       $step = $this->axisStep($context);
       
    } else{      
      $qname = $this->qname("QName", true, false);

      /* ComputedConstructor */
      if (isset($this->query[$qname['Position']]) and $this->query[$qname['Position']] == "{" and $qname['Prefix'] === null) {
        if (in_array(strtolower($qname['LocalPart']), array("document", "element", "attribute", "text", "comment", "processing-instruction")) == false) {
          PEAR::raiseError("Syntaxfehler bei '" . substr($this->query, $this->position, 32) . "' <br />\n Der indirekte Konstruktor '" 
              . $qname['LocalPart'] . "' ist nicht erlaubt!");
        }
        $step = $this->constructor("computed");
      } elseif ($qname['Prefix'] === null
          and (in_array(strtolower($qname['LocalPart']), array("element", "attribute")) 
          and is_array($this->qname("QName", false, false)))) {
        $step = $this->constructor("computed");

      /* FunctionCall */
      } elseif (isset($this->query[$qname['Position']]) and $this->query[$qname['Position']] and $this->query[$qname['Position']] == "("
          and in_array($qname['LocalPart'], array("document-node", "element", "attribute", "processing-instruction", "comment", "text", "node")) == false) {
        $step = $this->functionCall($context);
      
      /* AxisStep */
      } else {
        $step = $this->axisStep($context);
      }
    }
    
    /* PredicateList */
    if (isset($this->query[$this->position]) and $this->query[$this->position] == "[") {
      while ($this->string("[")) {
        $variable = "\$x" . substr(md5(uniqid(microtime()) . rand()), 0, 5);
    	  $hash = md5(uniqid(microtime()) . rand());
      	$step = CODE_SEP . "\$sequence=array();"
      	      .  CODE_SEP . "foreach(" . $step . " as \$key=>" . $variable . ") {";
        if (preg_match('/^((\d+)\s*)/', substr($this->query, $this->position), $match) and $this->query[$this->position + strlen($match[1])] == "]") {
          $literal = $this->literal("IntegerLiteral", true);
          $step .= CODE_SEP . "if(__toBoolean(" . CODE_SEP . "__comparison("
                .  CODE_SEP . "__functionCall('" . ($this->infos['Type'] == "Libary" ? $this->infos['Value'] : "" ) . "','position',"
                .  "array(\$key=>" . $variable . "))," .  CODE_SEP . "array(\$GLOBALS['XQDB_Storage']->registerItem(new AtomicValue('" 
                .  $literal['Value'] . "','" . $literal['Type'] . "'))),'='))){";
        } else {
          $step .= CODE_SEP . "if(__toBoolean(" . $this->expr("array(\$key=>" . $variable . ")") . ")){";
        }
        $this->instructions .= CODE_SEP . "function __autoFN_" . $hash . "(){" . $step . CODE_SEP . "\$sequence[]=" . $variable . ";" . CODE_SEP . "}}"
                            .  CODE_SEP . "return \$sequence;" . CODE_SEP . "}";
        $step = CODE_SEP . "__autoFN_" . $hash . "()";
        $this->string("]", true);
      }
    }
    
    /* Code zurückgeben */
    return $step;
  }

  /* Token: ParenthesizedExpr */
  // ParenthesizedExpr ::= "(" Expr? ")"
	function parenthesizedExpr($context) {
    $this->string("(", true);
    if ($this->string(")")) {
      return CODE_SEP . "array()";
    } else {
      $expr = $this->expr($context);
      $this->string(")");
      return $expr;
    }
	}
	
  /* Token: AxisStep */
  // AxisStep          ::= (ForwardStep | ReverseStep) 
  // ForwardStep       ::= (ForwardAxis NodeTest) | AbbrevForwardStep
  // ForwardAxis       ::= ("child" "::")
  //                     | ("descendant" "::")
  //                     | ("attribute" "::")
  //                     | ("self" "::")
  //                     | ("descendant-or-self" "::")
  //                     | ("following-sibling" "::")
  //                     | ("following" "::")
  // AbbrevForwardStep ::= "@"? NodeTest
  // ReverseStep       ::= (ReverseAxis NodeTest) | AbbrevReverseStep
  // ReverseAxis       ::= ("parent" "::")
  //                     | ("ancestor" "::")
  //                     | ("preceding-sibling" "::")
  //                     | ("preceding" "::")
  //                     | ("ancestor-or-self" "::")
  // AbbrevReverseStep ::= ".."
  // NodeTest          ::= KindTest | NameTest
  // NameTest          ::= QName | Wildcard
  // Wildcard          ::= "*"
	function axisStep($context) {
	  
	  /* Achse suchen */
	  $axis = "child";

	  /* AbbrevForwardStep */
    if ($this->string("@")) {
      $axis = "attribute";
    
    /* AbbrevReverseStep */
    } elseif ($this->string("..")) {
      return CODE_SEP . "__axisStep(" . $context . ",'parent','node()')";
      
    /* Wildcard */
    } elseif ($this->query[$this->position] == "*");
    
    /* andere Achsen */
    else {
      $qname = $this->qname("QName", true, false);
      if ($qname['Prefix'] === null and substr($this->query, $qname['Position'], 2) == "::") {
        if (in_array(strtolower($qname['LocalPart']), array("child", "descendant", "attribute", "self", "descendant-or-self", "following-sibling", 
            "following", "parent", "ancestor", "preceding-sibling", "preceding", "ancestor-or-self")) == false) {
          PEAR::raiseError("Syntaxfehler bei '" . substr($this->query, $this->position, 32) . "' <br />\n Die Achse '" . $qname['LocalPart'] 
              . "' ist nicht erlaubt!");
        }
        $this->qname("NCName");
        $axis = strtolower($qname['LocalPart']);
      }
    }
    
    /* NodeTest, Wildcard */
    if ($this->string("*")) {
      $nodeTest = "*";            
    } else {
      $qname = $this->qname("QName", true, false);
      
      /* KindTest */
      if ($qname['Prefix'] === null and substr($this->query, $qname['Position'], 1) == "(") {
        $kindTest = $this->kindTest();
        $nodeTest = strtolower($kindTest['Name']) . "()";
      
      /* NameTest */
      } else {
        $nodeTest = $qname['LocalPart'];
        $this->qname("QName", true);
      }
    }
    
    /* Pfad zurückgeben */
    return CODE_SEP . "__axisStep(" . $context . ",'" . $axis . "','" . $nodeTest . "')";
	}

  /* Token: FunctionCall */
  // FunctionCall ::= QName "(" (ExprSingle ("," ExprSingle)* )? ")"
	function functionCall($context) {
    $name = $this->qname("QName", true);
    $this->string("(", true);
    
    /* Argumente suchen */
    $args = array();
    if ($this->string(")") == false) {
      do {
     	  $args[] = $this->exprSingle($context);
      } while ($this->string(","));
      $this->string(")", true);
    }

    /* Funktionsauf erstellen */
    return CODE_SEP . "__functionCall('" . ($this->infos['Type'] == "Libary" ? $this->infos['Value'] : "" ) . "','" .  $name['LocalPart'] . "',"
        . $context . (isset($args[0]) ? "," . implode(",", $args) : "") . ")";
  }
 
  /* Token: Constructor */
  // Constructor     ::= DirectConstructor
  //                   | ComputedConstructor
  function constructor($type) {
    if ($type == "direct") {
    	$fnName = "__autoFN_" . md5(uniqid(microtime()) . rand()) . "()";
      $this->instructions .= CODE_SEP . "function " . $fnName . " {" 
                          .  $this->directConstructor() .  CODE_SEP . "return array(" . $this->root . ");" . CODE_SEP . "}";
    	return CODE_SEP . $fnName;
    } else {
    	return $this->computedConstructor();
    }
  }

  /* Token: ComputedConstructor */
  // ComputedConstructor ::= CompDocConstructor
  //                       | CompElemConstructor
  //                       | CompAttrConstructor
  //                       | CompTextConstructor
  //                       | CompCommentConstructor
  //                       | CompPIConstructor
  // CompDocConstructor ::= "document" "{" Expr "}"
  // CompElemConstructor ::= (("element" QName "{") | ("element" "{" Expr "}" "{")) ContentExpr? "}"
  // ContentExpr ::= Expr
  // CompAttrConstructor ::= (("attribute" QName "{") | ("attribute" "{" Expr "}" "{")) Expr? "}"
  // CompTextConstructor ::= "text" "{" Expr "}"
  // CompCommentConstructor ::= "comment" "{" Expr "}"
  // CompPIConstructor ::= (("processing-instruction" NCName "{") | ("processing-instruction" "{" Expr "}" "{")) Expr? "}"
	function computedConstructor() {

	  /* Type des Konstruktor suchen*/
	  $qname = $this->qname("NCName", true);
    if (in_array(strtolower($qname['LocalPart']), array("document", "element", "attribute", "text", "comment", "processing-instruction")) == false) {
      PEAR::raiseError("Syntaxfehler bei '" . substr($this->query, $this->position, 32) . "' <br />\n Der indirekte Konstruktor '" 
        . $qname['LocalPart'] . "' ist nicht erlaubt!");
    }
    
    /* Ausdruck suchen */
    $expr1 = null;
    $expr2 = null;
    if ($this->string("{") == false) {
      if (in_array(strtolower($qname['LocalPart']), array("document", "text", "comment"))) {
        PEAR::raiseError("Auf Dokument-, Text-, und Kommentarknoten dürfen keine QNames folgen!");
      }
      $name = $this->qname("QName", true);
      $expr1 = CODE_SEP . "__token_qName('". $name['LocalPart'] . "')";
      $this->string("{", true);
    } else {
      $expr1 = $this->expr();
      $this->string("}", true);
    }
    if (in_array(strtolower($qname['LocalPart']), array("element", "attribute", "processing-instruction"))) {
      $this->string("{", true);
      if ($this->string("}") == false) {
        $expr2 = $this->expr();
        $this->string("}", true);
      } else {
        $expr2 = "__token_newSequence()";
      }
    }

    /* Objekte erzeugen */
    switch ($qname['LocalPart']) {
      case "document":
        return "__token_constructorDocument(" . $expr1 . ")";
      break;
      case "element":
        return "__token_constructorElement(" . $expr1 . "," . $expr2 . ")";
      break;
      case "attribute":
        return "__token_constructorAttribute(" . $expr1 . "," . $expr2 . ")";
      break;
      case "text":
        return "__token_constructorText(" . $expr1 . ")";
      break;
      case "comment":
        return "__token_constructorComment(" . $expr1 . ")";
      break;
      case "processing-instruction":
        return "__token_constructorPI(" . $expr1 . "," . $expr2 . ")";
      break;
    }    
    return PEAR::raiseError("Interner Fehler bei '" . substr($this->query, $this->position, 32) . "'!");
	}
}
?>