<?PHP

/* Klasse, die alle Elemente verwaltet. */
class Storage 
{
  /* Cache mit allen bis jetzt geladenen Elementen. */
  var $cache;
  
  /* Liste mit allen Geladenen Dokumenten */
  var $doc;
  
  /* Array von Knoten, die veränder wurden */
  var $changelog;
  
  /* Der Wert true */
  var $true;
  
  /* Der Wert false */
  var $false;
  
  /* Konstruktor der Klasse */
  function Storage() {
    
    /* Zufallszahlgenerator */
    $microtime = explode(" ", microtime());
    srand(intval($microtime[1]));

    /* Variablen initialisieren */
    $this->cache = array();
    $this->doc = array();
    $this->changelog = array();
    $this->true = array($this->registerItem(new AtomicValue(true, "xs:boolean")));
    $this->false = array($this->registerItem(new AtomicValue(false, "xs:boolean")));
  }
  
  /* Gibt den Wert True zurück 
     int[] return: Boolscher Wert True
  */
  function boolTrue() {
    return $this->true;
  }
  
  /* Gibt den Wert False zurück 
     int[] return: Boolscher Wert False
  */
  function boolFalse() {
    return $this->false;
  }
  
  /* Achse ausführen
     string  $id: ID des gesuchten Knoten
     string  $axis: Achse die ausgeführt werden soll
     string[] return: Neue Sequenz
   */
  function axis($id, $axis) {
  
    /* Falls Knoten nicht Existiert */
    if (isset($this->cache[$id]) == false) {
    	PEAR::raiseError("Der gesuchte Knoten existiert nicht!");
    }
    
    /* Achse auswerten und zurückgeben */
    switch ($axis) {
      case "attribute":  return $this->cache[$id]->axis("attribute"); break;
      case "child": return $this->cache[$id]->axis("child"); break;
      case "descendant": return $this->cache[$id]->axis("descendant"); break;
      case "self":  return array($id); break;
      case "descendant-or-self":  array_merge(array($id), $this->cache[$id]->axis("descendant")); break;
      case "following-sibling":
        return array_slice($this->cache[$this->cache[$id]->axis("parent")]->axis("child"),
               array_search($id, $this->cache[$this->cache[$id]->axis("parent")]->axis("child")) + 1);
      break;      
      case "following":
        for ($sequence = array(); $this->cache[$id]->axis("parent") != null; $id = $this->cache[$id]->axis("parent")) {
          $sequence = array_merge($sequence, array_slice($this->cache[$this->cache[$id]->axis("parent")]->axis("child"), 
                      array_search($id, $this->cache[$this->cache[$id]->axis("parent")]->axis("child"))));
        }
        return array_merge($sequence, $this->cache[$id]->axis("parent"));
      break;      
      case "preceding-sibling":
        $sequence = $this->cache[$this->cache[$id]->axis("parent")]->axis("child");
        array_reverse($sequence);
        return array_slice($this->cache[$this->cache[$id]->axis("parent")]->axis("child"),
               array_search($id, $this->cache[$this->cache[$id]->axis("parent")]->axis("child")) + 1);
      break;      
      case "preceding":
        for ($sequence = array(); $this->cache[$id]->axis("parent") != null; $id = $this->cache[$id]->axis("parent")) {
          $step = array();
          foreach ($this->cache[$this->cache[$id]->axis("parent")]->axis("child") as $child) {
            if ($child == $id) {
            	break;
            }
            $step[] = $id;
          }
          $sequence = array_merge($sequence, array_reverse($step, true));
        }
        return array_merge($sequence, $this->cache[$id]->axis("parent"));      
      case "parent":  return array($this->cache[$id]->axis("parent")); break;
      case "ancestor":
        for ($sequence = array(); ($sequence[] = $this->cache[$id]->axis("parent")) != null; $id = $this->cache[$id]->axis("parent"));
        array_pop($sequence);
        return array_reverse($sequence);
      break;
      case "ancestor-or-self":
        for ($sequence = array($id); ($sequence[] = $this->cache[$id]->axis("parent")) != null; $id = $this->cache[$id]->axis("parent"));
        array_pop($sequence);
        return array_reverse($sequence);
      default:
        $this->raiseError("Die Achse '" . $axis . "' ist nicht erlaubt!", E_SYNTAX);
      break; 
    }
  }
  
