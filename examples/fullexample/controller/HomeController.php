<?php

namespace cocacola\controller;

class HomeController
{
    public function indexAction($id="",$idparent="",$event="") {
        global $route;
        echo \BootstrapUtil::contentWeb(\BootstrapUtil::navigation($route->getCurrentUrl(),$route->controller." ".$route->action." ".$id));
    }
}