<?php

use eftec\routeone\RouteOne;

include "../vendor/autoload.php";
include "MiController.php";

$route=new RouteOne(".",null,false);

//var_dump($_GET['req']);
//die(1);

$url=$route->getCurrentUrl();

echo "<hr>Current url :".$url."<br><ul>";
echo "<li><a href='{$url}/Mi'>./Mi</a><br></li>";
echo "<li><a href='{$url}/Wrong'>./Wrong (it show throws an error)</a><br></li>";
echo "<li><a href='{$url}/Mi/Action2'>./Mi/Action2</a><br></li>";
echo "<li><a href='{$url}/Mi/ActionWrong'>./Mi/ActionWrong (it show throws an error)</a><br></li>";
echo "<li><a href='{$url}/Mi/Action2/id'>./Mi/Action2/id</a><br></li>";
echo "<li><a href='{$url}/Mi/Action2/id/parentid'>./Mi/Action2/id/parentid</a><br></li>";
echo "<li><a href='{$url}/Mi/Action2/id/parentid?_event=click'>./Mi/Action2/id/parentid?_event=click</a><br></li>";
echo "<li><a href='{$url}/Mi/Action3/id/parentid?_event=click'>./Mi/Action3/id/parentid?_event=click (method with only id)</a><br></li>";
echo "<li><a href='{$url}/Mi/ActionHTTPS/id/parentid?_event=click'>./Mi/ActionHTTPS/id/parentid?_event=click redirect to https</a> (https must be enable in the server)<br></li>";
echo "<li><a href='{$url}/Mi/ActionWWW/id/parentid?_event=click'>./Mi/ActionWWW/id/parentid?_event=click redirect to www.</a> (if www.**domain** is defined) <br></li>";
echo "<li><a href='{$url}/Mi/ActionWWWS/id/parentid?_event=click'>./Mi/ActionWWWS/id/parentid?_event=click redirect to www (https).</a> (if www.**domain** is defined) <br></li>";
echo "<li><a href='{$url}/Mi/ActionNaked/id/parentid?_event=click'>./Mi/ActionNaked/id/parentid?_event=click redirect to naked domain.</a> <br></li>";

echo "</ul><hr>";
echo "<b>It could show an error. It is expected (if the path is incorrect of the class/method does not exists)</b><br></li>";
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