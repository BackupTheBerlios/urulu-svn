<?PHP
/* Mysqlqueries definieren */
$GLOBALS['XQDB_Queries'] = array('mysql' => array(
/*
   Queries f�r den Installationsprozess
 */
/* Erststellt die Tabelle f�r den Dokumentindex */
  'create_Documents' => array("CREATE TABLE documents (
    id VARCHAR(32) NOT NULL default '',
    documentURI VARCHAR(255) NOT NULL default '',
    tableName VARCHAR(255) NOT NULL default '',
    typeName VARCHAR(255) NOT NULL default '',
    stringValue TEXT NOT NULL default '',
    typedValue TEXT NOT NULL default '',
    PRIMARY KEY (id)
  ) TYPE=MyISAM AUTO_INCREMENT=1", 0),
/* Erststellt die Tabelle in der Die Benuzter gespeichert sind */
  'creeate_user' => array("CREATE TABLE user (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL default '',
    passwd VARCHAR(32) NOT NULL default '',
    email VARCHAR(255) NOT NULL default '',
    KEY name (name(10)),
    PRIMARY KEY (id)
  ) TYPE=MyISAM AUTO_INCREMENT=1", 0),
/* Erstellt die Tabelle mit den Relationen Benuzter-Rollen */
  'create_userrole' => array("CREATE TABLE userrole (
    id INT(11) NOT NULL AUTO_INCREMENT,
    uid INT(11) NOT NULL default 0,
    rid INT(11) NOT NULL default 0,
    KEY indeces (uid, rid),
    PRIMARY KEY (id)
  ) TYPE=MyISAM AUTO_INCREMENT=1", 0),
/* Erstellt die Tabelle mit den Rollen der Benuzter */
  'create_role' => array("CREATE TABLE role (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL default '',
    KEY indeces (name(10)),
    PRIMARY KEY (id)
  ) TYPE=MyISAM AUTO_INCREMENT=1", 0),
/* Erstellt die Tabelle mit den Sessions */
  'create_sessions' => array("CREATE TABLE session (
    id VARCHAR(32) NOT NULL default '',
    hash VARCHAR(32) NOT NULL default '',
    UNIQUE KEY sess (id, hash)
  ) TYPE=Heap", 0),

/*
   Queries zur Benuzterverwalutng
 */
  'user_insert' => array("INSERT INTO user (name, passwd, email) VALUES ('?', md5(CONCAT(md5('?'), md5('Urulu-Secutrity-Pat'))), '?')", 3),
  'user_exist' => array("SELECT * FROM user WHERE name='?'", 1),
  'user_drop' => array("DELETE FROM user WHERE name='?'", 1),

  'role_insert' => array("INSERT INTO role (name) VALUES ('?')", 1),
  'role_exist' => array("SELECT * FROM role WHERE name='?'", 1),
  'role_drop' => array("DELETE FROM role WHERE name='?'", 1),
  
  'get_role_by_user' => array("SELECT role.* FROM role, userrole, user WHERE role.id=userrole.rid AND userrole.uid=user.id AND user.name='?'", 1),
  'login' => array("SELECT * FROM user WHERE name='?' AND passwd=md5(CONCAT(md5('?'), md5('Urulu-Secutrity-Pat')))", 2),
  
  'add_user_to_role' => array("INSERT INTO userrole (uid, rid) SELECT user.id, role.id FROM user, role WHERE user.name='?' AND role.name='?'", 2),
  'is_role_for_user' => array("SELECT userrole.* FROM userrole, user, role WHERE userrole.uid=user.id AND user.name='?' AND userrole.rid=role.id AND role.name='?'", 2),
  'get_roles_for_user' => array("SELECT role.* FROM userrole, user, role WHERE userrole.uid=user.id AND user.name='?' AND role.id=userrole.rid", 1),
  'get_users_for_role' => array("SELECT user.* FROM userrole, user, role WHERE userrole.uid=user.id AND role.name='?' AND role.id=userrole.rid", 1),
  'remove_user_from_role' => array("DELETE FROM userrole WHERE uid='?' AND rid='?'", 2),

/*
   Queries zur Verwaltung der Sessions
 */
  'session_update' => array("UPDATE session SET hash='?' WHERE id='?' AND hash='?'", 3),
  'session_delete' => array("DELETE FROM session WHERE id='?'", 1),
  'session_insert' => array("INSERT INTO session (id, hash) VALUES ('?', '?')", 2),    	 

/*
   Queries Dokumente zu erstellen
 */
/* Erstellt eine Tabelle mit den Elementen */
  'create_element' => array("CREATE TABLE ?_element (
    id VARCHAR(32) NOT NULL default '',
    nodeName VARCHAR(255) NOT NULL default '',
    typeName VARCHAR(255) NOT NULL default '',
    stringValue TEXT NOT NULL default '',
    parent VARCHAR(32) NOT NULL default 0,
    parentType ENUM('document', 'element') NOT NULL,
    PRIMARY KEY  (id),
    KEY element (nodeName(10))
  ) TYPE=MyISAM AUTO_INCREMENT=1", 1),
/* Erstellt eine Tabelle mit den Prozessorinstruktionen */
  'create_pi' => array("CREATE TABLE ?_pi (
    id VARCHAR(32) NOT NULL default '',
    target VARCHAR(255) NOT NULL default '',
    parent VARCHAR(32) NOT NULL default 0,
    parentType ENUM('document', 'element') NOT NULL,
    PRIMARY KEY  (id),
    KEY element (target(10))
  ) TYPE=MyISAM AUTO_INCREMENT=1", 1),
/* Erstellt eine Tabelle mit den Attributen */
  'create_attribute' => array("CREATE TABLE ?_attribute (
    id VARCHAR(32) NOT NULL default '',
    nodeName VARCHAR(255) NOT NULL default '',
    typeName VARCHAR(255) NOT NULL default '',
    stringValue TEXT NOT NULL default '',
    typedValue TEXT NOT NULL default '',
    parent VARCHAR(32) NOT NULL default 0,
    parentType ENUM('pi', 'element') NOT NULL,
    PRIMARY KEY  (id),
    KEY attribute (nodeName(10))
  ) TYPE=MyISAM AUTO_INCREMENT=1", 1),
/* Erstellt eine Tabelle mit den Texten */
  'create_text' => array("CREATE TABLE ?_text (
    id VARCHAR(32) NOT NULL default '',
    content TEXT NOT NULL default '',
    parent VARCHAR(32) NOT NULL default 0,
    parentType ENUM('document', 'element') NOT NULL,
    PRIMARY KEY  (id),
    KEY text (content(10))
  ) TYPE=MyISAM AUTO_INCREMENT=1", 1),
/* Erstellt die Tabelle mit den Kommentaren */
  'create_comment' => array("CREATE TABLE ?_comment (
    id VARCHAR(32) NOT NULL default '',
    content TEXT NOT NULL default '',
    parent VARCHAR(32) NOT NULL default 0,
    parentType ENUM('document', 'element') NOT NULL,
    PRIMARY KEY  (id),
    KEY comment (content(10))
  ) TYPE=MyISAM AUTO_INCREMENT=1", 1),
/* Erstellt die Tabelle mit den Kommentaren */
  'create_access' => array("CREATE TABLE ?_access (
    id VARCHAR(32) NOT NULL default '',
    parent VARCHAR(32) NOT NULL default 0,
    action ENUM('select', 'insert', 'update', 'rename') NOT NULL,
    node ENUM('element', 'attribute', 'text', 'comment', 'node', 'item') NOT NULL,
    role INT(11) NOT NULL default 0,
    type ENUM('execute', 'grant') NOT NULL,
    instance VARCHAR(32) NOT NULL default '*',
    descendant INT(11) NOT NULL default 0,
    PRIMARY KEY  (id),
    KEY action (role, action)
  ) TYPE=MyISAM AUTO_INCREMENT=1", 1),
/* Erstellt die Tabelle f�r die Attribute */
  'create_index_attribute' => array("CREATE TABLE ?_index_attribute (
    element VARCHAR(32) NOT NULL default 0,
    attribute VARCHAR(32) NOT NULL default 0,
    PRIMARY KEY  (element, attribute)
  ) TYPE=MyISAM AUTO_INCREMENT=1", 1),
/* Erstellt die Tabelle f�r die Attribute */
  'create_index_access' => array("CREATE TABLE ?_index_access (
    element VARCHAR(32) NOT NULL default 0,
    access VARCHAR(32) NOT NULL default 0,
    PRIMARY KEY  (element, access)
  ) TYPE=MyISAM AUTO_INCREMENT=1", 1),
/* Erstellt die Tabelle f�r die Children */
  'create_index_child' => array("CREATE TABLE ?_index_child (
    parent VARCHAR(32) NOT NULL default 0,
    child VARCHAR(32) NOT NULL default 0,
    intOrder INT(11) NOT NULL default 0,
    childType ENUM('element', 'pi', 'comment', 'text') NOT NULL,
    parentType ENUM('document', 'pi', 'element') NOT NULL,
    PRIMARY KEY (parent, child)
  ) TYPE=MyISAM AUTO_INCREMENT=1", 1),
  
/*
   Allgmeine Queries
 */
/* Gibt den zuletzt eingef�rgten index zur�ck */
  'last_insert_id' => array("SELECT LAST_INSERT_ID() as id", 0),
  
/*
   Queries zum Verwalten der Knoten
 */
/* Liest einen Dokumentknoten auf */
 'node_select_document' => array("SELECT * FROM documents WHERE documentURI='?' LIMIT 1", 1),
/* Listet alle Kinderelemente auf */
 'node_select_child' => array("SELECT child, childType as type FROM ?_index_child WHERE parent='?' ORDER BY intOrder", 2),
/* Listet alle Zugriffsknoten auf */
 'node_select_access' => array("SELECT access, 'access' as type FROM ?_index_access WHERE element='?'", 2),
/* Listet alle Attribute auf */
 'node_select_attribute' => array("SELECT attribute, 'attribute' as type FROM ?_index_attribute WHERE element='?'", 2),
/* Findet die Werte des Bodys */
 'node_select_node' => array("SELECT * FROM ?_? WHERE id='?' LIMIT 1", 3),
 
/* Erstellt einen Index zu einem Attribute */
 'node_insert_index_attribute' => array("INSERT INTO ?_index_attribute (element, attribute) VALUES ('?', '?')", 3),
/* Erstellt einen Index zu einem Kinderknoten */
 'node_insert_index_child' => array("INSERT INTO ?_index_child (parent, child, intOrder, childType, parentType) VALUES ('?', '?', '?', '?', '?')", 6),
/* F�gt en Dokumentknoten ein */
 'node_insert_document' => array("INSERT INTO documents (documentURI, tableName, typeName, stringValue, typedValue, id) VALUES ('?', '?', '?', '?', '?', '?')", 6),
/* F�gt ein Elementknoten ein */
 'node_insert_element' => array("INSERT INTO ?_element (nodeName, typeName, stringValue, parent, parentType, id) VALUES ('?', '?', '?', '?', '?', '?')", 7),
/* F�gt ein Prozessorinstruktionsknoten ein */
 'node_insert_pi' => array("INSERT INTO ?_pi (target, parent, parentType, id) VALUES ('?', '?', '?', '?')", 5),
/* F�gt ein Attribute ein */
 'node_insert_attribute' => array("INSERT INTO ?_attribute (nodeName, typeName, stringValue, typedValue, parent, parentType, id) VALUES ('?', '?', '?', '?', '?', '?', '?')", 8),
 /* F�gt ein Text- oder Kommentarknoten ein */
 'node_insert_text_comment' => array("INSERT INTO ?_? (content, parent, parentType, id) VALUES ('?', '?', '?', '?')", 6),

/* Weist einem Dokument neue Werte zu */
 'node_update_document' => array("UPDATE documents SET documentURI='?', tableName='?', typeName='?', stringValue='?', typedValue='?' WHERE id='?' LIMIT 1", 6), 
/* Weist einem Element neue Werte zu */
 'node_update_element' => array("UPDATE ?_element SET nodeName='?', typeName='?', stringValue='?', parent='?', parentType='?' WHERE id='?' LIMIT 1", 7),
/* Weist einer Prozessorinstruktion neue Werte zu */
 'node_update_pi' => array("UPDATE ?_pi SET target='?', parent='?', parentType='?'  WHERE id='?' LIMIT 1", 5),
/* Weist einem Attribute neue Werte zu */
 'node_update_attribute' => array("UPDATE ?_attribute SET nodeName='?', typeName='?', stringValue='?', typedValue='?', parent='?' parentType='?'  WHERE id='?' LIMIT 1", 8),
 /* Weist einem Text- oder Kommentarknoten neue Werte zu */
 'node_update_text_comment' => array("UPDATE ?_? SET content='?', parent='?', parentType='?' WHERE id='?' LIMIT 1", 6), 
 
/* L�scht einen Dokumentknoten */
 'node_delete_document' => array("DELETE FROM documents WHERE id='?' LIMIT 1", 1),
/* L�scht den Body eines Knotens */
 'node_delete_node' => array("DELETE FROM ?_? WHERE id='?' LIMIT 1", 3),
/* L�scht alle links zu Attributen des Knotens */
 'node_delete_index_attribute' => array("DELETE FROM ?_index_attribute WHERE element='?'", 2),
/* L�scht alle links zu Kinder des Knotens */
 'node_delete_index_child_by_parent' => array("DELETE FROM ?_index_child WHERE parent='?'", 2),
/* L�scht alle links zu Kinder des Knotens */
 'node_delete_index_child_by_child' => array("DELETE FROM ?_index_child WHERE child='?' LIMIT 1", 2),
));
?>