  /* Step auswerten
     string $id: Sequenz, mit den Kontextknoten
     string $axis: Achse, die ausgeführt werden soll
     string $nodeTest: NodeTest, der ausgeführt werden soll
     string[] return Sequenz mit den neuen Knotextknoten
   */
  function axisStep($id, $axis, $nodeTest) {
    $sequence = array();
  	foreach ($this->axis($id, $axis) as $item) {
  	  switch ($nodeTest) {
  	    
  	    /* Dokumentknoten */
        case "document-node()":
          PEAR::raiseError("Der NodeTest 'document-node' wird nicht unterstützt!");
          
        /* Whildcard */
        case "*":
          if ($axis == "attribute") {
            if (get_class($this->cache[$item]->properties()) == "attribute") {
              $sequence[] = $item;
            }
          	break;
          }
        case "element()":
          if (get_class($this->cache[$item]->properties()) == "element") {
            $sequence[] = $item;
          }
        break;
        
        /* Nur PI */
        case "processing-instruction()":      
          PEAR::raiseError("Der NodeTest 'processing-instruction' wird nicht unterstützt!");
          
        /* Texte und Kommentare */
        case "comment()":      
        case "text()":
          if (get_class($this->cache[$item]->properties()) == substr($nodeTest, 0, -2)) {
            $sequence[] = $item;
          }
        break;
        
        /* Alle Knoten */
        case "node()":
          if (in_array(get_class($this->cache[$item]->properties()), array("element", "text", "comment", "pi"))) {
            $sequence[] = $item;
          }
        break;
        
        /* Step mit Namen */
        default:
          $body = $this->cache[$item]->properties();
          if ($axis == "attribute") {
            if (get_class($this->cache[$item]->properties()) == "attribute" and $body->nodeName == $nodeTest) {
              $sequence[] = $item;
            }
          } elseif (get_class($this->cache[$item]->properties() == "element") and $body->nodeName == $nodeTest) {
            $sequence[] = $item;
          }
      	break;
  		}
  	}
  	
  	/* Sequenz zurückgeben */
  	return $sequence;
  }
  
  /* Knoten selbst zurückgeben
     string     $id: ID des Knotens, der zurückgegeben werden soll
     AtomicValue return: Werte des Knotens
   */
  function getObject($id) {
    
    /* Falls Knoten nicht Existiert */
    if (isset($this->cache[$id]) == false) {
    	PEAR::raiseError("Der gesuchte Knoten existiert nicht!");
    }
    
    /* Werte zurückgeben */
    return $this->cache[$id]->properties();
  }
  
  /* Gibt den URI eines Knotens zurück
     string $id: ID des Knotens, dessen URI zurückgegeben werden soll
     stirng  return: URI des Knotens
   */
  function getURI($id) {
    
    /* Falls Knoten nicht Existiert */
    if (isset($this->cache[$id]) == false) {
    	PEAR::raiseError("Der gesuchte Knoten existiert nicht!");
    }
    
    /* Werte zurückgeben */
    return $this->cache[$id]->storage() . "://" . $this->cache[$id]->uri();
  }
  
  /* Prüft ob ein Item bereits geändert wurde und setzt je nach dem den Log - Array
     string $id: ID des Knotens der upgedatet werden soll.
     string $target: Gibt an, was geändert wurde
   */
  function registerUpdate($id, $target) {
    if (isset($this->changelog[$id])) {
      switch ($this->changelog[$id]['Action']) {
      	case "renew":
      	case "update":
      	  $this->changelog[$id] = array('Action' => "renew");
      	break;
      	case "insert":
      	  $this->changelog[$id] = array('Action' => "insert");
      	break;
      	default:
      	  PEAR::raiseError("Der Knoten existiert nicht mehr!");
      }
    } elseif ($target == "all") {
      $this->changelog[$id] = array('Action' => "renew");
    } else {
      $this->changelog[$id] = array('Action' => "update", 'Target' => $target);
    }
  }

  /* Encodet den URI eines Dokuments
     string  $uri: URI des Dokuments
     string[] return: Array mit den Komponenten des URI
   */
  function encodeURI($uri) {
    if (preg_match("/^(\w*):\/\/(.*)$/", $uri, $parts)) {
      if (strtolower($parts[1]) == "fs") {
      	$parts[2] = realpath($parts[2]);
      }
      return array('Storage' => strtolower($parts[1]), 'URI' => $parts[2]);
    } else {
      return array('Storage' => "file", 'URI' => realpath($uri));
    }
  }
  
