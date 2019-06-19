<?php /** @noinspection PhpUnusedLocalVariableInspection */

namespace eftec\routeone;


/**
 * Class RouteOne
 * @package eftec\RouteOne
 * @version 0.10 20181028
 * @copyright jorge castro castillo
 * @license  lgpl v3
 */
class RouteOne {
    /** @var string It is the base url. RARELY CHANGED */
    var $base='';
    /** @var int It is the type url. RARELY CHANGED unless it's calls a different behaviour */
    var $type=0;
    /** @var string It's the module. RARELY CHANGED unless the application is jumping from one module to another */
    var $module="";
    /** @var string It's the controller. CAN CHANGE with the controller */
    var $controller;
    /** @var string It's the action. CAN CHANGE with the module */
    var $action;
    /** @var string It's the identifier. CAN CHANGE */
    var $id;
    /** @var string. It's the event (such as "click on button). CAN CHANGE with the idparent  */
    var $event;
    /** @var string. It's the event (such as "click on button). CAN CHANGE with the Id  */
    var $idparent;
    /** @var string. It's the event (such as "click on button). VARIABLE  */
    var $extra;


    var $category;
    var $subcategory;
    var $subsubcategory;

    var $forceType=null;

    /** @var boolean  */
    private $isPostBack=false;
    private $defController;
    private $defAction;
    private $isModule;

    /**
     * RouteOne constructor.
     *
     * @param string $base
     * @param string $defController
     * @param int    $forcedType=['api','ws','controller','front'][$i]
     * @param string $defAction
     * @param bool   $isModule
     */
    public function __construct($base='', $defController="Home", $forcedType=null, $defAction="index", $isModule=false)
    {
        $this->base=$base;
        $this->defController = $defController;
        $this->forceType = $forcedType;
        $this->defAction = $defAction;
        $this->isModule=$isModule;

        if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'POST') {
            $this->isPostBack=true;
        } else {
            $this->isPostBack=false;
        }
    }

    public function url($module=null,$controller=null,$action=null,$id=null,$idparent=null) {
        if ($module) $this->module=$module;
        if ($controller) $this->controller=$controller;
        if ($action) $this->action=$action;
        if ($id) $this->id=$id;
        if ($idparent) $this->id=$idparent;

        $this->extra=null;
        $this->event=null;
        return $this;

    }
    public function reset() {
        // $this->base=''; base is always keep
        $this->defController = '';
        // $this->type = 0;
        $this->forceType=null;
        $this->defAction = '';
        $this->isModule='';
        $this->id=null;
        $this->event=null;
        $this->idparent=null;
        $this->extra=null;
        return $this;
    }



    // .htaccess:
    // RewriteRule ^(.*)$ index.php?req=$1 [L,QSA]
    /**
     * It uses the first strategy to obtain the parameters<br>
     *  api/Controller/action/id/idparent?_event=xx&extra=xxx
     *  ws/Controller/action/id/idparent?_event=xx&extra=xxx
     *  Controller/action/id/idparent?_event=xx&extra=xxx
     *  Module/Controller/action/id/idparent?_event=xx&extra=xxx
     *  Module/api/ControllerApi/action/id/idparent/?_event=xx&extra=xxx
     *  Module/ws/ControllerWS/action/id/idparent/?_event=xx&extra=xxx
     * .htaccess = RewriteRule ^(.*)$ index.php?req=$1 [L,QSA]<br>
     */
    public function getStrategy1() {
        $path=explode("/",filter_var(@$_GET['req'],FILTER_SANITIZE_URL));
        $first=$path[0]??$this->defController;
        $id=0;
        if ($this->isModule) {
            $this->module = @$path[$id++];
        } else {
            $this->module = null;
        }

        if ($this->forceType===null) {
        	
            switch ($first) {
                case "api": // [module]/api/controller/action
                    $id++;
                    $this->type = 1;
                    $this->controller = @$path[$id++] ?? $this->defController;
                    break;
                case "webport": // [module]/ws/controller/action
                    $id++;
                    $this->type = 2;
                    $this->controller = @$path[$id++] ?? $this->defController;
                    break;

                default: // [module]/controller/action
                    $this->type = 0;
                    $this->controller = @$path[$id++] ?? $this->defController;
            }
        } else {
            switch ($this->forceType) {
                case 'api':
                    $id++;
                    $this->type = 1;
                    $this->controller = @$path[$id++] ?? $this->defController;
                    break;
                case 'webport':
                    $id++;
                    $this->type = 2;
                    $this->controller = @$path[$id++] ?? $this->defController;
                    break;
                case 'controller':
                    $this->type = 0;
                    $this->controller = @$path[$id++] ?? $this->defController;
                    break;
                case 'front':
                    $this->type = 3;
                    $this->category = @$path[$id++] ?? '';
                    $this->subcategory = @$path[$id++] ?? '';
                    $this->subsubcategory = @$path[$id++] ?? '';
                    /** @noinspection PhpUnusedLocalVariableInspection */
                    $this->id=end($path[$id++]); // id is the last element of the path
                    $this->event= $this->request('_event');
                    $this->extra=$this->request('_extra');
                    return;
            }
        }



        $this->action=@$path[$id++];
        $this->action=($this->action)?$this->action:$this->defAction;
        $this->id=@$path[$id++];
        $this->idparent=@$path[$id++];
        $this->event= $this->request('_event');
        $this->extra=$this->request('_extra');
    }

    private function request($id,$numeric=false,$default=null) {
        $v=isset($_POST[$id])?$_POST[$id]:(isset($_GET[$id])?$_GET[$id]:$default);
        if ($numeric && is_numeric($v)) return $v;
        if (!$numeric && ctype_alnum($v)) return $v;
        return $default;
    }

    /**
     * @param $event
     * @return RouteOne
     */
    public function setEvent($event) {
        $this->event=$event;
        return $this;
    }
    /**
     * @param $controller
     * @return RouteOne
     */
    public function setController($controller) {
        $this->controller=$controller;
        return $this;
    }
    /**
     * @param $action
     * @return RouteOne
     */
    public function setAction($action) {
        $this->action=$action;
        return $this;
    }
    /**
     * @param $id
     * @return RouteOne
     */
    public function setId($id) {
        $this->id=$id;
        return $this;
    }

    public function getUrl($extraParam='') {

        $url=$this->base.'/';
        if ($this->isModule) {
            $url.=$this->module.'/';
        }
        switch ($this->type) {
            case 0:
                $url.='';
                break;
            case 1:
                $url.='api/';
                break;
            case 2:
                $url.='webport/';
                break;
        }
        $url.=$this->controller.'/';
        $url.=$this->action.'/';
        if ($this->id) $url.=$this->id.'/';
        if ($this->idparent) $url.=$this->idparent.'/';
        if ($this->event) $url.='?_event='.$this->event;
        if ($this->extra) $url.='&_extra='.$this->extra;
        if ($extraParam) $url.='&'.$extraParam;
        return $url;
    }

    
}