<?php /** @noinspection PhpUnusedLocalVariableInspection */

namespace eftec\routeone;


use Exception;
use UnexpectedValueException;

/**
 * Class RouteOne
 *
 * @package eftec\RouteOne
 * @version 1.1 20190620
 * @copyright jorge castro castillo
 * @link     https://github.com/EFTEC/RouteOne
 * @license  lgpl v3
 */
class RouteOne {
    /** @var string It is the base url. RARELY CHANGED */
    public $base='';
    /** @var string=['api','ws','controller','front'][$i] It is the type url. RARELY CHANGED unless it's calls a different behaviour */
    public $type='';
    /** @var string It's the module. RARELY CHANGED unless the application is jumping from one module to another */
    public $module='';

    /** @var string It's the controller. CAN CHANGE with the controller */
    public $controller;
    /** @var string It's the action. CAN CHANGE with the module */
    public $action;
    /** @var string It's the identifier. CAN CHANGE */
    public $id;
    /** @var string. It's the event (such as "click on button). CAN CHANGE with the idparent  */
    public $event;
    /** @var string. It's the event (such as "click on button). CAN CHANGE with the Id  */
    public $idparent;
    /** @var string. It's the event (such as "click on button). VARIABLE  */
    public $extra;
    /** @var string Default api initial Path */
    private $apiPath;
    /** @var string default web service initial Path */
    private $wsPath;

    public $category;
    public $subcategory;
    public $subsubcategory;
    /** @var string|null=['api','ws','controller','front'][$i]  */
    private $forceType=null;

    /** @var boolean  */
    public $isPostBack=false;
    private $defController;
    private $defAction;
    private $isModule;

