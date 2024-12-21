<?php

declare(strict_types=1);
/**
 * Sword -> the simple, extensible and incredibly fast PHP framework,
 *          enables anyone to rapidly build RESTful web applications.
 *
 * @copyright   Copyright (c) 2018,2024 RobertoSciarra <roberto.sciarra@yahoo.com>
 * @license     MIT, http://swordphp.com/license
 */

use sword\core\Loader;

require_once __ENGINE__ . '/core/Loader.php';

Loader::autoload(true, [dirname(__DIR__)]);
