<?php

class MiController
{
    public function indexAction($id="",$idparent="",$event="") {
        echo "calling MiController->indexAction( event:[$event] id: [$id] idparent: [$idparent] )\n";
    }
    public function action2Action($id="",$idparent="",$event="") {
        echo "calling MiController->action2Action( event:[$event] id: [$id] idparent: [$idparent] )\n";
    }
    public function action3Action($id="") {
        echo "calling MiController->action3Action( id: [$id])\n";
    }

}