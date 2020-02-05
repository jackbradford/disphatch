# Disphatch
A server-side PHP router for simple web applications. Features user authentication/authorization via integration with Cartalyst's Sentinel.


## Installation
Install the package via Composer:
`composer require jackbradford/disphatch`

### Configure Database
The package lists cartalyst/sentinel as a dependency. In my experience, Sentinel will not automatically configure your database with the tables it needs. I've included a script to take care of it: `setup/sentinel.sh`


## Usage
After installation, include the autoload file:
`include_once 'vendor/autoload.php';`

Initialize an instance of the router:
`$router = JackBradford\Disphatch\Router\Router::init('path/to/disphatch.conf.json');`

Route and execute requests:
`$router->routeAndExecuteRequest();`


## Configuration
---
The router can be configured via the `disphatch.conf.json` file.



