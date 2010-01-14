<?php

/**
 * SQLite connector class.
 * @package SQLite3Database
 */

class SQLite3Database extends SS_Database {
     /**
      * Connection to the DBMS.
      * @var object
      */
     private $dbConn;
     
     /**
      * True if we are connected to a database.
      * @var boolean
      */
     private $active;
     
     /**
      * The name of the database.
      * @var string
      */
     private $database;
     
     /*
      * This holds the name of the original database
      * So if you switch to another for unit tests, you
      * can then switch back in order to drop the temp database 
      */
     private $database_original;
     
     /*
      * This holds the parameters that the original connection was created with,
      * so we can switch back to it if necessary (used for unit tests)
      */
     private $parameters;
     
     /*
      * Actually SQLite supports transactions (they are used below), but they
      * work signifficantly different to the transactions in Postgres on which
	  * the unit test are based upon... ;(
      */
     private $supportsTransactions=false;

     /**
      * Connect to a SQLite3 database.
      * @param array $parameters An map of parameters, which should include:
      *  - database: The database to connect to
      *  - path: the path to the SQLite3 database file
      *  - key: the encryption key (needs testing)
      *  - memory: use the faster In-Memory database for unit tests
      */
     public function __construct($parameters) {
          //We will store these connection parameters for use elsewhere (ie, unit tests)
          $this->parameters=$parameters;
          $this->connectDatabase();
          
          $this->database_original=$this->database;
     }
     
     /*
      * Uses whatever connection details are in the $parameters array to connect to a database of a given name
      */
     function connectDatabase(){
          
          $parameters=$this->parameters;

          $dbName = !isset($this->database) ? $parameters['database'] : $dbName=$this->database;
          
          //assumes that the path to dbname will always be provided:
		$file = $parameters['path'] . '/' . $dbName;
		
		// use the very lightspeed SQLite In-Memory feature for testing
		if($parameters['memory'] && preg_match('/^tmpdb[0-9]+$/', $dbName)) $file = ':memory:';
		
          $this->dbConn = new SQLite3($file, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE, $parameters['key']);
          
          //By virtue of getting here, the connection is active:
          $this->active=true;
          $this->database = $dbName;
                    
          if(!$this->dbConn) {
               $this->databaseError("Couldn't connect to SQLite3 database");
               return false;
          }
          return true;
     }
     /**
      * Not implemented, needed for PDO
      */
     public function getConnect($parameters) {
          return null;
     }
     
     /**
      * Returns true if this database supports collations
      * TODO: get rid of this?
      * @return boolean
      */
     public function supportsCollations() {
          return true;
     }
     
     /**
      * The version of SQLite3.
      * @var float
      */
     private $sqliteVersion;
     
     /**
      * Get the version of SQLite3.
      * @return float
      */
     public function getVersion() {
          if(!$this->sqliteVersion) {
               $db_version=$this->query("SELECT sqlite_version()")->value();
               
               $this->sqliteVersion = (float)$db_version;
          }
          return $this->sqliteVersion;
     }
     
     /**
      * Get the database server, namely SQLite3.
      * @return string
      */
     public function getDatabaseServer() {
          return "sqlite";
     }
     
     public function query($sql, $errorLevel = E_USER_ERROR) {

          if(isset($_REQUEST['previewwrite']) && in_array(strtolower(substr($sql,0,strpos($sql,' '))), array('insert','update','delete','replace'))) {
               Debug::message("Will execute: $sql");
               return;
          }

          if(isset($_REQUEST['showqueries'])) { 
               $starttime = microtime(true);
          }

          // @todo This is a very ugly hack to rewrite the update statement of SiteTree::doPublish()
          // @see SiteTree::doPublish() There is a hack for MySQL already, maybe it's worth moving this to SiteTree or that other hack to Database...
          if(preg_replace('/[\W\d]*/i','',$sql) == 'UPDATESiteTree_LiveSETSortSiteTreeSortFROMSiteTreeWHERESiteTree_LiveIDSiteTreeIDANDSiteTree_LiveParentID') {
               preg_match('/\d+/i',$sql,$matches);
               $sql = 'UPDATE "SiteTree_Live"
                    SET "Sort" = (SELECT "SiteTree"."Sort" FROM "SiteTree" WHERE "SiteTree_Live"."ID" = "SiteTree"."ID")
                    WHERE "ParentID" = ' . $matches[0];
          }

          $handle = $this->dbConn->query($sql);
          
          if(isset($_REQUEST['showqueries'])) {
               $endtime = round(microtime(true) - $starttime,4);
               Debug::message("\n$sql\n{$endtime}ms\n", false);
          }
          
          DB::$lastQuery=$handle;
          
          if(!$handle && $errorLevel) $this->databaseError("Couldn't run query: $sql | " . $this->dbConn->lastErrorMsg(), $errorLevel);
                    
          return new SQLite3Query($this, $handle);
     }
     
