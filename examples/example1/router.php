<?php

use eftec\routeone\RouteOne;

include '../../vendor/autoload.php';
include 'SomeClassController.php';

$route=new RouteOne('.', null, false);

//var_dump($_GET['req']);
//die(1);

$url=$route->getCurrentUrl();

echo '<hr>Current url :' .$url. '<br><ul>';

echo "<li><a href='{$url}/BaseUrl'>./BaseUrl</a><br></li>";
echo "<li><a href='{$url}/Wrong'>./Wrong (it shows throws an error)</a><br></li>";
echo "<li><a href='{$url}/BaseUrl/SomeClass/Action2'>./BaseUrl/SomeClass/Action2</a><br></li>";
echo "<li><a href='{$url}/BaseUrl/SomeClass/ActionWrong'>./BaseUrl/SomeClass/ActionWrong (it shows throws an error)</a><br></li>";
echo "<li><a href='{$url}/BaseUrl/SomeClass/Action2/id'>./BaseUrl/SomeClass/Action2/id</a><br></li>";
echo "<li><a href='{$url}/BaseUrl/SomeClass/Action2/id/parentid'>./BaseUrl/SomeClass/Action2/id/parentid</a><br></li>";
echo "<li><a href='{$url}/BaseUrl/SomeClass/Action2/id/parentid?_event=click'>./BaseUrl/SomeClass/Action2/id/parentid?_event=click</a><br></li>";
echo "<li><a href='{$url}/BaseUrl/SomeClass/Action3/id/parentid?_event=click'>./BaseUrl/SomeClass/Action3/id/parentid?_event=click (method with only id)</a><br></li>";
echo "<li><a href='{$url}/BaseUrl/SomeClass/ActionHTTPS/id/parentid?_event=click'>./BaseUrl/SomeClass/ActionHTTPS/id/parentid?_event=click redirect to https</a> (https must be enable in the server)<br></li>";
echo "<li><a href='{$url}/BaseUrl/SomeClass/ActionWWW/id/parentid?_event=click'>./BaseUrl/SomeClass/ActionWWW/id/parentid?_event=click redirect to www.</a> (if www.**domain** is defined) <br></li>";
echo "<li><a href='{$url}/BaseUrl/SomeClass/ActionWWWS/id/parentid?_event=click'>./BaseUrl/SomeClass/ActionWWWS/id/parentid?_event=click redirect to www (https).</a> (if www.**domain** is defined) <br></li>";
echo "<li><a href='{$url}/BaseUrl/SomeClass/ActionNaked/id/parentid?_event=click'>./BaseUrl/SomeClass/ActionNaked/id/parentid?_event=click redirect to naked domain.</a> <br></li>";
var_dump($_REQUEST);
echo '</ul><hr>';
echo '<b>It could show an error. It is expected (if the path is incorrect of the class/method does not exists)</b><br></li>';
//$route->addPath('BaseUrl/{controller}/{action:index}');
//$route->addPath('BaseUrl/{controller}/{action:index}/{id:123}');
$route->addPath('BaseUrl/{controller}/{action:index}/{id:123}/{idparent}');
$found=$route->fetchPath();
if(!$found) {
    die("Current url does not matches any path ".json_encode($route->lastError));
}

$route->callObject();
echo '<hr>';

echo "<img src='{$route->getCurrentUrl()}/img/indianhead.jpg' width='128' height='100'/>";
echo "<img src='{$route->getCurrentUrl()}/noimg/indianhead.jpg' width='128' height='100'/>";
echo '<hr>';
echo '<pre>';
var_dump($_REQUEST);


var_dump($route);
var_dump('getUrl:' .$route->getUrl());
var_dump('getIdparent:' .$route->getIdparent());

var_dump('getCurrentServer:' .$route->getCurrentServer());
var_dump('getCurrentUrl:' .$route->getCurrentUrl());
echo '</pre>';
