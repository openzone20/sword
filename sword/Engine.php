<?php

declare(strict_types=1);
/**
 * Sword -> the simple, extensible and incredibly fast PHP framework,
 *          enables anyone to rapidly build RESTful web applications.
 *
 * @copyright   Copyright (c) 2018,2024 RobertoSciarra <roberto.sciarra@yahoo.com>
 * @license     MIT, http://swordphp.com/license
 */

namespace sword;

use \DateTime;
use \ErrorException;
use \Exception;
use sword\core\Database;
use sword\core\Dispatcher;
use sword\core\Loader;
use sword\core\Session;
use sword\net\Request;
use sword\net\Response;
use sword\net\Router;
use sword\template\View;
use sword\util\Log2file;
use \Throwable;

/**
 * The Engine class contains the core functionality of the framework.
 * It is responsible for loading an HTTP request, running the assigned services,
 * and generating an HTTP response.
 *
 * Core methods
 *
 * @method void start() Starts engine
 * @method void stop() Stops framework and outputs current response
 * @method void halt(int $code = 200, string $message = '') Stops processing and returns a given response.
 * @method void route(string $pattern, callable $callback, bool $pass_route = false) Routes a URL to a callback function.
 * @method Router router() Gets router
 *
 * Views
 * @method void render(string $file, array $data = null, string $key = null) Renders template
 * @method View view() Gets current view
 *
 * Request-response
 * @method Request request() Gets current request
 * @method Response response() Gets current response
 * @method void error(Exception $e) Sends an HTTP 500 response for any errors.
 * @method void notFound() Sends an HTTP 404 response when a URL is not found.
 * @method void redirect(string $url, int $code = 303)  Redirects the current request to another URL.
 * @method void json(mixed $data, int $code = 200, bool $encode = true, string $charset = 'utf-8', int $option = 0) Sends a JSON response.
 * @method void jsonp(mixed $data, string $param = 'jsonp', int $code = 200, bool $encode = true, string $charset = 'utf-8', int $option = 0) Sends a JSONP response.
 *
 * HTTP caching
 * @method void etag($id, string $type = 'strong') Handles ETag HTTP caching.
 * @method void lastModified(int $time) Handles last modified HTTP caching.
 */
class Engine
{
    /**
     * Stored variables.
     */
    protected array $vars;

    /**
     * Class loader.
     */
    protected Loader $loader;

    /**
     * Event dispatcher.
     */
    protected Dispatcher $dispatcher;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->vars = [];

        $this->loader = new Loader();
        $this->dispatcher = new Dispatcher();
        // $this->logger = new Log2file();