  /* Überprüft ob ein Dokument bereits existiert 
     string $uri. Pfad des Dokuments
     boolean return: TRUE, falls das Dokument existiert, sonst false
   */
  function isDoc($uri) {
    /* URI encoden */   
    $encodedURI = $this->encodeURI($uri);

    /* Resultat zurückgeben */
    return isset($this->doc[$encodedURI['Storage'] . "://" . $encodedURI['URI']]);
  }
  
  /* Gibt die ID's der Wurzelknoten des Dokuments zurück
     string $uri: Pfad zum Dokument
     string[] return Wurzelknoten des Dokuments
   */
  function doc($uri) {
    
    /* URI encoden */   
    $encodedURI = $this->encodeURI($uri);
    
    /* Falls das Dokument noch nicht geladen ist */
    if (isset($this->doc[$encodedURI['Storage'] . "://" . $encodedURI['URI']]) == false) {
      
      /* Dokument laden */
      switch ($encodedURI['Storage']) {
        case "file":
          if (($fp = @fopen($encodedURI['URI'], 'r')) == false) {
            PEAR::raiseError("Die Datei '" . $encodedURI['URI'] . "' kann nicht geöffnet werden!");
          }
          $buffer = fread($fp, filesize($encodedURI['URI']));
          fclose($fp);
          
          /* DocReader laden */
          static $docReader;
          if ($docReader == null) {
          	include_once("docreader.php");
          	$docReader =& new DocReader();
          }
          $docReader->createDom($encodedURI['URI'], $buffer);        
        break;
        
        /* Dokument aus Datenbank laden */
        case "db":
      	  $tupel = __executeSQL("node_select_document", array($encodedURI['URI']), true);
      	  if (isset($tupel[0]) == false) {
            PEAR::raiseError("Das Dokument '" . $encodedURI['URI'] . "' existiert nicht!");
      	  }
      	  
   	      /* Wurzelknoten erstellen */
          $id = $this->registerItem(new Document($tupel[0]['documentURI'], $tupel[0]['stringValue'], $tupel[0]['typeName'], $tupel[0]['typedValue'])
              , "db", $tupel[0]['tableName'], $tupel[0]['id'], 0, null, null, null);
          $this->doc["db://" . $encodedURI['URI']] = $id;
        break;
        
        /* Andere Storage können nicht geladen werden */
        default:
    	    PEAR::raiseError("Der Stroage '" . $encodedURI['Storage'] . "' wird nicht unterstützt!");
      }
    }
    
    /* Wurzelknoten zurückgeben */
    return array($this->doc[$encodedURI['Storage'] . "://" . $encodedURI['URI']]);
  }
  
  /* Fügt dem Cache ein Element dazu
     AtomicValue $item: Item, das Registeriert werden soll
     string $storage: Gibt den Speicherort des Knotens an
     string $path: Pfad des Dokuments, in dem der Knoten gespeichert ist
     string $parent: ID des Vaterelements
     string[] $child: Kinderelemente des Knotens
     string[] $descendant: Nachkommen des Knotens
     string[] $attribute: Attribute des Knotens
     string return: Gibt die ID des Items zurück
   */
  function registerItem($item, $storage = "tmp", $path = "null", $id = null, $parent = "0", $child = array(), $descendant = array(), $attribute = array()) {
    if ($id == null) {
      $id = md5(uniqid(microtime()) + rand());
    }
    $this->cache[$id] = new Cache($id, get_class($item), $storage, $path, array_merge(
      ($parent !== null ? array('parent' => 0) : array()), 
      ($child !== null ? array('child' => array()) : array()),
      ($descendant !== null ? array('descendant' => array()) : array()),
      ($attribute !== null ? array('attribute' => array()) : array())), $item);
    return $id;
  }
  
  /* Prüft ob ein Item in Cache vorhanden ist und wenn nicht wird es dort registriert
     string $id: ID des Objekts, das registriert werden soll
     string $storage: Gibt den Speicherort des Knotens an
     string $path: Pfad des Dokuments, in dem der Knoten gespeichert ist
   */
  function cacheItem($id, $type, $storage, $path) {
    if (isset($this->cache[$id]) == false) {
      $this->cache[$id] = new Cache($id, $type, $storage, $path, array(), null);
    }
  }
  
