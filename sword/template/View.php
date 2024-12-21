<?php

declare(strict_types=1);
/**
 * Sword -> the simple, extensible and incredibly fast PHP framework,
 *          enables anyone to rapidly build RESTful web applications.
 *
 * @copyright   Copyright (c) 2018,2024 RobertoSciarra <roberto.sciarra@yahoo.com>
 * @license     MIT, http://swordphp.com/license
 */

namespace sword\template;

/**
 * The View class represents output to be displayed. It provides
 * methods for managing view data and inserts the data into
 * view templates upon rendering.
 */
class View
{
    /**
     * Location of view templates.
     *
     * @var String
     */
    public $path;

    /**
     * File extension.
     *
     * @var String
     */
    public $extension = '.php';

    /**
     * View variables.
     *
     * @var Array
     */
    protected $vars = [];

    /**
     * Template file.
     *
     * @var String
     */
    private $template;

    /**
     * Constructor.
     *
     * @param string $path Path to templates directory
     * @return void
     */
    public function __construct(string $path = '.')
    {
        $this->path = $path;
    }

    /**
     * Gets a template variable.
     *
     * @param string $key Key
     * @return mixed Value
     */
    public function get(string $key)
    {
        return $this->vars[$key] ?? null;
    }

    /**
     * Sets a template variable.
     *
     * @param mixed  $key   Key
     * @param string $value Value
     * @return void
     */
    public function set($key, $value = null)
    {
        if (\is_array($key) || \is_object($key)) {
            foreach ($key as $k => $v) {
                $this->vars[$k] = $v;
            }
        } else {
            $this->vars[$key] = $value;
        }
    }

    /**
     * Checks if a template variable is set.
     *
     * @param string $key Key
     * @return boolean - true if key exists
     */
    public function has($key): boolean
    {
        return isset($this->vars[$key]);
    }

    /**
     * Unsets a template variable. If no key is passed in, clear all variables.
     *
     * @param string $key Key
     * @return void
     */
    public function clear(string $key = null)
    {
        if (null === $key) {
            $this->vars = [];
        } else {
            unset($this->vars[$key]);
        }
    }

    /**
     * Renders a template.
     *
     * @param string $file Template file
     * @param array  $data Template data
     * @return void
     * @throws \Exception if template not found
     */
    public function render(string $file, array $data = null)
    {
        $this->template = $this->getTemplate($file);

        if (!file_exists($this->template)) {
            throw new \Exception(__METHOD__ . " - Template file not found: " . $this->template . ".");
        }

        if (\is_array($data)) {
            $this->vars = array_merge($this->vars, $data);
        }

        extract($this->vars);

        include $this->template;
    }

    /**
     * Gets the output of a template.
     *
     * @param string $file Template file
     * @param array  $data Template data
     * @return string Output of template
     */
    public function fetch(string $file, array $data = null): string
    {
        ob_start();

        $this->render($file, $data);

        return ob_get_clean();
    }

    /**
     * Checks if a template file exists.
     *
     * @param string $file Template file
     * @return boolean - true if Template file exists
     */
    public function exists(string $file): boolean
    {
        return file_exists($this->getTemplate($file));
    }

    /**
     * Gets the full path to a template file.
     *
     * @param string $file Template file
     * @return string Template file location
     */
    public function getTemplate(string $file): string
    {
        $ext = $this->extension;

        if (!empty($ext) && (substr($file, -1 * \strlen($ext)) != $ext)) {
            $file .= $ext;
        }

        if (('/' == substr($file, 0, 1))) {
            return $file;
        }

        return $this->path . '/' . $file;
    }

    /**
     * Displays escaped output.
     *
     * @param string $str String to escape
     * @return string Escaped string
     */
    public function e(string $str): string
    {
        echo htmlentities($str);
    }
}