     public function getGeneratedID($table) {
           return $this->dbConn->lastInsertRowID();
     }
     
     /**
      * OBSOLETE: Get the ID for the next new record for the table.
      * 
      * @var string $table The name od the table.
      * @return int
      */
     public function getNextID($table) {
          user_error('getNextID is OBSOLETE (and will no longer work properly)', E_USER_WARNING);
          $result = $this->query("SELECT MAX(ID)+1 FROM \"$table\"")->value();
          return $result ? $result : 1;
     }
     
     public function isActive() {
          return $this->active ? true : false;
     }
     
     /*
      * This will create a database based on whatever is in the $this->database value
      * So you need to have called $this->selectDatabase() first, or used the __construct method
      */
     public function createDatabase() {
          
          $fullpath = $this->parameters['path'] . '/' . $this->database;
          if(is_writable($fullpath)) unlink($fullpath);
          
          $this->connectDatabase();
          
     }

     /**
      * Drop the database that this object is currently connected to.
      * Use with caution.
      */
     public function dropDatabase() {
          //First, we need to switch back to the original database so we can drop the current one
          $db_to_drop=$this->database;
          $this->selectDatabase($this->database_original);
          $this->connectDatabase();
          
          $fullpath = $this->parameters['path'] . '/' . $db_to_drop;
          if(is_writable($fullpath)) unlink($fullpath);
     }
     
     /**
      * Returns the name of the currently selected database
      */
     public function currentDatabase() {
          return $this->database;
     }
     
     /**
      * Switches to the given database.
      * If the database doesn't exist, you should call createDatabase() after calling selectDatabase()
      */
     public function selectDatabase($dbname) {
          $this->database=$dbname;
          
          $this->tableList = $this->fieldList = $this->indexList = null;
                    
          return true;
     }

     
     /**
      * Returns true if the named database exists.
      */
     public function databaseExists($name) {
          $SQL_name=Convert::raw2sql($name);
          $result=$this->query("PRAGMA database_list");
          foreach($result as $db) if($db['name'] == 'main' && preg_match('/\/' . $name . '/', $db['file'])) return true;
		  if(file_exists($this->parameters['path'] . '/' . $name)) return true;
          return false;
     }
     
     public function clearTable($table) {
          $this->dbConn->query("DELETE FROM \"$table\"");
     }
     
     public function createTable($table, $fields = null, $indexes = null, $options = null, $advancedOptions = null) {

          if(!isset($fields['ID'])) $fields['ID'] = "INTEGER PRIMARY KEY AUTOINCREMENT";

          $fieldSchemata = array();
          if($fields) foreach($fields as $k => $v) $fieldSchemata[] = "\"$k\" $v";
          $fieldSchemas = implode(",\n",$fieldSchemata);

          // Switch to "CREATE TEMPORARY TABLE" for temporary tables
          $temporary = empty($options['temporary']) ? "" : "TEMPORARY";
          $this->query("CREATE $temporary TABLE \"$table\" (
                    $fieldSchemas
               )");
          
          return $table;
     }

     /**
      * Alter a table's schema.
      * @param $table The name of the table to alter
      * @param $newFields New fields, a map of field name => field schema
      * @param $newIndexes New indexes, a map of index name => index type
      * @param $alteredFields Updated fields, a map of field name => field schema
      * @param $alteredIndexes Updated indexes, a map of index name => index type
      */
     public function alterTable($tableName, $newFields = null, $newIndexes = null, $alteredFields = null, $alteredIndexes = null, $alteredOptions = null, $advancedOptions = null) {

          if($newFields) foreach($newFields as $fieldName => $fieldSpec) $this->createField($tableName, $fieldName, $fieldSpec);
          
          if($alteredFields) foreach($alteredFields as $fieldName => $fieldSpec) $this->alterField($tableName, $fieldName, $fieldSpec);
          
          if($newIndexes) foreach($newIndexes as $indexName => $indexSpec) $this->createIndex($tableName, $indexName, $indexSpec);

          if($alteredIndexes) foreach($alteredIndexes as $indexName => $indexSpec) $this->alterIndex($tableName, $indexName, $indexSpec);
          
     }
     
