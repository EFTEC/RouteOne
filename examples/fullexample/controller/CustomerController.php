<?php

namespace cocacola\controller;

class CustomerController
{
    public function indexAction($id="",$idparent="",$event="") {
        global $route;
        echo \BootstrapUtil::contentWeb(\BootstrapUtil::navigation($route->getCurrentUrl(),$route->controller." ".$route->action." ".$id));
    }
    public function updateAction($id="",$idparent="",$event="") {
        global $route;
        echo \BootstrapUtil::contentWeb(\BootstrapUtil::navigation($route->getCurrentUrl(),$route->controller." ".$route->action." ".$id));
    }
    public function getAction($id="",$idparent="",$event="") {
        global $route;
        echo \BootstrapUtil::contentWeb(\BootstrapUtil::navigation($route->getCurrentUrl(),$route->controller." ".$route->action." ".$id));
    }
    public function newAction($id="",$idparent="",$event="") {
        global $route;
        echo \BootstrapUtil::contentWeb(\BootstrapUtil::navigation($route->getCurrentUrl(),$route->controller." ".$route->action." ".$id));
    }
}