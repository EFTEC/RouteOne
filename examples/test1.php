<?php

use eftec\routeone\RouteOne;

include "../vendor/autoload.php";

$route=new RouteOne(".");
$route->getStrategy1();

var_dump($route);