     public function renameTable($oldTableName, $newTableName) {
          $this->query("ALTER TABLE \"$oldTableName\" RENAME \"$newTableName\"");
     }
     
     /**
      * Repairs and reindexes the table.  This might take a long time on a very large table.
      * @var string $tableName The name of the table.
      * @return boolean Return true if the table has integrity after the method is complete.
      */
     public function checkAndRepairTable($tableName) {
		//	it's a pitty, vacuuming doesn't work -> locking issue
		// $this->runTableCheckCommand("VACUUM"); 
          $this->runTableCheckCommand("REINDEX \"$tableName\"");
          return true;
     }
     
     /**
      * Helper function used by checkAndRepairTable.
      * @param string $sql Query to run.
      * @return boolean Returns true no matter what; we're not currently checking the status of the command
      */
     protected function runTableCheckCommand($sql) {
          $testResults = $this->query($sql);
          return true;
     }
     
     public function createField($tableName, $fieldName, $fieldSpec) {
          $this->query("ALTER TABLE \"$tableName\" ADD \"$fieldName\" $fieldSpec");
     }
     
     /**
      * Change the database type of the given field.
      * @param string $tableName The name of the tbale the field is in.
      * @param string $fieldName The name of the field to change.
      * @param string $fieldSpec The new field specification
      */
     public function alterField($tableName, $fieldName, $fieldSpec) {

          $oldFieldList = $this->fieldList($tableName);
          $fieldNameList = '"' . implode('","', array_keys($oldFieldList)) . '"';

          if(array_key_exists($fieldName, $oldFieldList)) {
               
               $oldCols = array();
               
               foreach($oldFieldList as $name => $spec) {
                    $newColsSpec[] = "\"$name\" " . ($name == $fieldName ? $fieldSpec : $spec);
               }

               $queries = array(
                    "BEGIN TRANSACTION",
                    "CREATE TEMPORARY TABLE \"{$tableName}_change\"(" . implode(',', $newColsSpec) . ")",
                    "INSERT INTO \"{$tableName}_change\" SELECT {$fieldNameList} FROM \"$tableName\"",
                    "DROP TABLE \"$tableName\"",
                    "CREATE TABLE \"$tableName\"(" . implode(',', $newColsSpec) . ")",
                    "INSERT INTO \"$tableName\" SELECT {$fieldNameList} FROM \"{$tableName}_change\"",
                    "DROP TABLE \"{$tableName}_change\"",
                    "COMMIT"
               );
               
			foreach($queries as $query) $this->query($query.';');
			
               
          }

     }

     /**
      * Change the database column name of the given field.
      * 
      * @param string $tableName The name of the tbale the field is in.
      * @param string $oldName The name of the field to change.
      * @param string $newName The new name of the field
      */
     public function renameField($tableName, $oldName, $newName) {

          $oldFieldList = $this->fieldList($tableName);

          if(array_key_exists($oldName, $oldFieldList)) {
               
               $oldCols = array();
               
               foreach($oldFieldList as $name => $spec) {
                    $oldCols[] = "\"$name\"" . (($name == $oldName) ? " AS $newName" : '');
                    $newCols[] = "\"". (($name == $oldName) ? $newName : $name). "\"";
                    $newColsSpec[] = "\"" . (($name == $oldName) ? $newName : $name) . "\" $spec";
               }

               $queries = array(
                    "BEGIN TRANSACTION",
                    "CREATE TEMPORARY TABLE \"{$tableName}_rename\"(" . implode(',', $newColsSpec) . ")",
                    "INSERT INTO \"{$tableName}_rename\" SELECT " . implode(',', $oldCols) . " FROM \"$tableName\"",
                    "DROP TABLE \"$tableName\"",
                    "CREATE TABLE \"$tableName\"(" . implode(',', $newColsSpec) . ")",
                    "INSERT INTO \"$tableName\" SELECT " . implode(',', $newCols) . " FROM \"{$tableName}_rename\"",
                    "DROP TABLE \"{$tableName}_rename\"",
                    "COMMIT"
               );
               
               foreach($queries as $query) $this->query($query.';');
               
          }
     }
     