        $this->init();
    }

    /**
     * Handles calls to class methods.
     *
     * @param string $name   Method name
     * @param array  $params Method parameters
     *
     * @throws Exception
     *
     * @return mixed Callback results
     */
    public function __call(string $name, array $params)
    {
        $callback = $this->dispatcher->get($name);

        if (\is_callable($callback)) {
            return $this->dispatcher->run($name, $params);
        }

        if (!$this->loader->get($name)) {
            throw new \Exception(__METHOD__ . ": "  . $name  . " must be a mapped method.");
        }

        $shared = empty($params) || $params[0];

        return $this->loader->load($name, $shared);
    }

    // Core Methods

    /**
     * Initializes the framework.
     */
    public function init(): void
    {
        static $initialized = false;
        $self = $this;

        if ($initialized) {
            $this->vars = [];
            $this->loader->reset();
            $this->dispatcher->reset();
        }

        // Register default components
        $this->loader->register('request', Request::class);
        $this->loader->register('response', Response::class);
        $this->loader->register('router', Router::class);
        $this->loader->register('logger', Log2file::class);
        $this->loader->register('db', Database::class);
        $this->loader->register('session', Session::class);
        $this->loader->register('view', View::class, [], function ($view) use ($self) {
            $view->path = $self->get('sword.views.path');
            $view->extension = $self->get('sword.views.extension');
        });

        // Register framework methods
        $methods = [
            'start',
            'stop',
            'route',
            'halt',
            'error',
            'notFound',
            'render',
            'redirect',
            'etag',
            'lastModified',
            'json',
            'jsonp',
            'post',
            'put',
            'patch',
            'delete',
        ];
        foreach ($methods as $name) {
            $this->dispatcher->set($name, [$this, '_' . $name]);
        }

        // Default configuration settings
        $this->set('sword.base_url');
        $this->set('sword.case_sensitive', false);
        $this->set('sword.handle_errors', true);
        $this->set('sword.log_errors', false);
        $this->set('sword.views.path', './views');
        $this->set('sword.views.extension', '.php');
        $this->set('sword.content_length', true);

        // Startup configuration
        $this->before('start', function () use ($self) {
            // Enable error handling
            if ($self->get('sword.handle_errors')) {
                set_error_handler([$self, 'handleError']);
                set_exception_handler([$self, 'handleException']);
            }

            // Set case-sensitivity
            $self->router()->case_sensitive = $self->get('sword.case_sensitive');
            // Set Content-Length
            $self->response()->content_length = $self->get('sword.content_length');
        });

        $initialized = true;
    }

    /**
     * Custom error handler. Converts errors into exceptions.
     *
     * @param int    $errno   Error number
     * @param string $errstr  Error string
     * @param string $errfile Error file name
     * @param int    $errline Error file line number
     * @return void
     * @throws ErrorException
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): void
    {
        if ($errno & error_reporting()) {
            throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
        }
    }

    /**
     * Custom exception handler. Logs exceptions.
     *
     * @param Exception $e Thrown exception
     * @erturn void
     */
    public function handleException($e): void
    {
        if ($this->get('sword.log_errors')) {
            // $logFileName = $this->get('sword.log.path') . '/' . $this->get('sword.log.filename') . '.' . $this->get('sword.log.extension');
            // error_log($e->getMessage());
            $message = "(" . get_class($e) . ") " . $e->getMessage() . " in " . $e->getFile() . "(" . $e->getLine() . ")" . PHP_EOL;
            $date = \DateTime::createFromFormat('U.u', (string) microtime(true))->setTimezone(new \DateTimeZone(date_default_timezone_get()))->format('Y-m-d\TH:i:s.uP');
            $this->logger()->emergency(__METHOD__ . "(" . __LINE__ . "): " . $message);
        }

        $this->error($e);
    }

    /**
     * Maps a callback to a framework method.
     *
     * @param string   $name     Method name
     * @param callback $callback Callback function
     * @return void
     * @throws Exception If trying to map over a framework method
     */
    public function map(string $name, callable $callback_function): void
    {
        if (method_exists($this, $name)) {
            throw new \Exception('Cannot override an existing framework method.');
        }

        $this->dispatcher->set($name, $callback_function);
    }

    /**
     * Registers a class to a framework method.
     *
     * @param string        $name     Method name
     * @param string        $class    Class name
     * @param array         $params   Class initialization parameters
     * @param callable|null $callback $callback Function to call after object instantiation
     * @return void
     * @throws Exception If trying to map over a framework method
     */
    public function register(string $name, string $class, array $params = [], ?callable $callback = null): void
    {
        if (method_exists($this, $name)) {
            throw new \Exception('Cannot override an existing framework method.');
        }

        $this->loader->register($name, $class, $params, $callback);
    }

    /**
     * Adds a pre-filter to a method.
     *
     * @param string   $name     Method name
     * @param callback $callback Callback function
     * @return void
     */
    public function before(string $name, callable $callback_function): void
    {
        $this->dispatcher->hook($name, 'before', $callback_function);
    }

    /**
     * Adds a post-filter to a method.
     *
     * @param string   $name     Method name
     * @param callback $callback Callback function
     * @return void
     */
    public function after(string $name, callable $callback_function): void
    {
        $this->dispatcher->hook($name, 'after', $callback_function);
    }

    /**
     * Gets a variable.
     *
     * @param string|null $key Key
     * @return array|mixed|null
     */
    public function get(?string $key = null)
    {
        if (null === $key) {
            return $this->vars;
        }

        return $this->vars[$key] ?? null;
    }

    /**
     * Sets a variable.
     *
     * @param mixed      $key   Key
     * @param mixed|null $value Value
     * @return void
     */
    public function set($key, $value = null): void
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
     * Checks if a variable has been set.
     *
     * @param string $key Key
     * @return bool Variable status
     */
    public function has(string $key): bool
    {
        return isset($this->vars[$key]);
    }

    /**
     * Unsets a variable. If no key is passed in, clear all variables.
     *
     * @param string|null $key Key
     * @return void
     */
    public function clear(?string $key = null): void
    {
        if (null === $key) {
            $this->vars = [];
        } else {
            unset($this->vars[$key]);
        }
    }

    /**
     * Adds a path for class autoloading.
     *
     * @param string $dir Directory path
     * @return void
     */
    public function path(string $dir): void
    {
        $this->loader->addDirectory($dir);
    }

    // Extensible Methods

    /**
     * Starts the framework.
     *
     * @param void
     * @return void
     * @throws Exception
     */
    public function _start(): void
    {
        $dispatched = false;
        $self = $this;
        $request = $this->request();
        $response = $this->response();
        $router = $this->router();

        // Allow filters to run
        $this->after('start', function () use ($self) {
            $self->stop();
        });

        // Flush any existing output
        if (ob_get_length() > 0) {
            $response->write(ob_get_clean());
        }

        // Enable output buffering
        ob_start();

        // Route the request
        while ($route = $router->route($request)) {
            $params = array_values($route->params);

            // Add route info to the parameter list
            if ($route->pass) {
                $params[] = $route;
            }

            // Call route handler
            $continue = $this->dispatcher->execute(
                $route->callback,
                $params
            );

            $dispatched = true;

            if (!$continue) {
                break;
            }

            $router->next();

            $dispatched = false;
        }

        if (!$dispatched) {
            $this->notFound();
        }
    }

    /**
     * Sends an HTTP 500 response for any errors.
     *
     * @param void
     * @return void
     * @param Throwable $e Thrown exception
     */
    public function _error($e): void
    {
        $msg = sprintf(
            '<h1>500 Internal Server Error</h1>' .
                '<h3>%s (%s)</h3>' .
                '<pre>%s</pre>',
            $e->getMessage(),
            $e->getCode(),
            $e->getTraceAsString()
        );

        try {
            $this->response()
                ->clear()
                ->status(500)
                ->write($msg)
                ->send();
        } catch (\Throwable $t) {
            exit($msg);
        }
    }

    /**
     * Stops the framework and outputs the current response.
     *
     * @param int|null $code HTTP status code
     * @return void
     * @throws Exception
     */
    public function _stop(?int $code = null): void
    {
        $response = $this->response();

        if (!$response->sent()) {
            if (null !== $code) {
                $response->status($code);
            }

            $response->write(ob_get_clean());

            $response->send();
        }
    }

    /**
     * Routes a URL to a callback function.
     *
     * @param string   $pattern    URL pattern to match
     * @param callback $callback   Callback function
     * @param bool     $pass_route Pass the matching route object to the callback
     * @return void
     */
    public function _route(string $pattern, callable $callback_function, bool $pass_route = false): void
    {
        $this->router()->map($pattern, $callback_function, $pass_route);
    }

    /**
     * Routes a URL to a callback function.
     *
     * @param string   $pattern    URL pattern to match
     * @param callback $callback   Callback function
     * @param bool     $pass_route Pass the matching route object to the callback
     * @return void
     */
    public function _post(string $pattern, callable $callback_function, bool $pass_route = false): void
    {
        $this->router()->map('POST ' . $pattern, $callback_function, $pass_route);
    }

    /**
     * Routes a URL to a callback function.
     *
     * @param string   $pattern    URL pattern to match
     * @param callback $callback   Callback function
     * @param bool     $pass_route Pass the matching route object to the callback
     * @return void
     */
    public function _put(string $pattern, callable $callback_function, bool $pass_route = false): void
    {
        $this->router()->map('PUT ' . $pattern, $callback_function, $pass_route);
    }

    /**
     * Routes a URL to a callback function.
     *
     * @param string   $pattern    URL pattern to match
     * @param callback $callback   Callback function
     * @param bool     $pass_route Pass the matching route object to the callback
     * @return void
     */
    public function _patch(string $pattern, callable $callback_function, bool $pass_route = false): void
    {
        $this->router()->map('PATCH ' . $pattern, $callback_function, $pass_route);
    }

    /**
     * Routes a URL to a callback function.
     *
     * @param string   $pattern    URL pattern to match
     * @param callback $callback   Callback function
     * @param bool     $pass_route Pass the matching route object to the callback
     * @return void
     */
    public function _delete(string $pattern, callable $callback_function, bool $pass_route = false): void
    {
        $this->router()->map('DELETE ' . $pattern, $callback_function, $pass_route);
    }

    /**
     * Stops processing and returns a given response.
     *
     * @param int    $code    HTTP status code
     * @param string $message Response message
     * @return void
     */
    public function _halt(int $code = 200, string $message = ''): void
    {
        $this->response()
            ->clear()
            ->status($code)
            ->write($message)
            ->send();
        exit();
    }

    /**
     * Sends an HTTP 404 response when a URL is not found.
     *
     * @param void
     * @return void
     */
    public function _notFound(): void
    {
        $this->response()
            ->clear()
            ->status(404)
            ->write(
                '<h1>404 Not Found</h1>' .
                    '<h3>The page you have requested could not be found.</h3>' .
                    str_repeat(' ', 512)
            )
            ->send();
    }

    /**
     * Redirects the current request to another URL.
     *
     * @param string $url  URL
     * @param int    $code HTTP status code
     * @return void
     */
    public function _redirect(string $url, int $code = 303): void
    {
        $base = $this->get('sword.base_url');

        if (null === $base) {
            $base = $this->request()->base;
        }

        // Append base url to redirect url
        if ('/' !== $base && false === strpos($url, '://')) {
            $url = $base . preg_replace('#/+#', '/', '/' . $url);
        }

        $this->response()
            ->clear()
            ->status($code)
            ->header('Location', $url)
            ->send();
    }

    /**
     * Renders a template.
     *
     * @param string      $file Template file
     * @param array|null  $data Template data
     * @param string|null $key  View variable name
     * @return void
     * @throws Exception
     */
    public function _render(string $file, ?array $data = null, ?string $key = null): void
    {
        if (null !== $key) {
            $this->view()->set($key, $this->view()->fetch($file, $data));
        } else {
            $this->view()->render($file, $data);
        }
    }

    /**
     * Sends a JSON response.
     *
     * @param mixed  $data    JSON data
     * @param int    $code    HTTP status code
     * @param bool   $encode  Whether to perform JSON encoding
     * @param string $charset Charset
     * @param int    $option  Bitmask Json constant such as JSON_HEX_QUOT
     * @return void
     * @throws Exception
     */
    public function _json($data, int $code = 200, bool $encode = true, string $charset = 'utf-8', int $option = 0): void
    {
        $json = $encode ? json_encode($data, $option) : $data;

        $this->response()
            ->status($code)
            ->header('Content-Type', 'application/json; charset=' . $charset)
            ->write($json)
            ->send();
    }

    /**
     * Sends a JSONP response.
     *
     * @param mixed  $data    JSON data
     * @param string $param   Query parameter that specifies the callback name.
     * @param int    $code    HTTP status code
     * @param bool   $encode  Whether to perform JSON encoding
     * @param string $charset Charset
     * @param int    $option  Bitmask Json constant such as JSON_HEX_QUOT
     * @return void
     * @throws Exception
     */
    public function _jsonp($data, string $param = 'jsonp', int $code = 200, bool $encode = true, string $charset = 'utf-8', int $option = 0): void
    {
        $json = $encode ? json_encode($data, $option) : $data;

        $callback = $this->request()->query[$param];

        $this->response()
            ->status($code)
            ->header('Content-Type', 'application/javascript; charset=' . $charset)
            ->write($callback . '(' . $json . ');')
            ->send();
    }

    /**
     * Handles ETag HTTP caching.
     *
     * @param string $id   ETag identifier
     * @param string $type ETag type
     * @return void
     */
    public function _etag(string $id, string $type = 'strong'): void
    {
        $id = (('weak' === $type) ? 'W/' : '') . $id;

        $this->response()->header('ETag', $id);

        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $id) {
            $this->halt(304);
        }
    }

    /**
     * Handles last modified HTTP caching.
     *
     * @param int $time Unix timestamp
     * @return void
     */
    public function _lastModified(int $time): void
    {
        $this->response()->header('Last-Modified', gmdate('D, d M Y H:i:s \G\M\T', $time));

        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $time) {
            $this->halt(304);
        }
    }
}
