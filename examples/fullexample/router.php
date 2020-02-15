<?php

use eftec\routeone\RouteOne;

include "../../vendor/autoload.php";
include "BootstrapUtil.php";
include "./controller/CustomerController.php";
include "./controller/HomeController.php";
$route=new RouteOne(".",null,false);
$route->setDefaultValues('Home','index');   // if controller or action is empty then it will use the default values
$route->fetch();




//$route->callObject('cocacola\controller\%sController');
$route->callObjectEx('cocacola\controller\{controller}Controller');