     public function fieldList($table) {
          $sqlCreate = DB::query('SELECT sql FROM sqlite_master WHERE type = "table" AND name = "' . $table . '"')->record();

          if($sqlCreate && $sqlCreate['sql']) {
               preg_match('/^[\s]*CREATE[\s]+TABLE[\s]+"[a-zA-Z0-9_]+"[\s]*\((.+)\)[\s]*$/ims', $sqlCreate['sql'], $matches);
               $fields = isset($matches[1]) ? preg_split('/,/i', $matches[1]) : array();
               foreach($fields as $field) {
                    $details = preg_split('/\s/', trim($field));
                    $name = array_shift($details);
                    $name = str_replace('"', '', trim($name));
                    $fieldList[$name] = implode(' ', $details);
               }
               return $fieldList;
          } else {
               return array();
          }
     }
     
     /**
      * Create an index on a table.
      * @param string $tableName The name of the table.
      * @param string $indexName The name of the index.
      * @param string $indexSpec The specification of the index, see Database::requireIndex() for more details.
      */
     public function createIndex($tableName, $indexName, $indexSpec) {
          $cleanIndexName = $this->getDbSqlDefinition($tableName, $indexName, $indexSpec);

          $this->query("DROP INDEX IF EXISTS " . $cleanIndexName);

          $this->query("CREATE INDEX \"$cleanIndexName\" ON \"$tableName\" (" . $this->convertIndexSpec($indexSpec) . ")");
     }
     
     /*
      * This takes the index spec which has been provided by a class (ie static $indexes = blah blah)
      * and turns it into a proper string.
      * Some indexes may be arrays, such as fulltext and unique indexes, and this allows database-specific
      * arrays to be created.
      */
     public function convertIndexSpec($indexSpec, $asDbValue=false, $table=''){

          $indexSpecNew = is_array($indexSpec) ? $indexSpec['value'] : $indexSpec;
          
		$indexSpecNew = preg_match('/[a-z_ ]*\((.+)\)/i',$indexSpecNew,$matches) ? $matches[1] : $indexSpecNew;

          $indexSpecNew = preg_replace('/[\s\(\)]/', '', $indexSpecNew);
          
          return $indexSpecNew;
     }
     
     /**
      * prefix indexname with uppercase tablename if not yet done, in order to avoid ambiguity
      */
     function getDbSqlDefinition($tableName, $indexName, $indexSpec) {
          $newIndexName = preg_match('/^' . strtoupper($tableName) . '_/', $indexName) ? $indexName : strtoupper($tableName) . '_' . $indexName;
          return $newIndexName;
     }

     /**
      * Alter an index on a table.
      * @param string $tableName The name of the table.
      * @param string $indexName The name of the index.
      * @param string $indexSpec The specification of the index, see Database::requireIndex() for more details.
      */
     public function alterIndex($tableName, $indexName, $indexSpec) {
          $this->createIndex($tableName, $indexName, $indexSpec);
     }
     
     /**
      * Return the list of indexes in a table.
      * @param string $table The table name.
      * @return array
      */
     public function indexList($table) {
     
			$indexList = array();
          foreach(DB::query('PRAGMA index_list("' . $table . '")') as $index) {
               $list = array();
               foreach(DB::query('PRAGMA index_info("' . $index["name"] . '")') as $details) $list[] = $details['name'];
               $indexList[$index["name"]] = implode(',', $list);
          }

          return $indexList;
     }

     /**
      * Returns a list of all the tables in the database.
      * Table names will all be in lowercase.
      * @return array
      */
     public function tableList() {
          foreach($this->query('SELECT name FROM sqlite_master WHERE type = "table"') as $record) {
               //$table = strtolower(reset($record));
               $table = reset($record);
               $tables[$table] = $table;
          }
          
          //Return an empty array if there's nothing in this database
          return isset($tables) ? $tables : Array();
     }
     
     function TableExists($tableName){
          $result=$this->query('SELECT name FROM sqlite_master WHERE type = "table" AND name="' . $tableName . '"')->first();
          
          if($result)
               return true;
          else
               return false;
          
     }
     
     /**
      * Return the number of rows affected by the previous operation.
      * @return int
      */
     public function affectedRows() {
          return $this->dbConn->changes();
     }
     
     /**
      * Return a boolean type-formatted string
      * 
      * @params array $values Contains a tokenised list of info about this data type
      * @return string
      */
     public function boolean($values, $asDbValue=false){

          return 'BOOL not null default ' . (int)$values['default'];

     }
     
     /**
      * Return a date type-formatted string
      * 
      * @params array $values Contains a tokenised list of info about this data type
      * @return string
      */
     public function date($values){

          return "TEXT";

     }
     
     /**
      * Return a decimal type-formatted string
      * 
      * @params array $values Contains a tokenised list of info about this data type
      * @return string
      */
     public function decimal($values, $asDbValue=false){

          return "NUMERIC not null DEFAULT 0";

     }
     