  /* Fügt ein Knoten in den Cache ein
     string $id: ID des Objekts, das eingefügt werden soll
     string $parent: ID des Vaterelements des Knotens
     int $order: Position an dem Das Dokument eingefürt werden soll
   */
  function insertItem($id, $parent, $order = -1) {
    
    /* Testen ob Vaterlement existerit */
    if (isset($this->cache[$id]) == false or isset($this->cache[$parent]) == false
        or in_array($this->cache[$parent]->type(), array("element", "document")) == false) {
      PEAR::raiseError("Eines der angegebenen Elemente existiert nicht oder darf kein Vaterelement sein!");    	
    }
    
    /* Dokumentknoten können nicht eingefürt werden */
    if (get_class($this->cache[$id]->properties()) == "document") {
      foreach ($this->cache[$id]->axis("child") as $key => $child) {
      	$this->insertItem($child, $parent, ( $order > 0 ? $order + $key : $order ));
      }

    /* Attribute einfügen */
    } elseif (get_class($this->cache[$id]->properties()) == "attribute") {

      /* Vaterelement updaten */
      $this->cache[$parent]->updateAxis("attribute", array_merge($this->cache[$parent]->axis("attribute"), array($id)));
      $this->registerUpdate($parent, "attribute");
      
      /* Attribute updaten */
      $this->cache[$id]->updateAxis("parent", $parent);
      $this->changelog[$id] = array('Action' => "insert");
    
    /* Andere Knoten einfügen */
    } else {

      /* Kinderelemente festlegen */
      $children = $this->cache[$parent]->axis("child");

      /* Ort bestimmen, an dem das Item eingefügt werden soll */
      while ($order < 0) {
        $order += count($children) + 1;
      }
      if ($order > count($children)) {
      	$order = $order % (count($children) + 1);
      }      
      
      /* Vaterelement updaten */
      array_splice($children, intval($order), 0, $id);
      $this->cache[$parent]->updateAxis("child", $children);
      
      /* Vaterelement setzen */
      $this->cache[$id]->updateAxis("parent", $parent);      
      
      /* Kinderelemente registrieren */
      $descendant = array_merge(array($id), $this->cache[$id]->axis("descendant"));
      foreach ($descendant as $item) {
        foreach ($this->cache[$item]->axis("attribute") as $attribute) {
          $this->changelog[$attribute] = array('Action' => "insert");
        }
        $this->changelog[$item] = array('Action' => "insert");
      }
      
      /* Vorfahren updaten */
      for ($ancestor = $parent; $ancestor != null; $ancestor = $this->cache[$ancestor]->axis('parent')) {
        
        /* StringValue updaten */
        $children = $this->cache[$ancestor]->axis('child');
        $body = $this->cache[$ancestor]->properties();
        $body->stringValue = "";
        $loop = -1;
        foreach ($children as $key => $index) {
          if ($children[$key] == $id) {
          	$loop = $key;
          }
          $child = $this->cache[$children[$key]]->properties();
        	$body->stringValue .= $child->toString();
        }
        if ($loop == -1) {
        	PEAR::raiseError("Interner Fehler!");
        }
        $this->cache[$ancestor]->updateProperties($body);

        /* Nachfolger setzen */
        $descendant = $this->cache[$ancestor]->axis('descendant');
        
        if (in_array($id, $this->cache[$ancestor]->axis('descendant'))) {
          $this->cache[$ancestor]->updateAxis("descendant", array_merge((array_search($children[$loop], $descendant) != 0 ?
              array_slice($descendant, 0, array_search($children[$loop], $descendant)) : array()),
              array($id), $this->cache[$id]->axis('descendant'), (isset($children[$loop + 1]) ? array_slice($descendant, 
              array_search($children[$loop + 1], $descendant)) : array())));
        } elseif ($loop == count($children) - 1) {
          $this->cache[$ancestor]->updateAxis("descendant", array_merge($descendant, array($id), $this->cache[$id]->axis('descendant')));
        } else {
          $this->cache[$ancestor]->updateAxis("descendant", array_merge(($loop == 0 ? array() : array_slice($descendant, 0, 
              array_search($children[$loop + 1], $descendant))), array($id), $this->cache[$id]->axis('descendant'), 
              array_slice($descendant, array_search($children[$loop + 1], $descendant))));
        }
        
        /* Changelog Updaten */
        $this->registerUpdate($ancestor, "all");
        
        /* ID nachrücken */
        $id = $ancestor;
      }
    }
  }
  