    /**
     * RouteOne constructor.
     *
     * @param string $base base url
     * @param string $forcedType=['api','ws','controller','front'][$i]<br>
     *                          <b>api</b> then it expects a path as api/controller/action/id/idparent<br>
     *                          <b>ws</b> then it expects a path as ws/controller/action/id/idparent<br>
     *                          <b>controller</b> then it expects a path as controller/action/id/idparent<br>
     *                          <b>front</b> then it expects a path as /category/subcategory/subsubcategory/id<br>
     *                          aaa<br>
     * @param bool   $isModule if true then the route start reading a module name<br>
     *                         <b>false</b> controller/action/id/idparent<br>
     *                         <b>true</b> module/controller/action/id/idparent<br>
     */
    public function __construct($base='', $forcedType=null, $isModule=false)
    {
        $this->base=$base;

        $this->forceType = $forcedType;

        $this->isModule=$isModule;

        if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'POST') {
            $this->isPostBack=true;
        } else {
            $this->isPostBack=false;
        }
        $this->setDefaultValues();
        $this->setPath();
    }

    /**
     * It creates and object and calls the method.
     * @param string $classStructure structure of the class. %s is the name of the controller.<br>
     *                               Example: namespace/%sClass if the controller=Example then it calls namespace/ExampleClass
     * @param bool   $throwOnError if true then it throws an exception. If false then it returns the error (if any)
     *
     * @return string|null null if the operation was correct, or the message of error if it failed.
     * @throws Exception
     */
    public function callObject($classStructure='%sController',$throwOnError=true) {
        global $controller;
        $op=sprintf($classStructure,$this->controller);
        if (!class_exists($op,true)) {
            if($throwOnError) throw new Exception("Class $op doesn't exist");
            return "Class $op doesn't exist";
        }
        try {
            $controller = new $op();
            $action2 = $this->action . "Action";
        } catch (Exception $ex) {
            if($throwOnError) throw $ex;
            return $ex->getMessage();
        }

        if (method_exists($controller,$action2)) {
            $controller->{$action2}($this->id, $this->idparent,$this->event);
        } else {
            if($throwOnError) throw new UnexpectedValueException("incorrect action [{$this->action}] for [{$this->controller}]");
            else return "Incorrect action [{$this->action}] for [{$this->controller}]";
        }
        return null;
    }

    /**
     * It calls (include) a file using the current controller.
     * @param string $fileStructure
     * @param bool   $throwOnError
     *
     * @return string|null
     * @throws Exception
     */
    public function callFile($fileStructure='%s.php',$throwOnError=true) {
        global $controller;
        $op=sprintf($fileStructure,$this->controller);
        try {
            /** @noinspection PhpIncludeInspection */
            include $op;
        } catch (Exception $ex) {
            if($throwOnError) throw $ex;
            return $ex->getMessage();
        }
        return null;
    }

    /**
     * It sets the default root path for api and ws
     * @param string $apiPath
     * @param string $wsPath
     *
     * @return $this
     */
    public function setPath($apiPath="api",$wsPath="ws") {
        $this->apiPath=$apiPath;
        $this->wsPath=$wsPath;
        return $this;
    }
    public function getCurrentServer()
    {
        $server_name = $_SERVER['SERVER_NAME'];
        $port = !in_array($_SERVER['SERVER_PORT'], [80, 443]) ? ":$_SERVER[SERVER_PORT]" : '';
        if (!empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == '1')) {
            $scheme = 'https';
        } else {
            $scheme = 'http';
        }
        return $scheme.'://'.$server_name.$port;
    }

    /**
     * Returns the current and real url without traling space<br>
     * Note: this function relies on $_SERVER['SERVER_NAME'] and it could be modified by the end-user
     * @param bool $withoutFilename if true then it doesn't include the filename
     *
     * @return string
     */
    public function getCurrentUrl($withoutFilename=true) {
        if($withoutFilename) {
            return dirname($this->getCurrentServer().$_SERVER['SCRIPT_NAME']);
        }
        return $this->getCurrentServer().$_SERVER['SCRIPT_NAME'];
    }

    /**
     * It sets the default controller and action (if they are not entered in the route)<br>
     * It is uses to set a default route.
     * @param string $defController
     * @param string $defAction
     *
     * @return $this
     */
    public function setDefaultValues($defController="Home",$defAction="index") {
        $this->defController = $defController;
        $this->defAction = $defAction;
        return $this;
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
    public function fetch() {
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
                case $this->apiPath: // [module]/api/controller/action
                    $id++; // ignores the first one cause it's "api"
                    $this->type = 'api';
                    $this->controller = @(!$path[$id]) ? $this->defController:$path[$id];
                    $id++;
                    break;
                case $this->wsPath: // [module]/ws/controller/action
                    $id++; // ignores the first one cause it's "ws"
                    $this->type = 'ws';
                    $this->controller = @(!$path[$id]) ? $this->defController:$path[$id];
                    $id++;
                    break;
                default: // [module]/controller/action
                    $this->type = 'controller';
                    $this->controller = @(!$path[$id]) ? $this->defController:$path[$id];
                    $id++;
                    break;
            }
        } else {
            $this->type=$this->forceType;
            switch ($this->forceType) {
                case 'api':
                    $id++;
                    $this->controller = @$path[$id++] ?? $this->defController;
                    break;
                case 'ws':
                    $id++;
                    $this->controller = @$path[$id++] ?? $this->defController;
                    break;
                case 'controller':
                    $this->controller = @$path[$id++] ?? $this->defController;
                    break;
                case 'front':
                    // it is processed differently.
                    $this->category = @$path[$id++] ?? '';
                    $this->subcategory = @$path[$id++] ?? '';
                    $this->subsubcategory = @$path[$id++] ?? '';
                    /** @noinspection PhpUnusedLocalVariableInspection */
                    $this->id=end($path); // id is the last element of the path
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
    /**
     * @param $idParent
     * @return RouteOne
     */
    public function setIdParent($idParent) {
        $this->idparent=$idParent;
        return $this;
    }
    public function getUrl($extraParam='') {

        $url=$this->base.'/';
        if ($this->isModule) {
            $url.=$this->module.'/';
        }
        switch ($this->type) {
            case 'api':
                $url.=$this->apiPath.'/';
                $url.='';
                break;
            case 'ws':
                $url.=$this->wsPath.'/';
                break;
            case 'controller':
                $url.='';
                break;
            case 'front':
                $url.="{$this->category}/{$this->subcategory}/{$this->subsubcategory}/";
                if ($this->id) $url.=$this->id.'/';
                if ($this->idparent) $url.=$this->idparent.'/';
                return $url;
                break;
            default:
                trigger_error('type not defined');
                break;
        }
        $url.=$this->controller.'/';
        $url.=$this->action.'/';
        if ($this->id!==null && $this->idparent!==null) $url.=$this->id.'/';
        if ($this->idparent!==null) $url.=$this->idparent.'/';
        if ($this->event) $url.='?_event='.$this->event;
        if ($this->extra) $url.='&_extra='.$this->extra;
        if ($extraParam) $url.='&'.$extraParam;
        return $url;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return string
     */
    public function getIdparent()
    {
        return $this->idparent;
    }

    /**
     * @return string
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return mixed
     */
    public function getSubcategory()
    {
        return $this->subcategory;
    }

    /**
     * @return mixed
     */
    public function getSubsubcategory()
    {
        return $this->subsubcategory;
    }

}