     /**
      * Return a enum type-formatted string
	  *
 	  * enumus are not supported. as a workaround to store allowed values we creates an additional table
      * 
      * @params array $values Contains a tokenised list of info about this data type
      * @return string
      */
     public function enum($values){

          $bt=debug_backtrace();
          if(basename($bt[0]['file']) == 'Database.php') {
               $column = $bt[0]['args'][0]['table'].'.'.$bt[0]['args'][0]['name'];
               $this->query("CREATE TABLE IF NOT EXISTS SQLiteEnums (TableColumn TEXT PRIMARY KEY, EnumList TEXT)");
               $this->query("REPLACE INTO SQLiteEnums (TableColumn,EnumList) VALUES (\"$column\",\"".implode(',', $values['enums'])."\")");
          }
          
          return 'TEXT DEFAULT \'' . $values['default'] . '\'';

     }
     
     /**
      * Return a float type-formatted string
      * 
      * @params array $values Contains a tokenised list of info about this data type
      * @return string
      */
     public function float($values, $asDbValue=false){

          return "REAL";

     }
     
     /**
      * Return a int type-formatted string
      * 
      * @params array $values Contains a tokenised list of info about this data type
      * @return string
      */
     public function int($values, $asDbValue=false){

          return "INTEGER($values[precision]) $values[null] DEFAULT " . (int)$values['default'];

     }
     
     /**
      * Return a datetime type-formatted string
      * For SQLite3, we simply return the word 'TEXT', no other parameters are necessary
      * 
      * @params array $values Contains a tokenised list of info about this data type
      * @return string
      */
     public function SS_Datetime($values, $asDbValue=false){
          
          return "DATETIME";

     }
     
     /**
      * Return a text type-formatted string
      * 
      * @params array $values Contains a tokenised list of info about this data type
      * @return string
      */
     public function text($values, $asDbValue=false){

          return 'TEXT';

     }
     
     /**
      * Return a time type-formatted string
      * 
      * @params array $values Contains a tokenised list of info about this data type
      * @return string
      */
     public function time($values){

          return "TEXT";

     }
     
     /**
      * Return a varchar type-formatted string
      * 
      * @params array $values Contains a tokenised list of info about this data type
      * @return string
      */
     public function varchar($values, $asDbValue=false){

		  return 'VARCHAR(' . $values['precision'] . ') COLLATE NOCASE';

     }
     
     /*
      * Return a 4 digit numeric type.  MySQL has a proprietary 'Year' type.
      * For SQLite3 we use TEXT
      */
     public function year($values, $asDbValue=false){

          return "TEXT";

     }
     
     function escape_character($escape=false){

          if($escape) return "\\\""; else return "\"";

     }
     
     /**
      * This returns the column which is the primary key for each table
      * In SQLite3 it is INTEGER PRIMARY KEY AUTOINCREMENT
      * SQLite3 does autoincrement ids even without the AUTOINCREMENT keyword, but the behaviour is signifficantly different
      *
      * @return string
      */
     function IdColumn($asDbValue=false){
          return 'INTEGER PRIMARY KEY AUTOINCREMENT';
     }
     
     /**
      * Returns true if this table exists
      */
     function hasTable($tableName) {
          $SQL_table = Convert::raw2sql($table);
          return (bool)($this->query("SELECT name FROM sqlite_master WHERE type = \"table\" AND name = \"$tableName\"")->value());
     }
     
     /**
      * Returns the SQL command to get all the tables in this database
      */
     function allTablesSQL(){
          //ANDY return "SELECT table_name FROM information_schema.tables WHERE table_schema='public' AND table_type='BASE TABLE';";
          return 'SELECT name FROM sqlite_master WHERE type = "table"';
     }
     
     /**
      * Return enum values for the given field
      */
     public function enumValuesForField($tableName, $fieldName) {
          $classnameinfo = DB::query("SELECT EnumList FROM SQLiteEnums WHERE TableColumn = \"{$tableName}.{$fieldName}\"")->first();
          return explode(',', $classnameinfo['EnumList']);
     }

     /**
      * Get the actual enum fields from the constraint value:
      */
     private function EnumValuesFromConstraint($constraint){
          $constraint=substr($constraint, strpos($constraint, 'ANY (ARRAY[')+11);
          $constraint=substr($constraint, 0, -11);
          $constraints=Array();
          $segments=explode(',', $constraint);
          foreach($segments as $this_segment){
               $bits=preg_split('/ *:: */', $this_segment);
               array_unshift($constraints, trim($bits[0], " '"));
          }
          
          return $constraints;
     }
     
