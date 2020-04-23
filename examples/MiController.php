<?php

class MiController
{
    public function indexAction($id= '',$idparent= '',$event= '') {
        echo "calling MiController->indexAction( event:[$event] id: [$id] idparent: [$idparent] )\n";
    }
    public function action2Action($id= '',$idparent= '',$event= '') {
        echo "calling MiController->action2Action( event:[$event] id: [$id] idparent: [$idparent] )\n";
    }
    public function action3Action($id= '') {
        echo "calling MiController->action3Action( id: [$id])\n";
    }
    public function actionHTTPSAction($id= '') {
        echo "calling MiController->actionHTTPSAction( id: [$id])\n";
        global $route;
        $route->alwaysHTTPS();
    }
    public function actionWWWAction($id= '') {
        echo "calling MiController->actionWWWAction( id: [$id])\n";
        global $route;
        $route->alwaysWWW();
    }
    public function actionWWWSAction($id= '') {
        echo "calling MiController->actionWWWSAction( id: [$id])\n";
        global $route;
        $route->alwaysWWW(true);
    }
    public function actionNakedAction($id= '') {
        echo "calling MiController->actionNakedAction( id: [$id])\n";
        global $route;
        $route->alwaysNakedDomain();
    }
}