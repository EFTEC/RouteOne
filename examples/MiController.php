<?php

class MiController
{
    public function indexAction($event="",$id="",$idparent="") {
        echo "calling MiController->indexAction( event:[$event] id: [$id] idparent: [$idparent] )\n";
    }
    public function action2Action($event="",$id="",$idparent="") {
        echo "calling MiController->action2Action( event:[$event] id: [$id] idparent: [$idparent] )\n";
    }
}