  /* Kopiert ein Item mit all seinen Nachfolgen und setzt den angegebenen URI
     string $id: ID des Element's das eingefügt werden soll
     string $uri: Neue URI des Knotens
   */
  function deepCopy($id, $uri) {
    
    /* Testen ob Vaterlement existerit */
    if (isset($this->cache[$id]) == false) {
      PEAR::raiseError("Das angegebene Element existiert nicht!");    	
    }
    
    /* Knoten Kopieren */
    $encodedURI = $this->encodeURI($uri);
    $newID = $this->registerItem($this->cache[$id]->properties(), $encodedURI['Storage'], $encodedURI['URI']);
    
    /* Kinderelemente registrieren */
    $mapping = array(0 => 0, $id => $newID);
    foreach ($this->cache[$id]->axis("descendant") as $item) {
      $newItem = $this->registerItem($this->cache[$item]->properties(), $encodedURI['Storage'], $encodedURI['URI']);
      $mapping[$item] = $newItem;
    }
    
    /* Alte Struktur übernehmen */
    foreach (array_merge($id, $this->cache[$id]->axis("descendant")) as $item) {
      
      /* Attribue übernehmen */
      if (count($this->cache[$item]->axis("attribute")) > 0) {
        $attributes = array();
        foreach ($this->cache[$item]->axis("attribute") as $attribute) {
          $attributes[] = $newAttribute = $this->registerItem($this->cache[$attribute]->properties(), $encodedURI['Storage'], $encodedURI['URI']);
          $this->cache[$newAttribute]->updateAxis("parent", $mapping[$item]);
        }
        $this->cache[$mapping[$item]]->updateAxis("attribute", $attributes);
      }
      
      /* Andere Achsen updaten */
      foreach (array("child", "descendant") as $axis) {
      	$set = $this->cache[$item]->axis($axis);
      	if (count($set) > 0) {
        	$newSet = array();
        	foreach ($set as $axisID) {
        		$newSet[] = $mapping[$axisID];
        	}
          $this->cache[$mapping[$item]]->updateAxis($axis, $newSet);
      	}
      }
      
      /* Vaterelement updaten */
      $this->cache[$mapping[$item]]->updateAxis("parent", $mapping[$this->cache[$item]->axis("parent")]);
    }
    
    /* Neue ID zurückgeben */
    return $newID;
  }  
  /* Erstellt ein Neues Dokument 
     string $uri: URI des neuen Dokuments
  */
  function createDoc($uri) {
    $encodedURI = $this->encodeURI($uri);
    
    /* Prüfen ob das Dokument existiert */
    if (isset($this->doc[$encodedURI['Storage'] . "://" . $encodedURI['URI']])) {
    	PEAR::raiseError("Es existiert bereits ein Dokument mit dem URI '" . $uri . "'!");
    }
    
    /* Je nach Storage uri manipulieren */
    switch ($encodedURI['Storage']) {
      case "db":
        if (count(__executeSQL("node_select_document", array($encodedURI['URI']), true)) == 1) {
        	PEAR::raiseError("Es existiert bereits ein Dokument mit dem Namen '" . $encodedURI['URI'] . "' in der Datenbank!");
        }
        $newURI = preg_replace("/\W/", "_", $encodedURI['URI']) . "_" . substr(md5(uniqid(microtime()) . rand()), 0, 3);
      break;
      default:
        $newURI = $encodedURI['URI'];
    }    
    
    /* Wurzelknoten erstellen */
    $id = $this->registerItem(new Document($encodedURI['URI']), $encodedURI['Storage'], $newURI);
    $this->changelog[$id] = array('Action' => "insert");
    $this->doc[$encodedURI['Storage'] . "://" . $encodedURI['URI']] = $id;
    return array($id);
  }
  
  /* Löscht ein Item mit all seinen Kinderelementen 
     string $id: ID des Knotens, der gelöscht werden soll
  */  
  function delete($id) {
    
    /* überprüfen ob der Knoten existiert */
    if (isset($this->cache[$id]) == false) {
    	PEAR::raiseError("Der angegebene Knoten existiert nicht!");
    }

    /* Knoten und Kinderknoten löschen */
    $parent = $this->cache[$parent]->axis("parent");
    $items = array_merge(array($id), $this->cache[$id]->axis("descendant"));
    foreach ($items as $item) {
      foreach ($this->cache[$item]->axis("attribute") as $attribute) {
      	$this->changelog[$attribute] = array('Action' => "delete", 'URI' => $this->cache[$id]->uri(), 'class' => get_class($this->cache[$id]->properties()));
      	unset($this->cache[$attribute]);
      }
    	$this->changelog[$item] = array('Action' => "delete", 'URI' => $this->cache[$id]->uri(), 'class' => get_class($this->cache[$id]->properties()));
    	unset($this->cache[$item]);
    }
    
    /* Baumstruktur updaten */
    if ($parent != null) {
      $this->cache[$parent]->updateAxis("child", array_intersect($this->cache[$parent]->axis("child"), array($id)));
      $this->registerUpdate($parent, "child");
      
      for ($id = $parent; $id != null; $id = $this->cache[$id]->axis('parent')) {
        $this->cache[$id]->updateAxis("descendant", array_intersect($this->cache[$id]->axis("descendant"), $items));
        $this->registerUpdate($id, "descendant");
      }
    }
  }
  
