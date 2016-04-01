<?php

/**
*	@file cPDO.php
*	@brief PDO management object.  Connects to MySQL server.
*/

namespace Data {

/**
*	@brief PDO management object.  Connects to MySQL server.
*/
class cPDO extends \Error\cError {

	private $host     = null; /**< mysql host */
	private $username = null; /**< mysql username */
	private $password = null; /**< mysql password */
	private $database = null; /**< mysql database */
	private $dbh      = null; /**< PDO instance */
	private $sth      = null; /**< PDOStatement instance */
	public  $state    = null;

	function logError($str) {
		parent::logError($str);
		$this->state = $str;
	}
	
	function getState() {
		$st = $this->state;
		$this->state = null;
		return $st;
	}
	
	/**	connects to the mysql server
	*	@param $log destination log file
	*	@param $host
	*	@param $username
	*	@param $password
	*	@param $database
	*/
	function __construct($log = null, $host = null, $username = null, $password = null, $database = null) {
		parent::__construct($log);
		$this->host     = $host     == null ? (defined("PDO_HOST")     ? PDO_HOST     : null) : $host;
		$this->username = $username == null ? (defined("PDO_USERNAME") ? PDO_USERNAME : null) : $username;
		$this->password = $password == null ? (defined("PDO_PASSWORD") ? PDO_PASSWORD : null) : $password;
		$this->database = $database == null ? (defined("PDO_DATABASE") ? PDO_DATABASE : null) : $database;
 
                if ($this->password && $this->username && $this->password && $this->database) {
                        try {
				$options = array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'); // prior to php version 5.3.6 the charset element is ignored
				$this->dbh = new \PDO("mysql:host={$this->host};dbname={$this->database};charset=utf8mb4", $this->username, $this->password);//, $options);
				$this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			} catch(\Exception $e) {
				$this->logError($e->__toString());
				$this->dbh = null;
			}
		} else {
			$this->logError("cPDO::__construct() - configuration not set");
			$this->dbh = null;
		}
		$this->sth = null;
	}
	/**	destructor
	*/
	function __destruct() {
		parent::__destruct();
		try {
			$this->dbh = null;
		} catch(\Exception $e) {
			$this->logError($e->__toString());
		}
	}

	/**	grabs the last insert id
	*/
	function insertID() {
		$last = 0;
		try {
			$last = $this->dbh ? $this->dbh->lastInsertId() : 0;
		} catch(\Exception $e) {
			$this->logError($e->__toString());
		}
		return $last;
	}

	/**	returns the rows affected by the last query
	*/
	function rowsAffected() {
		return $this->rowCount();
	}

	/**	returns the row count
	*/
	function rowCount() {
		$rows = 0;
		try {
			$rows = $this->sth ? $this->sth->rowCount() : 0;
		} catch(\Exception $e) {
			$this->logError($e->__toString());
		}
		return $rows;
	}

	/**	returns the column count
	*/
	function columnCount() {
		$columns = 0;
		try {
			$columns = $this->sth ? $this->sth->columnCount() : 0;
		} catch(\Exception $e) {
			$this->logError($e->__toString());
		}
		return $columns;
	}

	/**	returns the column meta related to the last query
	*/
	function getColumnMeta() {
		$columns = $this->columnCount();
		$meta = array();
		for ($i = 0; $i < $columns; $i++) {
			try {
				$meta[] = $this->sth ? $this->sth->getColumnMeta($i) : array();
			} catch(\Exception $e) {
				$meta[] = array();
				$this->logError($e->__toString());
			}
		}
		return $meta;
	}

	/**	returns a list of tables in the database
	*/
	function showTables() {
		return $this->fetchNum("SHOW TABLES");
	}

	/**	quotes a string for safe queries
	*	@param $unsafe the string to quote
	*/
	function quote($unsafe) {
		$safe = '';
		try {
			$safe = $this->dbh ? $this->dbh->quote($unsafe) : '';
		} catch(\Exception $e) {
			$this->logError($e->__toString());
		}
		return $safe;
	}

	/**	performs a query
	*	@param $query
	*/
	function query($query) {
		try {
			if ($this->dbh) {
				$this->sth = $this->dbh->query($query);
//				$this->sth = $this->dbh->prepare($query);
//				$this->sth->execute();
			}
		} catch(\Exception $e) {
			$this->logError($e->__toString());
		}
	}

