<?php

declare(strict_types=1);
/**
 * Sword -> the simple, extensible and incredibly fast PHP framework,
 *          enables anyone to rapidly build RESTful web applications.
 *
 * @copyright   Copyright (c) 2018,2024 RobertoSciarra <roberto.sciarra@yahoo.com>
 * @license     MIT, http://swordphp.com/license
 */

// get all the folder names in the current directory - assumption is:
// arr[0] = application folder
// arr[1] = Sword framework folder
// arr[2] = vendor folder
// so they should have been named wisely...
$elements = scandir('.'); // get all elements in current directory
$folders = [];
foreach ($elements as $element) {
    // accept directories only, escluding '.' and '..'
    if (is_dir($element) && $element !== '.' && $element !== '..') {
        $folders[] = $element;
    }
}

defined('__ROOT__') || define('__ROOT__', str_replace('\\', '/', dirname(__FILE__)));
// defined('__SUBDIR__') || define('__SUBDIR__', str_replace(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '', __ROOT__));
defined('__APP__') || define('__APP__', __ROOT__ . '/' . $folders[0]);
defined('__FRAMEWORK__') || define('__FRAMEWORK__', __ROOT__ . '/' . $folders[1]);
defined('__VENDOR__') || define('__VENDOR__', __ROOT__ . '/' . $folders[2]);

// read a configuration file which contains the name of the framwork engine as parameter
$settingsFile = __ROOT__ . '/index.settings.php';
if (is_readable($settingsFile)) {
    $settings = include $settingsFile;
    if (isset($settings) && is_array($settings)) {
        echo $settings['frameworkEngine']; // Esempio di accesso a un valore
    } else {
        die("An essential configuration file is corrupted. Current execution aborted.");
    }
} else {
    die("An essential configuration file has not been found. Current execution aborted.");
}

defined('__ENGINE__') || define('__ENGINE__', 'sword');

date_default_timezone_set('Europe/London');


// require the framework main file
require './sword/Sword.php';


// if the ‘vendor’ folder exists then use Composer autoload
if (is_dir(__VENDOR__)) {
    require __ROOT__ . '/' . $folder[2] . '/autoload.php'; // using Composer, require the vendor autoloader
} else {
    require __ROOT__ . '/' . __ENGINE__ . '/Sword.php'; // not using Composer, load the framework directly
}


/**
 * show debug information on screen
 *
 * @return void
 */
function bug(): void
{
    $argv = func_get_args();
    $args_num = func_num_args();

    echo "<pre>";

    foreach ($argv as $arg) {
        if (is_array($arg)) {
            print_r($arg);
            echo PHP_EOL;
        } else {
            if (is_null($arg)) {
                echo "null", PHP_EOL;
            } else {
                echo $arg, PHP_EOL;
            }
        }
    }

    echo "<br /><hr />]=--> Project powered by <b>" . ucwords(__ENGINE__) . "</b> <--=[   =-=-=-=-=-=   " . date('D, j M Y, H:i:s (T)', time());
    exit(0);
}
