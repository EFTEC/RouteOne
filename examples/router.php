<?php

use eftec\routeone\RouteOne;

include "../vendor/autoload.php";
include "MiController.php";

$route=new RouteOne(".",null,false);

//var_dump($_GET['req']);
//die(1);

$url=$route->getCurrentUrl();

echo "<hr>Current url :".$url."<br>";
echo "<a href='{$url}/Mi'>./Mi</a><br>";
echo "<a href='{$url}/Wrong'>./Wrong (it show throws an error)</a><br>";
echo "<a href='{$url}/Mi/Action2'>./Mi/Action2</a><br>";
echo "<a href='{$url}/Mi/ActionWrong'>./Mi/ActionWrong (it show throws an error)</a><br>";
echo "<a href='{$url}/Mi/Action2/id'>./Mi/Action2/id</a><br>";
echo "<a href='{$url}/Mi/Action2/id/parentid'>./Mi/Action2/id/parentid</a><br>";
echo "<a href='{$url}/Mi/Action2/id/parentid?_event=click'>./Mi/Action2/id/parentid?_event=click</a><br>";
echo "<a href='{$url}/Mi/Action3/id/parentid?_event=click'>./Mi/Action3/id/parentid?_event=click (method with only id)</a><br>";

echo "<hr>";
echo "<b>It could show an error. It is expected (if the path is incorrect of the class/method does not exists)</b><br>";
$route->fetch();
$route->callObject();
echo "<hr>";
echo "<pre>";



var_dump($route);
var_dump($route->getUrl());
var_dump($route->getIdparent());

var_dump("Server:".$route->getCurrentServer());
var_dump("Server:".$route->getCurrentUrl());
echo "</pre>";