     /*
      * Returns the database-specific version of the now() function
      */
     function now(){
          return "datetime('now', 'localtime')";
     }
     
     /*
      * Returns the database-specific version of the random() function
      */
     function random(){
          return 'random()';
     }
     
     /*
      * This will return text which has been escaped in a database-friendly manner
      */
     function addslashes($value){
          return $this->dbConn->escapeString($value);
     }
     
     /*
      * This changes the index name depending on database requirements.
      */
     function modifyIndex($index, $spec){
          return $index;
     }
     
     /**
      * The core search engine configuration.
      * @todo There is a fulltext search for SQLite making use of virtual tables, the fts3 extension and the MATCH operator
      * there are a few issues with fts:
      * - shared cached lock doesn't allow to create virtual tables on versions prior to 3.6.17
      * - there must not be more than one MATCH operator per statement
      * - the fts3 extension needs to be available
      * for now we use the MySQL implementation with the MATCH()AGAINST() uglily replaced with LIKE
      * 
      * @param string $keywords Keywords as a space separated string
      * @return object DataObjectSet of result pages
      */
     public function searchEngine($classesToSearch, $keywords, $start, $pageLength, $sortBy = "Relevance DESC", $extraFilter = "", $booleanSearch = false, $alternativeFileFilter = "", $invertedMatch = false) {
          $fileFilter = '';           
          $keywords = Convert::raw2sql(str_replace(array('*','+','-'),'',$keywords));
          $htmlEntityKeywords = htmlentities(utf8_decode($keywords));
          
          $extraFilters = array('SiteTree' => '', 'File' => '');
           
           if($extraFilter) {
                $extraFilters['SiteTree'] = " AND $extraFilter";
                
                if($alternativeFileFilter) $extraFilters['File'] = " AND $alternativeFileFilter";
                else $extraFilters['File'] = $extraFilters['SiteTree'];
           }
           
          // Always ensure that only pages with ShowInSearch = 1 can be searched
          $extraFilters['SiteTree'] .= " AND ShowInSearch <> 0";

          $limit = $start . ", " . (int) $pageLength;
          
          $notMatch = $invertedMatch ? "NOT " : "";
          if($keywords) {
               $match['SiteTree'] = "
                    (Title LIKE '%$keywords%' OR MenuTitle LIKE '%$keywords%' OR Content LIKE '%$keywords%' OR MetaTitle LIKE '%$keywords%' OR MetaDescription LIKE '%$keywords%' OR MetaKeywords LIKE '%$keywords%' OR
                     Title LIKE '%$htmlEntityKeywords%' OR MenuTitle LIKE '%$htmlEntityKeywords%' OR Content LIKE '%$htmlEntityKeywords%' OR MetaTitle LIKE '%$htmlEntityKeywords%' OR MetaDescription LIKE '%$htmlEntityKeywords%' OR MetaKeywords LIKE '%$htmlEntityKeywords%')
               ";
               $match['File'] = "(Filename LIKE '%$keywords%' OR Title LIKE '%$keywords%' OR Content LIKE '%$keywords%') AND ClassName = 'File'";
     
               // We make the relevance search by converting a boolean mode search into a normal one
               $relevanceKeywords = $keywords;
               $htmlEntityRelevanceKeywords = $htmlEntityKeywords;
               $relevance['SiteTree'] = "(Title LIKE '%$relevanceKeywords%' OR MenuTitle LIKE '%$relevanceKeywords%' OR Content LIKE '%$relevanceKeywords%' OR MetaTitle LIKE '%$relevanceKeywords%' OR MetaDescription LIKE '%$relevanceKeywords%' OR MetaKeywords) + (Title LIKE '%$htmlEntityRelevanceKeywords%' OR MenuTitle LIKE '%$htmlEntityRelevanceKeywords%' OR Content LIKE '%$htmlEntityRelevanceKeywords%' OR MetaTitle LIKE '%$htmlEntityRelevanceKeywords%' OR MetaDescription LIKE '%$htmlEntityRelevanceKeywords%' OR MetaKeywords LIKE '%$htmlEntityRelevanceKeywords%')";
               $relevance['File'] = "(Filename LIKE '%$relevanceKeywords%' OR Title LIKE '%$relevanceKeywords%' OR Content LIKE '%$relevanceKeywords%')";
          } else {
               $relevance['SiteTree'] = $relevance['File'] = 1;
               $match['SiteTree'] = $match['File'] = "1 = 1";
          }

          // Generate initial queries and base table names
          $baseClasses = array('SiteTree' => '', 'File' => '');
          foreach($classesToSearch as $class) {
               $queries[$class] = singleton($class)->extendedSQL($notMatch . $match[$class] . $extraFilters[$class], "");
               $baseClasses[$class] = reset($queries[$class]->from);
          }
          
          // Make column selection lists
          $select = array(
               'SiteTree' => array("\"ClassName\"","\"SiteTree\".\"ID\"","\"ParentID\"",        "\"Title\"","\"URLSegment\"",        "\"Content\"","\"LastEdited\"","\"Created\"","NULL AS \"Filename\"", "NULL AS \"Name\"", "\"CanViewType\"", "$relevance[SiteTree] AS Relevance"),
               'File'     => array("\"ClassName\"","\"File\".\"ID\"",    "NULL AS \"ParentID\"","\"Title\"","NULL AS \"URLSegment\"","\"Content\"","\"LastEdited\"","\"Created\"","\"Filename\"",         "\"Name\"", "NULL AS \"CanViewType\"", "$relevance[File] AS Relevance"),
          );
          
          // Process queries
          foreach($classesToSearch as $class) {
               // There's no need to do all that joining
               $queries[$class]->from = array(str_replace('`','',$baseClasses[$class]) => $baseClasses[$class]);
               $queries[$class]->select = $select[$class];
               $queries[$class]->orderby = null;
          }

          // Combine queries
          $querySQLs = array();
          $totalCount = 0;
          foreach($queries as $query) {
               $querySQLs[] = $query->sql();
               $totalCount += $query->unlimitedRowCount();
          }
          $fullQuery = implode(" UNION ", $querySQLs) . " ORDER BY $sortBy LIMIT $limit";
          // Get records
          $records = DB::query($fullQuery);

          foreach($records as $record)
               $objects[] = new $record['ClassName']($record);
          
          if(isset($objects)) $doSet = new DataObjectSet($objects);
          else $doSet = new DataObjectSet();
          
          $doSet->setPageLimits($start, $pageLength, $totalCount);
          return $doSet;
     }
     
