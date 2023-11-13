<?php http_response_code(404); die(1); // It is a template file, not a code to execute directly. This line is used to avoid to execute or read it. ?>
use eftec\routeone\RouteOne;

include __DIR__ . "/vendor/autoload.php";
/**
 * Generate by RouteOne.php
 */
function configureRouteOne():RouteOne
{
    if (gethostname() === '{{dev}}') {
        $baseurl= "{{baseurldev}}"; // dev url
    } else {
        $baseurl="{{baseurlprod}}"; // prod url
    }
    $routeNS={{namespaces}};
    $routePath={{paths}};
    $route = new RouteOne($baseurl);
    foreach ($routePath as $k => $v) {
        $route->addPath($v, $k);
    }
    $route->fetchPath();
    /* todo: we could do some auth work here.
    if($auth===null && $route->controller=="login") {
        $route->redirect("xxxx");
    }
    */
    try {
        // it will be the class somenamespace\ControllerNameController::actionAction
        $found = false;
        foreach ($routeNS as $k => $namespace) {
            if ($route->currentPath === $k) {
                $found = true;
                $route->callObjectEx($namespace . "\{controller}Controller");
            }
        }
        if (!$found) {
            http_response_code(404);
            die(1);
        }
    } catch (Exception $e) {
        echo $e->getMessage();
        http_response_code($e->getCode());
        die(1);
    }
    return $route;
}
configureRouteOne();