  /* Aktualisiert den aktuellen Knoten
     string $id: id, die den Neuen Wert bekommen soll
     string $item: ID des neuen Inhalts
   */
  function update($id, $item) {
  
    /* überprüfen ob der Knoten existiert */
    if (isset($this->cache[$id]) == false or isset($this->cache[$item]) == false) {
    	PEAR::raiseError("Der angegebene Knoten existiert nicht!");
    }
    
    /* Knoten updaten */
    $properties = $this->cache[$item]->properties();
    $this->cache[$id]->updateProperties($properties);
    $this->registerUpdate($id, "properties");
  }
  
  /* Speichert die Änderungen, die an einem Dokument gemacht wurden definitiv 
     string $uri: URI des Dokuments, das gespeichert werden soll
   */
  function commit($uri) {

    /* Überprüfen ob das Dokument existiert */
    $properties = $this->cache[$uri]->properties();
    if (isset($this->doc[$this->cache[$uri]->storage() . "://" . $properties->documentURI]) == false) {
    	PEAR::raiseError("Es existiert kein Dokument mit dem URI '" . $uri . "'!");
    }    
    
    /* Knoten Registrieren */
    foreach ($this->changelog as $id => $item) {
    	if ($this->cache[$id]->storage() == $this->cache[$uri]->storage() and $this->cache[$id]->uri() == $this->cache[$uri]->uri()) {
       	switch ($this->cache[$id]->storage()) {
  
       	  /* Dokumente, die aus PHPariablen erstellt wurden */
       	  case "php":
       	    
       	    /* Vorfahren der Variablen suchen */
       	    $ancestor = $this->axis($id, "ancestor");
       	    
       	    /* Zeil bestimmen */
       	    $parts = explode("/", $this->cache[$id]->uri());
       	    $target =& $GLOBALS[$parts[0]];
       	    foreach (array_slice($parts, 1) as $step) {
       	    	$target =& $target[$step];
       	    }

       	    /* Varerelemente laden */
       	    foreach ($ancestor as $node) {
              if ($this->cache[$node]->type() == "element") {
                $properties = $this->cache[$node]->properties();
                $target =& $target[$properties->nodeName];
              }
            }
            
            /* Falls der Ast nicht existiert, Fehler ausgeben */
            if ($target === null) {
              PEAR::raiseError("Commit fehlgeschalgaen");
            }

       	    /* Aktion ausführen */
            $properties = $this->cache[$id]->properties();
       	    if ($item['Action'] == "insert") {
       	      switch ($this->cache[$id]->type()) {
                case "element":
                  $target[$properties->nodeName] = array();
                break;
                case "text":
                  $target = $properties->content;
                break;
                case "atomicvalue":
                  $target = $properties->value;
                break;
       	      }
       	    } elseif ($this->cache[$id]->type() == "atomicvalue") {
              $target = $properties->value;
            }
       	  break;
       	  
      	  /* Dokumente, die in der Datenbank gespeichert sind */
      	  case "db":
  
        	  /* Variablen löschen */
        	  $class = $mode = null;
        	  
            // Aktionen ausführen
        	  switch ($item['Action']) {
        	    
        	    /* Knoten löschen*/
        	    case "renew":
        	      $item['URI'] = $this->cache[$id]->uri();
        	      $item['class'] = get_class($this->cache[$id]->properties());
      	      case "delete":
      	        if (get_class($this->cache[$id]->properties()) == "document") {
        	        __executeSQL("node_delete_document", array($id)); 
      	        } else {   
        	        __executeSQL("node_delete_node", array($item['URI'], $item['class'], $id)); 
      	        }
        	      __executeSQL("node_delete_index_attribute", array($item['URI'], $id));
        	      __executeSQL("node_delete_index_child_by_parent", array($item['URI'], $id));
        	      __executeSQL("node_delete_index_descandant_by_parent", array($item['URI'], $id));
        	    
        	      /* Bei Renew muss der Knoten neu eingefügt werden */
        	      if ($item['Action'] == "delete") {
        	      	break;
        	      }

        	    /* Knoten einfügen */
        	    case "insert":
                $class = get_class($this->cache[$id]->properties());
                $mode = "insert";
              break;
              
              /* Teile des Knotens ändern */
      	      case "update":
      	        switch ($item['Target']) {
                  case "attribute";
          	        __executeSQL("node_delete_index_attribute", array($this->cache[$id]->uri(), $id));
          	        foreach ($this->cache[$id]->axis("attribute") as $attribue) {
            	        __executeSQL("node_insert_index_attribute", array($this->cache[$id]->uri(), $id, $attribue));
          	        }
                  break;
                  case "child";
                  case "descendant";
          	        __executeSQL("node_delete_index_" . $item['Target'] . "_by_parent", array($this->cache[$id]->uri(), $id));
          	        foreach ($this->cache[$id]->axis("child") as $order => $child) {
             	        __executeSQL("node_insert_index_" . $item['Target'], array($this->cache[$id]->uri(), $id, $child,
             	          $order, get_class($this->cache[$id]->properties()), get_class($this->cache[$child]->properties())));
          	        }
          	        // ancestor müssen noch upgedatet werden
                  break;
                  case "properties";
                    $class = get_class($this->cache[$id]->properties());
                    $mode = "update";
                  break;
      	        }
      	      break;
      	      default:
      	        PEAR::raiseError("Die Aktion '" . $item['Action'] . "' ist nicht erlaubt!");
        	  }
        	  
        	  /* Insert und Update ausfürhen */
            if ($mode != null) {
              $body =& $this->cache[$id]->properties();
              switch ($class) {                            	
                case "document": 
                
                  /* neues Dokument erstellen */
                  if ($mode == "insert") {
            	      __executeSQL("node_insert_document", array($body->documentURI, $this->cache[$id]->uri(), $body->typeName, $body->stringValue, 
            	         $body->typedValue, $id));
            	      __executeSQL("create_element", array($this->cache[$id]->uri()));
            	      __executeSQL("create_attribute", array($this->cache[$id]->uri()));
            	      __executeSQL("create_text", array($this->cache[$id]->uri()));
            	      __executeSQL("create_comment", array($this->cache[$id]->uri()));
            	      __executeSQL("create_index_attribute", array($this->cache[$id]->uri()));
            	      __executeSQL("create_index_child", array($this->cache[$id]->uri()));
            	      __executeSQL("create_index_descendant", array($this->cache[$id]->uri()));
                  
                  /* Dokumentknoten updaten */
                  } else {
                   __executeSQL("node_update_document", array($body->documentURI, $this->cache[$id]->uri(), $body->typeName, $body->stringValue, 
                      $body->typedValue, $id));
                  }
                break;
                case "element": 
                   __executeSQL("node_" . $mode . "_element", array($this->cache[$id]->uri(), $body->nodeName, $body->typeName, 
                      $body->stringValue, $this->cache[$id]->axis('parent'), get_class($this->cache[$this->cache[$id]->axis('parent')]->properties()), 
                      $id));
                break;
                case "pi": 
                   __executeSQL("node_" . $mode . "_pi", array($this->cache[$id]->uri(), $body->target, $this->cache[$id]->axis('parent'), 
                      get_class($this->cache[$this->cache[$id]->axis('parent')]->properties()), $id));
                break;
                case "attribute": 
                   __executeSQL("node_" . $mode . "_attribute", array($this->cache[$id]->uri(), $body->nodeName, $body->typeName, 
                      $body->stringValue, $body->typedValue, $this->cache[$id]->axis('parent'), 
                      get_class($this->cache[$this->cache[$id]->axis('parent')]->properties), $id));
          	       __executeSQL("node_insert_index_attribute", array($this->cache[$id]->uri(), $this->cache[$id]->axis('parent'), $id));
                break;
                case "attribute": 
                   __executeSQL("node_" . $mode . "_attribute", array($this->cache[$id]->uri(), $body->nodeName, $body->nodeType, 
                      $body->stringValue, $body->typedValue, $this->cache[$id]->axis('parent'), $id));
                break;                    
                case "text": 
                case "comment": 
                   __executeSQL("node_" . $mode . "_text_comment", array($this->cache[$id]->uri(), get_class($body), $body->content, 
                      $this->cache[$id]->axis('parent'), get_class($this->cache[$this->cache[$id]->axis('parent')]->properties()), $id));
                break;
                default:
                  PEAR::raiseError("Der Knotentype '" . $class . "' kann nicht verarbeitet werden!");
              }
              
              /* Index updaten */
              if (in_array($class, array('element', 'pi', 'text', 'comment')) and $mode == "insert") {
                __executeSQL("node_insert_index_child", array($this->cache[$id]->uri(), $this->cache[$id]->axis('parent'), $id, 
          	      array_search($id, $this->cache[$this->cache[$id]->axis('parent')]->axis('child')), $class, 
          	      get_class($this->cache[$this->cache[$id]->axis('parent')]->properties())));
          	    foreach ($this->axis($id, "ancestor") as $ancestor) {
            	    __executeSQL("node_insert_index_descendant", array($this->cache[$id]->uri(), $ancestor, $id, 
            	      array_search($id, $this->cache[$ancestor]->axis('descendant')), $class, 
            	      get_class($this->cache[$ancestor]->properties())));          	    	
          	    }
              }
            }
              
          break;
          
      	  /* Alle andern Datenquellen können nicht gespeicher werden! */
      	  default:
      	    PEAR::raiseError("Der Storage '" . $this->cache[$id]->storage() . "' kann nicht gespeichert werden!") ;
      	}
      	
      	/* Aktion aus dem Log löschen */
    		unset($this->changelog[$id]);
    	}
    }
  }
  
