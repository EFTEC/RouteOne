<?php

use eftec\routeone\RouteOne;

include "../../vendor/autoload.php";
include "BootstrapUtil.php";
include "./controller/CustomerController.php";
include "./controller/HomeController.php";
$route=new RouteOne(".",null,false);
$route->fetch();


$route->callObject('cocacola\controller\%sController');

