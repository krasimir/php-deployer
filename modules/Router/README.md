# Router

- - -

## Setup and initialization
Copy /htaccess/.htaccess file in your root directory and simply create an instance of Router class.

    $router = new Router();

## Routing

    // register($pattern, $controller, $method = "ALL")
    // $method could be any valid request method or 'ALL'
    $router->register("/users", "ControllerUsers", "GET")->run();

## Routing with parameter

    $router->register("/users/@id", "ControllerUsers")->run();

## Adding more then one controller for a route (middleware architecture)

    $router->register("/users/@id", array("CheckSession", "ControllerUsers"))->run();

## Example
Request:

    http://fabrico.dev/examples/simpleapp/users/20

Route:

    $router->register("/users/@id", "ControllerUsers")->run();

Controller:

    class ControllerUsers {
        public function __construct($params) {
            $id = isset($params["id"]) ? $params["id"] : null;
            $rule = $params["ROUTER_RULE_MATCH"];
            ...
        }   
    }

*$params* contains

  - GET parameters
  - POST parameters
  - variables passed via the url
  - *ROUTER_RULE_MATCH* value, which represents the current matched rule

## Chaining

    $router
    ->register("/users/@id", "ControllerUsers")
    ->register("/users", "ControllerUsers")
    ->register("", "ControllerHome")
    ->run();