  /* Macht die Änderungen, die an einem Dokument gemacht wurden rückgängig 
     string $uri: ID des Dokumentknotens, der zurückgesetzt werden soll
   */
  function rollback($uri) {

    /* Temporäre Knoten können nicht zurückgesetzt werden! */
    if ($this->cache[$uri]->storage() != "db") {
    	PEAR::raiseError("Nur Dokumente in der Datenbank können zurückgesetzt werden!");
    }
    
    /* Überprüfen ob das Dokument existiert */
    $properties = $this->cache[$uri]->properties();
    if (isset($this->doc[$this->cache[$uri]->storage() . "://" . $properties->documentURI]) == false) {
    	PEAR::raiseError("Es existiert kein Dokument mit dem URI '" . $uri . "'!");
    }
    
    /* Knoten Unregistrieren */
    foreach ($this->changelog as $id => $item) {
    	if ($this->cache[$id]->storage() == $this->cache[$uri]->storage() and $this->cache[$id]->uri() == $this->cache[$uri]->uri()) {
    		unset($this->changelog[$id]);
    		$this->cache[$id]->rollback();
    	}
    }
  }

  
  /* Gibt das Item als Zeichenkette aus 
     string $id ID des Knotens
     string  return String-Wert des Knotens
   */
  function toString($id) {
    $item = $this->cache[$id]->properties();
    return $item->toString();
  }