     /*
      * Does this database support transactions?
      */
     public function supportsTransactions(){
          return $this->supportsTransactions;
     }
     
     /*
      * This is a quick lookup to discover if the database supports particular extensions
      */
     public function supportsExtensions($extensions=Array('partitions', 'tablespaces', 'clustering')){
          if(isset($extensions['partitions']))
               return true;
          elseif(isset($extensions['tablespaces']))
               return true;
          elseif(isset($extensions['clustering']))
               return true;
          else
               return false;
     }
     
     /*
      * Start a prepared transaction
      */
     public function startTransaction($transaction_mode=false, $session_characteristics=false){
          DB::query('BEGIN');
     }
     
     /*
      * Create a savepoint that you can jump back to if you encounter problems
      */
     public function transactionSavepoint($savepoint){
          DB::query("SAVEPOINT \"$savepoint\"");
     }
     
     /*
      * Rollback or revert to a savepoint if your queries encounter problems
      * If you encounter a problem at any point during a transaction, you may
      * need to rollback that particular query, or return to a savepoint
      */
     public function transactionRollback($savepoint=false){
          
          if($savepoint) {
               DB::query("ROLLBACK TO $savepoint;");
          } else {
               DB::query('ROLLBACK;');
		}
     }
     
     /*
      * Commit everything inside this transaction so far
      */
     public function endTransaction(){
          DB::query('COMMIT;');
     }
     
