<?php

declare(strict_types=1);
/**
 * Sword -> the simple, extensible and incredibly fast PHP framework,
 *          enables anyone to rapidly build RESTful web applications.
 *
 * @copyright   Copyright (c) 2018,2024 RobertoSciarra <roberto.sciarra@yahoo.com>
 * @license     MIT, http://swordphp.com/license
 */

namespace sword\util;

use Sword;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

class Log2file
{
	/**
	 * Logger internal object
	 *
	 * @var Object
	 */
	private $logger;

	/**
	 * settings retrived from Sword
	 *
	 * @var Object
	 */
	private $config;

	/**
	 * Mapped methods to call the real ones
	 *
	 * @var Array
	 */
	private $mapped_methods = [
		'debug' => 'debug',
		'info' => 'info',
		'notice' => 'notice',
		'warning' => 'warning',
		'error' => 'error',
		'critical' => 'critical',
		'alert' => 'alert',
		'emergency' => 'emergency'
	];

	/**
	 * __ctor
	 *
	 * @param void
	 * @return void
	 */
	public function __construct(string $channel = "Application")
	{
		if (CONFIG_LOGS['enabled'] == true) {
			$this->logger = new Logger(ucfirst($channel));
			$this->logger->pushHandler(new StreamHandler(CONFIG_LOGS['path'] . '/' . CONFIG_LOGS['filename'] . '.' . CONFIG_LOGS['extension'], Level::Debug));
			$this->logger->pushHandler(new FirePHPHandler());
		}
	}

	/**
	 * Magic method
	 *
	 * @param string $method the mapped method
	 * @param mixed $args arguments to be passed to the real method
	 * @return void
	 */
	public function __call(string $method, $args)
	{
		if (!in_array($method, array_keys($this->mapped_methods))) {
			throw new \Exception("Logger method " . $method . " is not mapped.");
		}

		return call_user_func_array([$this->logger, $this->mapped_methods[$method]], $args);
	}
}
