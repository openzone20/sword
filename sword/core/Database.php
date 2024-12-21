<?php

declare(strict_types=1);
/**
 * Sword -> the simple, extensible and incredibly fast PHP framework,
 *          enables anyone to rapidly build RESTful web applications.
 *
 * @copyright   Copyright (c) 2018,2024 RobertoSciarra <roberto.sciarra@yahoo.com>
 * @license     MIT, http://swordphp.com/license
 */

namespace sword\core;

use Sword;

/**
 * The Database class is responsible for managing input/output from a MySQL database.
 * It uses simple wrapper methods to reduce the nuumber of instructions needed to
 * execute SQL commands.
 */

class Database extends \PDO
{
	/*  */
	private static $PDOInstance = null;
	private static $db_delay_after_operation = .2;

	/**
	 *  _ctor
	 * 
	 *  @param void
	 *  @return void
	 **/
	public function __construct()
	{
		if (!self::$PDOInstance) {
			$options = [
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
				\PDO::ATTR_EMULATE_PREPARES => false
			];

			// \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"

			$db_dsn = Sword::get('db.driver');
			$db_dsn .= ":host=" . Sword::get('db.host');
			$db_dsn .= ";dbname=" . Sword::get('db.name');
			$db_dsn .= ";port=" . Sword::get('db.prot');
			$db_dsn .= ";charset=" . Sword::get('db.charset');

			try {
				self::$PDOInstance = parent::__construct($db_dsn, Sword::get('db.username'), Sword::get('db.password'), $options);
			} catch (\PDOException $e) {
				throw new \Exception("PDO Connection Error: " . $e->getMessage() . "<br/>");
				die("Program Aborted.");
			}
		}
		return self::$PDOInstance;
	}

	/**
	 *  call any PDO function not defined here
	 *
	 *  @param void
	 *  @return void
	 **/
	public function __call($method, $args)
	{
		$dbh = self::connect();
		return call_user_func_array([$dbh, $method], $args);
	}

	/**
	 *  disconnect form database once done
	 *
	 *  @param void
	 *  @return void
	 **/
	public function disconnect()
	{
		self::$PDOInstance = null;
	}

	/**
	 *  connect to database to execute a command
	 *
	 *  @param void
	 *  @return void
	 **/
	public static function connect()
	{
		if (is_null(self::$PDOInstance)) {
			self::$PDOInstance = new self();
		}
		// self::$PDOInstance->exec("SET CHARACTER SET 'utf8'");

		return self::$PDOInstance;
	}

	/**
	 *  read from database
	 *
	 *  @param void
	 *  @return void
	 **/
	public function query($sql, $params = false)
	{
		$operations = array("INSERT", "UPDATE", "DELETE", "TRUNCA", "DROP T", "CREATE");

		$return_count = (in_array(strtoupper(substr($sql, 0, 6)), $operations)) ? true : false;

		$dbh = self::connect();
		$stmt = $dbh->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
		// $result = $stmt->setFetchMode(\PDO::FETCH_ASSOC);

		if ($params) {
			$stmt->execute($params);
		} else {
			$stmt->execute();
		}

		$result = ($return_count) ? $stmt->rowCount() : $stmt->fetchAll();
		$stmt = null;

		self::disconnect();
		sleep(self::$db_delay_after_operation);

		return $result;
	}

	/**
	 *  truncate a table
	 *
	 *  @param void
	 *  @return void
	 **/
	public function truncate($table)
	{
		$cmnd = "TRUNCATE TABLE `" . $table . "`;";
		$result = self::read($cmnd);

		return $result;
	}

	/**
	 *  save data into the database
	 *  if there's a condition is an UPDATE, otherwise is an INSERT
	 *
	 *  @param void
	 *  @return void
	 **/
	public function save($table, $data, $condition = "")
	{
		$newID = ($condition == "") ? self::_insert($table, $data) : self::_update($table, $data, $condition);
		// sleep(self::$db_delay_after_operation);

		return $newID;
	}

	/**
	 *  delete from table
	 *
	 *  @param void
	 *  @return void
	 **/
	public function delete($table, $condition = "")
	{
		if ($condition == "") {
			return false;
		}
		$dbh = self::connect();
		$sql  = 'DELETE FROM `' . $table . '` WHERE ' . $condition . '';
		$ok = $dbh->exec($sql);

		self::disconnect();
		sleep(self::$db_delay_after_operation);

		return $ok;
	}

	/**
	 *  _insert data into the database
	 *
	 *  @param void
	 *  @return void
	 **/
	private function _insert($table, $data = array())
	{
		if (!is_array($data) || !count($data)) {
			return false;
		}
		$dbh = self::connect();
		$bind = ':' . implode(',:', array_keys($data));
		// $fields = implode(',', array_keys($data));
		$fields = "";
		foreach ($data as $key => $value) {
			$fields .= "`" . $key . "`,";
		}
		$fields = rtrim($fields, ",");
		$sql  = 'INSERT INTO `' . $table . '` (' . $fields . ') VALUES (' . $bind . ')';
		$stmt = $dbh->prepare($sql);
		$ok = $stmt->execute(array_combine(explode(',', $bind), array_values($data)));
		$stmt = null;

		self::disconnect();
		sleep(self::$db_delay_after_operation);

		return $ok;
	}

	/**
	 *  _update data into the database
	 *
	 *  @param void
	 *  @return void
	 **/
	private function _update($table, $data = array(), $condition = "")
	{
		if (!is_array($data) || !count($data)) {
			return false;
		}
		if ($condition == "") {
			return false;
		}
		$dbh = self::connect();
		$values = array();
		$sql  = 'UPDATE `' . $table . '` SET ';
		foreach ($data as $key => $value) {
			$sql .= "`" . $key . "` =:" . $key . ", ";
			$values[':' . $key] = $value;
		}
		$sql = rtrim($sql, ", ");
		$sql .= ' WHERE ' . $condition . '';
		$stmt = $dbh->prepare($sql);
		$ok = $stmt->execute($values);
		$stmt = null;

		self::disconnect();
		sleep(self::$db_delay_after_operation);

		return $ok;
	}
}
