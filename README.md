# What is Sword?

Sword is a fast, simple, extensible framework for PHP. Sword enables you to
quickly and easily build RESTful web applications.

```php
require 'Sword/Sword.php';

Sword::route('/', function(){
    echo 'hello world!';
});

Sword::start();
```

[Learn more](https://docs.flightphp.com/learn)

# Requirements

Sword requires `PHP 7.4` or greater.

# License

Sword is released under the [BSD 3-Clause](https://github.com/openzone20/sword/blob/default/LICENSE) license.

# Installation

1\. Download the files.

If you're using [Composer](https://getcomposer.org/), you can run the following command:

```
composer require openzone20/Sword
```

OR you can [download](https://github.com/openzone20/sword/archive/refs/heads/default.zip) them directly
and extract them to your web directory.

2\. Configure your webserver.

For _Apache_, edit your `.htaccess` file with the following:

```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**Note**: If you need to use Sword in a subdirectory add the line `RewriteBase /subdir/` just after `RewriteEngine On`.

For _Nginx_, add the following to your server declaration:

```
server {
    location / {
        try_files $uri $uri/ /index.php;
    }
}
```

3\. Create your `index.php` file.

First include the framework.

```php
require 'sword/Sword.php';
```

If you're using Composer, run the autoloader instead.

```php
require 'vendor/autoload.php';
```

Then define a route and assign a function to handle the request.

```php
Sword::route('/', function(){
    echo 'hello world!';
});
```

Finally, start the framework.

```php
Sword::start();
```

# Routing

Routing in Sword is done by matching a URL pattern with a callback function.

```php
Sword::route('/', function(){
    echo 'hello world!';
});
```

The callback can be any object that is callable. So you can use a regular function:

```php
function hello(){
    echo 'hello world!';
}

Sword::route('/', 'hello');
```

Or a class method:

```php
class Greeting {
    public static function hello() {
        echo 'hello world!';
    }
}

Sword::route('/', array('Greeting', 'hello'));
```

Or an object method:

```php
class Greeting
{
    public function __construct() {
        $this->name = 'John Doe';
    }

    public function hello() {
        echo "Hello, {$this->name}!";
    }
}

$greeting = new Greeting();

Sword::route('/', array($greeting, 'hello'));
```

Routes are matched in the order they are defined. The first route to match a
request will be invoked.

## Method Routing

By default, route patterns are matched against all request methods. You can respond
to specific methods by placing an identifier before the URL.

```php
Sword::route('GET /', function(){
    echo 'I received a GET request.';
});

Sword::route('POST /', function(){
    echo 'I received a POST request.';
});
```

You can also map multiple methods to a single callback by using a `|` delimiter:

```php
Sword::route('GET|POST /', function(){
    echo 'I received either a GET or a POST request.';
});
```

## Regular Expressions

You can use regular expressions in your routes:

```php
Sword::route('/user/[0-9]+', function(){
    // This will match /user/1234
});
```

## Named Parameters

You can specify named parameters in your routes which will be passed along to
your callback function.

```php
Sword::route('/@name/@id', function($name, $id){
    echo "hello, $name ($id)!";
});
```

You can also include regular expressions with your named parameters by using
the `:` delimiter:

```php
Sword::route('/@name/@id:[0-9]{3}', function($name, $id){
    // This will match /bob/123
    // But will not match /bob/12345
});
```

Matching regex groups `()` with named parameters isn't supported.

## Optional Parameters

You can specify named parameters that are optional for matching by wrapping
segments in parentheses.

```php
Sword::route('/blog(/@year(/@month(/@day)))', function($year, $month, $day){
    // This will match the following URLS:
    // /blog/2012/12/10
    // /blog/2012/12
    // /blog/2012
    // /blog
});
```

Any optional parameters that are not matched will be passed in as NULL.

## Wildcards

Matching is only done on individual URL segments. If you want to match multiple
segments you can use the `*` wildcard.

```php
Sword::route('/blog/*', function(){
    // This will match /blog/2000/02/01
});
```

To route all requests to a single callback, you can do:

```php
Sword::route('*', function(){
    // Do something
});
```

## Passing

You can pass execution on to the next matching route by returning `true` from
your callback function.

```php
Sword::route('/user/@name', function($name){
    // Check some condition
    if ($name != "Bob") {
        // Continue to next route
        return true;
    }
});

Sword::route('/user/*', function(){
    // This will get called
});
```

## Route Info

If you want to inspect the matching route information, you can request for the route
object to be passed to your callback by passing in `true` as the third parameter in
the route method. The route object will always be the last parameter passed to your
callback function.

```php
Sword::route('/', function($route){
    // Array of HTTP methods matched against
    $route->methods;

    // Array of named parameters
    $route->params;

    // Matching regular expression
    $route->regex;

    // Contains the contents of any '*' used in the URL pattern
    $route->splat;
}, true);
```

# Extending

Sword is designed to be an extensible framework. The framework comes with a set
of default methods and components, but it allows you to map your own methods,
register your own classes, or even override existing classes and methods.

## Mapping Methods

To map your own custom method, you use the `map` function:

```php
// Map your method
Sword::map('hello', function($name){
    echo "hello $name!";
});

// Call your custom method
Sword::hello('Bob');
```

## Registering Classes

To register your own class, you use the `register` function:

```php
// Register your class
Sword::register('user', 'User');

// Get an instance of your class
$user = Sword::user();
```

The register method also allows you to pass along parameters to your class
constructor. So when you load your custom class, it will come pre-initialized.
You can define the constructor parameters by passing in an additional array.
Here's an example of loading a database connection:

```php
// Register class with constructor parameters
Sword::register('db', 'PDO', array('mysql:host=localhost;dbname=test','user','pass'));

// Get an instance of your class
// This will create an object with the defined parameters
//
//     new PDO('mysql:host=localhost;dbname=test','user','pass');
//
$db = Sword::db();
```

If you pass in an additional callback parameter, it will be executed immediately
after class construction. This allows you to perform any set up procedures for your
new object. The callback function takes one parameter, an instance of the new object.

```php
// The callback will be passed the object that was constructed
Sword::register('db', 'PDO', array('mysql:host=localhost;dbname=test','user','pass'), function($db){
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
});
```

By default, every time you load your class you will get a shared instance.
To get a new instance of a class, simply pass in `false` as a parameter:

```php
// Shared instance of the class
$shared = Sword::db();

// New instance of the class
$new = Sword::db(false);
```

Keep in mind that mapped methods have precedence over registered classes. If you
declare both using the same name, only the mapped method will be invoked.

# Overriding

Sword allows you to override its default functionality to suit your own needs,
without having to modify any code.

For example, when Sword cannot match a URL to a route, it invokes the `notFound`
method which sends a generic `HTTP 404` response. You can override this behavior
by using the `map` method:

```php
Sword::map('notFound', function(){
    // Display custom 404 page
    include 'errors/404.html';
});
```

Sword also allows you to replace core components of the framework.
For example you can replace the default Router class with your own custom class:

```php
// Register your custom class
Sword::register('router', 'MyRouter');

// When Sword loads the Router instance, it will load your class
$myrouter = Sword::router();
```

Framework methods like `map` and `register` however cannot be overridden. You will
get an error if you try to do so.

# Filtering

Sword allows you to filter methods before and after they are called. There are no
predefined hooks you need to memorize. You can filter any of the default framework
methods as well as any custom methods that you've mapped.

A filter function looks like this:

```php
function(&$params, &$output) {
    // Filter code
}
```

Using the passed in variables you can manipulate the input parameters and/or the output.

You can have a filter run before a method by doing:

```php
Sword::before('start', function(&$params, &$output){
    // Do something
});
```

You can have a filter run after a method by doing:

```php
Sword::after('start', function(&$params, &$output){
    // Do something
});
```

You can add as many filters as you want to any method. They will be called in the
order that they are declared.

Here's an example of the filtering process:

```php
// Map a custom method
Sword::map('hello', function($name){
    return "Hello, $name!";
});

// Add a before filter
Sword::before('hello', function(&$params, &$output){
    // Manipulate the parameter
    $params[0] = 'Fred';
});

// Add an after filter
Sword::after('hello', function(&$params, &$output){
    // Manipulate the output
    $output .= " Have a nice day!";
});

// Invoke the custom method
echo Sword::hello('Bob');
```

This should display:

    Hello Fred! Have a nice day!

If you have defined multiple filters, you can break the chain by returning `false`
in any of your filter functions:

```php
Sword::before('start', function(&$params, &$output){
    echo 'one';
});

Sword::before('start', function(&$params, &$output){
    echo 'two';

    // This will end the chain
    return false;
});

// This will not get called
Sword::before('start', function(&$params, &$output){
    echo 'three';
});
```

Note, core methods such as `map` and `register` cannot be filtered because they
are called directly and not invoked dynamically.

# Variables

Sword allows you to save variables so that they can be used anywhere in your application.

```php
// Save your variable
Sword::set('id', 123);

// Elsewhere in your application
$id = Sword::get('id');
```

To see if a variable has been set you can do:

```php
if (Sword::has('id')) {
     // Do something
}
```

You can clear a variable by doing:

```php
// Clears the id variable
Sword::clear('id');

// Clears all variables
Sword::clear();
```

Sword also uses variables for configuration purposes.

```php
Sword::set('Sword.log_errors', true);
```

# Views

Sword provides some basic templating functionality by default. To display a view
template call the `render` method with the name of the template file and optional
template data:

```php
Sword::render('hello.php', array('name' => 'Bob'));
```

The template data you pass in is automatically injected into the template and can
be reference like a local variable. Template files are simply PHP files. If the
content of the `hello.php` template file is:

```php
Hello, '<?php echo $name; ?>'!
```

The output would be:

    Hello, Bob!

You can also manually set view variables by using the set method:

```php
Sword::view()->set('name', 'Bob');
```

The variable `name` is now available across all your views. So you can simply do:

```php
Sword::render('hello');
```

Note that when specifying the name of the template in the render method, you can
leave out the `.php` extension.

By default Sword will look for a `views` directory for template files. You can
set an alternate path for your templates by setting the following config:

```php
Sword::set('Sword.views.path', '/path/to/views');
```

## Layouts

It is common for websites to have a single layout template file with interchanging
content. To render content to be used in a layout, you can pass in an optional
parameter to the `render` method.

```php
Sword::render('header', array('heading' => 'Hello'), 'header_content');
Sword::render('body', array('body' => 'World'), 'body_content');
```

Your view will then have saved variables called `header_content` and `body_content`.
You can then render your layout by doing:

```php
Sword::render('layout', array('title' => 'Home Page'));
```

If the template files looks like this:

`header.php`:

```php
<h1><?php echo $heading; ?></h1>
```

`body.php`:

```php
<div><?php echo $body; ?></div>
```

`layout.php`:

```php
<html>
<head>
<title><?php echo $title; ?></title>
</head>
<body>
<?php echo $header_content; ?>
<?php echo $body_content; ?>
</body>
</html>
```

The output would be:

```html
<html>
  <head>
    <title>Home Page</title>
  </head>
  <body>
    <h1>Hello</h1>
    <div>World</div>
  </body>
</html>
```

## Custom Views

Sword allows you to swap out the default view engine simply by registering your
own view class. Here's how you would use the [Smarty](http://www.smarty.net/)
template engine for your views:

```php
// Load Smarty library
require './Smarty/libs/Smarty.class.php';

// Register Smarty as the view class
// Also pass a callback function to configure Smarty on load
Sword::register('view', 'Smarty', array(), function($smarty){
    $smarty->template_dir = './templates/';
    $smarty->compile_dir = './templates_c/';
    $smarty->config_dir = './config/';
    $smarty->cache_dir = './cache/';
});

// Assign template data
Sword::view()->assign('name', 'Bob');

// Display the template
Sword::view()->display('hello.tpl');
```

For completeness, you should also override Sword's default render method:

```php
Sword::map('render', function($template, $data){
    Sword::view()->assign($data);
    Sword::view()->display($template);
});
```

# Error Handling

## Errors and Exceptions

All errors and exceptions are caught by Sword and passed to the `error` method.
The default behavior is to send a generic `HTTP 500 Internal Server Error`
response with some error information.

You can override this behavior for your own needs:

```php
Sword::map('error', function(Exception $ex){
    // Handle error
    echo $ex->getTraceAsString();
});
```

By default errors are not logged to the web server. You can enable this by
changing the config:

```php
Sword::set('Sword.log_errors', true);
```

## Not Found

When a URL can't be found, Sword calls the `notFound` method. The default
behavior is to send an `HTTP 404 Not Found` response with a simple message.

You can override this behavior for your own needs:

```php
Sword::map('notFound', function(){
    // Handle not found
});
```

# Redirects

You can redirect the current request by using the `redirect` method and passing
in a new URL:

```php
Sword::redirect('/new/location');
```

By default Sword sends a HTTP 303 status code. You can optionally set a
custom code:

```php
Sword::redirect('/new/location', 401);
```

# Requests

Sword encapsulates the HTTP request into a single object, which can be
accessed by doing:

```php
$request = Sword::request();
```

The request object provides the following properties:

```
url - The URL being requested
base - The parent subdirectory of the URL
method - The request method (GET, POST, PUT, DELETE)
referrer - The referrer URL
ip - IP address of the client
ajax - Whether the request is an AJAX request
scheme - The server protocol (http, https)
user_agent - Browser information
type - The content type
length - The content length
query - Query string parameters
data - Post data or JSON data
cookies - Cookie data
files - Uploaded files
secure - Whether the connection is secure
accept - HTTP accept parameters
proxy_ip - Proxy IP address of the client
host - The request host name
```

You can access the `query`, `data`, `cookies`, and `files` properties
as arrays or objects.

So, to get a query string parameter, you can do:

```php
$id = Sword::request()->query['id'];
```

Or you can do:

```php
$id = Sword::request()->query->id;
```

## RAW Request Body

To get the raw HTTP request body, for example when dealing with PUT requests, you can do:

```php
$body = Sword::request()->getBody();
```

## JSON Input

If you send a request with the type `application/json` and the data `{"id": 123}` it will be available
from the `data` property:

```php
$id = Sword::request()->data->id;
```

# HTTP Caching

Sword provides built-in support for HTTP level caching. If the caching condition
is met, Sword will return an HTTP `304 Not Modified` response. The next time the
client requests the same resource, they will be prompted to use their locally
cached version.

## Last-Modified

You can use the `lastModified` method and pass in a UNIX timestamp to set the date
and time a page was last modified. The client will continue to use their cache until
the last modified value is changed.

```php
Sword::route('/news', function(){
    Sword::lastModified(1234567890);
    echo 'This content will be cached.';
});
```

## ETag

`ETag` caching is similar to `Last-Modified`, except you can specify any id you
want for the resource:

```php
Sword::route('/news', function(){
    Sword::etag('my-unique-id');
    echo 'This content will be cached.';
});
```

Keep in mind that calling either `lastModified` or `etag` will both set and check the
cache value. If the cache value is the same between requests, Sword will immediately
send an `HTTP 304` response and stop processing.

# Stopping

You can stop the framework at any point by calling the `halt` method:

```php
Sword::halt();
```

You can also specify an optional `HTTP` status code and message:

```php
Sword::halt(200, 'Be right back...');
```

Calling `halt` will discard any response content up to that point. If you want to stop
the framework and output the current response, use the `stop` method:

```php
Sword::stop();
```

# JSON

Sword provides support for sending JSON and JSONP responses. To send a JSON response you
pass some data to be JSON encoded:

```php
Sword::json(array('id' => 123));
```

For JSONP requests you, can optionally pass in the query parameter name you are
using to define your callback function:

```php
Sword::jsonp(array('id' => 123), 'q');
```

So, when making a GET request using `?q=my_func`, you should receive the output:

```
my_func({"id":123});
```

If you don't pass in a query parameter name it will default to `jsonp`.

# Configuration

You can customize certain behaviors of Sword by setting configuration values
through the `set` method.

```php
Sword::set('Sword.log_errors', true);
```

The following is a list of all the available configuration settings:

    Sword.base_url - Override the base url of the request. (default: null)
    Sword.case_sensitive - Case sensitive matching for URLs. (default: false)
    Sword.handle_errors - Allow Sword to handle all errors internally. (default: true)
    Sword.log_errors - Log errors to the web server's error log file. (default: false)
    Sword.views.path - Directory containing view template files. (default: ./views)
    Sword.views.extension - View template file extension. (default: .php)

# Framework Methods

Sword is designed to be easy to use and understand. The following is the complete set of methods for the framework.
It consists of core methods, which are regular static methods, and extensible methods, which are mapped methods that can be filtered
or overridden.

## Core Methods

```php
Sword::map($name, $callback) // Creates a custom framework method.
Sword::register($name, $class, [$params], [$callback]) // Registers a class to a framework method.
Sword::before($name, $callback) // Adds a filter before a framework method.
Sword::after($name, $callback) // Adds a filter after a framework method.
Sword::path($path) // Adds a path for autoloading classes.
Sword::get($key) // Gets a variable.
Sword::set($key, $value) // Sets a variable.
Sword::has($key) // Checks if a variable is set.
Sword::clear([$key]) // Clears a variable.
Sword::init() // Initializes the framework to its default settings.
Sword::app() // Gets the application object instance
```

## Extensible Methods

```php
Sword::start() // Starts the framework.
Sword::stop() // Stops the framework and sends a response.
Sword::halt([$code], [$message]) // Stop the framework with an optional status code and message.
Sword::route($pattern, $callback) // Maps a URL pattern to a callback.
Sword::redirect($url, [$code]) // Redirects to another URL.
Sword::render($file, [$data], [$key]) // Renders a template file.
Sword::error($exception) // Sends an HTTP 500 response.
Sword::notFound() // Sends an HTTP 404 response.
Sword::etag($id, [$type]) // Performs ETag HTTP caching.
Sword::lastModified($time) // Performs last modified HTTP caching.
Sword::json($data, [$code], [$encode], [$charset], [$option]) // Sends a JSON response.
Sword::jsonp($data, [$param], [$code], [$encode], [$charset], [$option]) // Sends a JSONP response.
```

Any custom methods added with `map` and `register` can also be filtered.

# Framework Instance

Instead of running Sword as a global static class, you can optionally run it
as an object instance.

```php
require './sword/autoload.php';

use sword\Engine;

$app = new Engine();

$app->route('/', function(){
    echo 'hello world!';
});

$app->start();
```

So instead of calling the static method, you would call the instance method with the same name on the Engine object.
