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

class Session
{
	// const SESSION_STARTED = true;
	// const SESSION_NOT_STARTED = false;

	private $sessionExpireSeconds = 300;	// the number of seconds of inactivity before a session expires

	// private static $instance;	    // the only possible instance of the class

	private $sessionID = null;
	// private $sessionState = self::SESSION_NOT_STARTED;
	private $sessionName = null;

	/**
	 * _ctor
	 *
	 * @param
	 * @return
	 **/
	public function __construct()
	{
		// $this->sessionState = self::SESSION_NOT_STARTED;
	}

	/**
	 * returns the instance of 'Session' (session is automatically initialized if it wasn't)
	 *   
	 * @param	void
	 * @return	object	the object instance
	 **/
	// public static function getInstance(Config $settings)
	// {
	// if (!isset(self::$instance)) {
	// self::$instance = new self($settings);
	// }

	// self::$instance->startSession();

	// return self::$instance;
	// }

	/**
	 * (re)starts the session
	 *   
	 * @param	void
	 * @return  bool	TRUE if the session has been initialized, else FALSE
	 **/

	public function start()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		if (session_status() == PHP_SESSION_ACTIVE) {
			$this->setSessionID();
		} else {
			die("FATAL ERROR: it was not possible to initiate a Session. Application aborted.");
			exit(0);
		}

		if (!$this->has('LAST_ACTIVE')) {
			$this->LAST_ACTIVE = time();
		}

		// $this->checkSessionIsNotExpired();

		// return $this->sessionState;
	}

	/**
	 * set session ID value
	 *
	 * @param	void
	 * @return	void
	 **/
	public function setSessionID()
	{
		$this->sessionID = session_id();
	}

	/**
	 * set session key and value
	 *
	 * @param	void
	 * @return	session ID string value
	 **/
	public function getSessionID()
	{
		return ($this->sessionID) ? $this->sessionID : null;
	}

	/**
	 * expires a session if inactive for a specified amount of time.
	 *
	 * @param  void
	 * @return void
	 */
	public function checkSessionIsNotExpired()
	{
		if ($this->timeLeft() <= 0) {
			$this->removeSession();
			return false;
		}

		return true;
	}

	/**
	 * expires a session if inactive for a specified amount of time.
	 *
	 * @param  void
	 * @return void
	 */
	public function timeElapsed()
	{
		return (time() - $this->LAST_ACTIVE);
	}

	/**
	 * expires a session if inactive for a specified amount of time.
	 *
	 * @param  void
	 * @return void
	 */
	public function timeLeft()
	{
		return $this->sessionExpireSeconds - $this->timeElapsed();
	}

	/**
	 * expires a session if inactive for a specified amount of time.
	 *
	 * @param  void
	 * @return void
	 */
	public function timeLeftFormatted()
	{
		$time_left = $this->timeLeft();
		$time_sign = ($time_left < 0) ? '-' : '';
		return $time_sign . str_pad(abs(intval($time_left / 60)), 2, '0', STR_PAD_LEFT) . ':' . str_pad(abs(($time_left % 60)), 2, '0', STR_PAD_LEFT);
	}

	/**
	 * has - check if a variable exists
	 *
	 * @param
	 * @return
	 **/
	public function has($key)
	{
		return isset($_SESSION[strtoupper($key)]);
	}

	/**
	 * _set
	 *
	 * @param
	 * @return
	 **/
	public function __set($key, $val)
	{
		if (is_string($key)) {
			$_SESSION[strtoupper($key)] = $val;
		}
	}

	/**
	 * set multiple keys value
	 *
	 * to set something like $_SESSION['key1']['key2']['key3'] = value
	 * $session->setMultiKey(array('key1', 'key2', 'key3'), 'value')
	 *
	 * @param
	 * @return
	 **/
	public function setMultiKey($keyArray, $val)
	{
		// if (is_array($keyArray)) {
		// $arrStr = "['" . implode("']['", strtoupper($keyArray)) . "']";
		// $_SESSION{$arrStr} = $val;
		// }
	}

	/**
	 * _get
	 *
	 * @param
	 * @return
	 **/
	public function __get($key)
	{
		if (is_string($key)) {
			// return (isset($_SESSION[strtoupper($key)])) ? $_SESSION[strtoupper($key)] : false;
			return $this->has($key) ? $_SESSION[strtoupper($key)] : false;
		}
	}

	/*
	*/

	/**
	 * get multiple keys value
	 *
	 * to get something like $_SESSION['key1']['key2']['key3']:
	 * $session->getMultiKey(array('key1', 'key2', 'key3'))
	 *
	 * @param
	 * @return
	 **/
	public function getMultiKey($keyArray)
	{
		// if (is_array($keyArray)) {
		// $arrStr = "['" . implode("']['", strtoupper($keyArray)) . "']";
		// return (isset($_SESSION{$arrStr})) ? $_SESSION{$arrStr} : false;
		// }
	}

	/**
	 * delete session key
	 *
	 * @param
	 * @return
	 **/
	public function removeKey($key)
	{
		if (is_string($key)) {
			if (isset($_SESSION[strtoupper($key)])) {
				unset($_SESSION[strtoupper($key)]);
				return true;
			}
		}
		return false;
	}

	/**
	 * delete multiple keys values
	 *
	 * @param
	 * @return
	 **/
	public function removeMultiKey($keyArray)
	{
		// if (is_array($keyArray)) {
		// $arrStr = "['" . implode("']['", strtoupper($keyArray)) . "']";
		// if (isset($_SESSION{$arrStr})) {
		// unset($_SESSION{$arrStr});
		// return true;
		// }
		// }
		// return false;
	}

	/**
	 * regenerate session id
	 *
	 * false new Id has the same value as the previous
	 * true new Id is different from the previous
	 *
	 * @param
	 * @return
	 **/
	public function regenerateId($destroyOldSession = false)
	{
		session_regenerate_id(false);

		if ($destroyOldSession) {
			$sid = session_id();	//  save the new session id and name
			session_write_close();	//  close all existing sessions
			session_id($sid);		//  re-open the new session
			$this->start();
			// self::getInstance();
		}
	}

	/**
	 * destroy session
	 *
	 * @param
	 * @return
	 **/
	public function removeSession()
	{
		$_SESSION = array();
		if (ini_get('session.use_cookies')) {
			$p = $this->getCookieParams();
			setcookie(session_name(), '', time() - 31536000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
		}
		session_unset();
		$removed = session_destroy();
		// $this->sessionState == self::SESSION_NOT_STARTED;

		return $removed;
	}

	/**
	 * Returns current session cookie parameters or an empty array.
	 * 
	 * @return array Associative array of session cookie parameters.
	 */
	public function getCookieParams()
	{
		return ((session_id() !== '') ? session_get_cookie_params() : []);
	}

	/**
	 * get the session name
	 *
	 * @param
	 * @return
	 **/
	public function getName()
	{
		return $this->sessionName;
	}
}
