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

class Controller
{
    public $beforeRender;
    public $afterRender;

    protected $controllerData = [];

    /**
     *
     *
     * 
     **/
    public function __construct()
    {
        $this->controllerData = [
            'appName' => Sword::get('app.name'),
            'appVersion' => Sword::get('app.version'),
            'appWebAssets' => Sword::get('app.web.assets'),
            'appWebAddress' => Sword::get('app.web.address')
        ];

        // load the language translations
        $lang = "en_GB";    // Session::get('preferred_language')
        $language_file = __APP__ . '/config/language/' . $lang . '.php';
        if (!is_readable($language_file)) {
            bug("Configuration file " . $language_file . " doesn't exist");
            exit(0);
        }
        $languageData = include $language_file;

        // merge the translations to the main data array
        $this->controllerData = array_merge($this->controllerData, $languageData);

        // $this->checkUserIsLoggedIn();
    }

    /**
     *
     *
     * 
     **/
    public function showTemplate($template, $data = [])
    {
        if (file_exists(Sword::get('sword.views.path') . "/" . $template . Sword::get('sword.views.extension'))) {
            // merge data coming from the child controller to the main data array
            $finalData = array_merge($this->controllerData, $data);

            if ($this->beforeRender instanceof \Closure) {
                $t = $this->beforeRender;
                $t($this);
            }

            Sword::view()->display($template . Sword::get('sword.views.extension'), $finalData);
            // Sword::render($template . '.php', $data);

            if ($this->afterRender instanceof \Closure) {
                $t = $this->afterRender;
                $t($this);
            }
        } else {
            $this->notFound();
        }
    }

    /**
     *
     *
     * 
     **/
    public function notFound()
    {
        $data = [
            'requestedPage' => $_SERVER["REQUEST_URI"]
        ];

        $this->showTemplate('404', $data);
    }

    /**
     *
     *
     * 
     **/
    public function infoPHP()
    {
        phpinfo();
    }
}