	/**	returns the results of a query as a numeric array
	*	@param $query
	*/
	function fetchNum($query) {
		$res = Array();
		try {
			if ($this->dbh) {
				$this->sth = $this->dbh->query($query);
				$this->sth->setFetchMode(\PDO::FETCH_NUM);
				while ($row = $this->sth->fetch()) {
					$res[] = $row;
				}
			}
		} catch(\Exception $e) {
			$this->logError($e->__toString());
		}
		return $res;
	}

	/**	returns the results of a query as an associative array
	*	@param $query
	*	@param $key optional key
	*/
	function fetchAssoc($query, $key = null) {
		$res = Array();
		try {
			if ($this->dbh) {
				$this->sth = $this->dbh->query($query);
				$this->sth->setFetchMode(\PDO::FETCH_ASSOC);
				while ($row = $this->sth->fetch()) {
					if ($key) $res[$row[$key]] = $row;
					else $res[] = $row;
				}
			}
		} catch(\Exception $e) {
			$this->logError($e->__toString());
		}
		return $res;
	}

	/**	returns the first result of the first record of a query
	*	@param $query
	*/
	function lookup($query) {
		$res = null;
		try {
			if ($this->dbh) {
				$this->sth = $this->dbh->query($query);
				$this->sth->setFetchMode(\PDO::FETCH_NUM);
				while ($row = $this->sth->fetch()) {
					return $row[0];
				}
			}
		} catch(\Exception $e) {
			$this->logError($e->__toString());
		}
		return $res;
	}

	/**	exports the query results to a csv file
	*	@param $filename
	*	@param $query
	*/
	function exportCSV($filename, $query) {
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=\"{$filename}\"");
		echo "\xEF\xBB\xBF";

		$handle = fopen("php://memory", 'r+');
		$results = $this->fetchNum($query);
		$columns = $this->getColumnMeta();

		$cols = array();
		foreach ($columns as $c) $cols[] = $c['name'];
		fputcsv($handle, $cols);

		foreach ($results as $r) {
			fputcsv($handle, $r);
		}

		rewind($handle);
		while ($line = fgets($handle)) echo $line;
		fclose($handle);

		exit();
	}

	/**	exports data to a csv file
	*	@param $filename
	*	@param $columns the column information
	*	@param $results the data to export
	*/
	function exportCSVdata($filename, $columns, $results) {
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=\"{$filename}\"");
		echo "\xEF\xBB\xBF";

		$handle = fopen("php://memory", 'r+');

		fputcsv($handle, $columns);

		foreach ($results as $r) {
			fputcsv($handle, $r);
		}

		rewind($handle);
		while ($line = fgets($handle)) echo $line;
		fclose($handle);

		exit();
	}

	/**	performs a mysql dump of the database to a file
	*	@param $filename
	*/
	function dump($filename) {
		$cmd = "mysqldump -u ".escapeshellarg($this->username)." -p".escapeshellarg($this->password)." ".escapeshellarg($this->database)." > ".escapeshellarg($filename);
		$last = exec($cmd, $output, $return_var);
	}

	function prepare($statement, $driver_options = array()) {
		try {
			if ($this->dbh) $this->sth = $this->dbh->prepare($statement, $driver_options);
		} catch(\Exception $e) {
			$this->logError($e->__toString());
		}
	}

	function bindParam($parameter, $variable, $data_type = \PDO::PARAM_STR, $length = 0, $driver_options = array()) {
		try {
			if ($this->sth) $this->sth->bindParam($parameter, $variable, $data_type, $length, $driver_options);
		} catch(\Exception $e) {
			$this->logError($e->__toString());
		}
	}

	function execute($input_parameters = null) {
		try {
			if ($this->sth) $this->sth->execute($input_parameters);
		} catch(\Exception $e) {
			$this->logError($e->__toString());
		}
	}

	function fetch($fetch_style = \PDO::FETCH_BOTH, $cursor_orientation = \PDO::FETCH_ORI_NEXT, $cursor_offset = 0) {
		try {
			return $this->sth ? $this->sth->fetch($fetch_style, $cursor_orientation, $cursor_offset) : null;
		} catch(\Exception $e) {
			$this->logError($e->__toString());
		}
		return null;
	}
}

}

?>