	/**
	 * Convert a SQLQuery object into a SQL statement
	 */
	public function sqlQueryToString(SQLQuery $sqlQuery) {
		if (!$sqlQuery->from) return '';
		$distinct = $sqlQuery->distinct ? "DISTINCT " : "";
		if($sqlQuery->delete) {
			$text = "DELETE ";
		} else if($sqlQuery->select) {
			$text = "SELECT $distinct" . implode(", ", $sqlQuery->select);
		}
		$text .= " FROM " . implode(" ", $sqlQuery->from);

		if($sqlQuery->where) $text .= " WHERE (" . $sqlQuery->getFilter(). ")";
		if($sqlQuery->groupby) $text .= " GROUP BY " . implode(", ", $sqlQuery->groupby);
		if($sqlQuery->having) $text .= " HAVING ( " . implode(" ) AND ( ", $sqlQuery->having) . " )";
		if($sqlQuery->orderby) $text .= " ORDER BY " . $this->orderMoreSpecifically($sqlQuery->select,$sqlQuery->orderby);

		if($sqlQuery->limit) {
			$limit = $sqlQuery->limit;
			// Pass limit as array or SQL string value
			if(is_array($limit)) {
				if(!array_key_exists('limit',$limit)) user_error('SQLQuery::limit(): Wrong format for $limit', E_USER_ERROR);

				if(isset($limit['start']) && is_numeric($limit['start']) && isset($limit['limit']) && is_numeric($limit['limit'])) {
					$combinedLimit = "$limit[limit] OFFSET $limit[start]";
				} elseif(isset($limit['limit']) && is_numeric($limit['limit'])) {
					$combinedLimit = (int)$limit['limit'];
				} else {
					$combinedLimit = false;
				}
				if(!empty($combinedLimit)) $text .= " LIMIT " . $combinedLimit;

			} else {
				$text .= " LIMIT " . $sqlQuery->limit;
			}
		}

		return $text;
	}
	
	/**
	 * SQLite3 complains about ambiguous column names if the ORDER BY expression doesn't contain the table name
	 * and the expression matches more than one expression in the SELECT expression.
	 * assuming that there is no amibguity we just use the first table name
     * 
     * used by SQLite3Database::sqlQueryToString()
     * 
     * @param array $select SELECT expressions as of SQLquery
     * @param string $order ORDER BY expressions to be checked and augmented as of SQLquery
     * @return string fully specified ORDER BY expression
	 */
	protected function orderMoreSpecifically($select,$order) {
		
		$altered = false;

		// split expression into order terms
		$terms = explode(',', $order);
		
		foreach($terms as $i => $term) {
			$term = trim($term);
			
			// check if table is unspecified
			if(!preg_match('/\./', $term)) {
				$direction = '';
				if(preg_match('/( ASC)$|( DESC)$/i',$term)) list($term,$direction) = explode(' ', $term);

				// find a match in the SELECT array and replace
				foreach($select as $s) {
					if(preg_match('/"[a-z0-9_]+"\.' . $term . '/i', trim($s))) {
						$terms[$i] = $s . ' ' . $direction;
						$altered = true;
						break;
					}
				}
			}
			
		}

		return implode(',', $terms);
	}
}

/**
 * A result-set from a SQLite3 database.
 * @package SQLite3Database
 */
class SQLite3Query extends SS_Query {
     /**
      * The SQLite3Database object that created this result set.
      * @var SQLite3Database
      */
     private $database;
     
     /**
      * The internal sqlite3 handle that points to the result set.
      * @var resource
      */
     private $handle;

     /**
      * Hook the result-set given into a Query class, suitable for use by sapphire.
      * @param database The database object that created this query.
      * @param handle the internal sqlite3 handle that is points to the resultset.
      */
     public function __construct(SQLite3Database $database, SQLite3Result $handle) {
          $this->database = $database;
          $this->handle = $handle;
     }
     
     public function __destroy() {
          $this->handle->finalize();
     }
     
     public function seek($row) {
          $this->handle->reset();
          $i=0;
          while($i < $row && $row = SQLite3Result::fetchArray()) $i++;
          return (bool) $row;
     }
     
     /**
      * @todo This looks terrible but there is no SQLite3::get_num_rows() implementation
      */
     public function numRecords() {
          $c=0;
          while($this->handle->fetchArray()) $c++;
          $this->handle->reset();
          return $c;
     }
     
     public function nextRecord() {
          // Coalesce rather than replace common fields.
          if(@$data = $this->handle->fetchArray(SQLITE3_NUM)) {
               foreach($data as $columnIdx => $value) {
					if(preg_match('/^"([a-z0-9_]+)"\."([a-z0-9_]+)"$/i', $this->handle->columnName($columnIdx), $matches)) $columnName = $matches[2];
					else if(preg_match('/^"([a-z0-9_]+)"$/i', $this->handle->columnName($columnIdx), $matches)) $columnName = $matches[1];
					else $columnName = trim($this->handle->columnName($columnIdx),"\"' \t");
                    // $value || !$ouput[$columnName] means that the *last* occurring value is shown
                    // !$ouput[$columnName] means that the *first* occurring value is shown
                    if(isset($value) || !isset($output[$columnName])) {
                         $output[$columnName] = is_null($value) ? null : (string)$value;
                    }
               }
               return $output;
          } else {
               return false;
          }
     }
     
     
}