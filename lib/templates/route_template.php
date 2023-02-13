<?php http_response_code(404); die(1); // It is a template file, not a code to execute directly. This line is used to avoid to execute or read it. ?>
<?php

use eftec\routeone\RouteOne;
include __DIR__."/vendor/autoload.php";
/**
 * Generate by RouteOne.php
 */
const BASEURL="http://localhost"; // Base url edit this value.
const BASEWEBNS="eftec\\controller"; // Base namespace (web) edit this value
const BASEAPINS="eftec\\api"; // Base namespace (api) edit this value


$route=new RouteOne(BASEURL);
$route->addPath("api/{controller:HomeApi}/{action:list}/{id:0}","apipath");
$route->addPath("{controller:Home}/{action:list}/{id:0}/{idparent}","webpath");


$route->fetchPath();

/* todo: we could do some auth work here.
if($auth===null && $route->controller=="login") {
    $route->redirect("xxxx");
}
*/
try {
    // it will be the class somenamespace\ControllerNameController::actionAction
    switch ($route->currentPath) {
        case 'webpath':
            $route->callObjectEx(BASEWEBNS . "\{controller}Controller");
            break;
        case 'apipath':
            $route->callObjectEx(BASEAPINS . "\{controller}Controller");
            break;
        default:
            http_response_code(404);
    }

} catch (Exception $e) {
    echo $e->getMessage();
    http_response_code($e->getCode());
    die(1);
}