  /* Gibt das Item als Boolscher Wert aus
     string $id ID des Knotens
     boolean return Boolscher Wert des Tokens
   */
  function toBoolean($id) {
    $item = $this->cache[$id]->properties();
    return $item->toBoolean();
  }
  
  /* Gibt das Item als XML aus 
     string $id ID des Knotens
     string  return XML-Repräsentation des Knotens
   */
  function toXML($id) {
    $xml = "";
    $item = $this->cache[$id]->properties();
    switch ($this->cache[$id]->type()) {
      
      /* Dokumentknoten */
      case "document": 
      break;
      
      /* Einzelne Werte */
      case "atomicvalue":
        $xml .= $item->value;
      break;
      
      /* Element-Knoten */
      case "element":
        $xml .= "<" . $item->toXML();
        foreach ($this->cache[$id]->axis("attribute") as $attribute) {
          $attributeItem = $this->cache[$attribute]->properties();
        	$xml .= " " . $attributeItem->toXML();
        }
        $xml .= ">";
        foreach ($this->cache[$id]->axis("child") as $child) {
        	$xml .= $this->toXML($child);
        }
        $xml .= "</" . $item->toXML() . ">";
      break;
      
      /* Text- und Kommantar-Knoten */
      case "comment":
      case "text":
        $xml .= $item->toXML();
      break;
      
      default:
        PEAR::raiseError("Knoten mit dem Type '" . $this->cache[$id]->type() . "' können nicht zu XML gemacht werden!");
    }
    return $xml;
  }  
}

/* Globales Storagobjekt alozieren */
$GLOBALS['XQDB_Storage'] =& new Storage();
?>