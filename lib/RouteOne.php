<?php /** @noinspection PrintfScanfArgumentsInspection */

/** @noinspection ReturnTypeCanBeDeclaredInspection */

namespace eftec\routeone;

use Exception;
use RuntimeException;
use UnexpectedValueException;

/**
 * Class RouteOne
 *
 * @package   RouteOne
 * @copyright 2019 jorge castro castillo
 * @license   lgpl v3
 * @version   1.14.2 2020-06-27
 * @link      https://github.com/EFTEC/RouteOne
 */
class RouteOne
{
    /**
     * @var string It is the base url. RARELY CHANGED<br>
     */
    public $base = '';
    /**
     * @var string=['api','ws','controller','front'][$i] It is the type url.<br>
     * RARELY CHANGED unless it's calls a different behaviour
     */
    public $type = '';
    /**
     * @var string It's the module. RARELY CHANGED unless the application<br>
     * is jumping from one module to another
     */
    public $module = '';
    /**
     * @var string It's the controller. CAN CHANGE with the controller
     */
    public $controller;
    /**
     * @var string It's the action. CAN CHANGE with the module
     */
    public $action;
    /**
     * @var string It's the identifier. CAN CHANGE
     */
    public $id;
    /**
     * @var string. It's the event (such as "click on button).
     * CAN CHANGE with the idparent
     */
    public $event;
    /**
     * @var string. It's the event (such as "click on button). CAN CHANGE with the Id
     */
    public $idparent;
    /**
     * @var string. It's the event (such as "click on button). VARIABLE
     */
    public $extra;
    /** @var string The current category. It is useful for the type 'front' */
    public $category;
    /** @var string The current sub-category. It is useful for the type 'front' */
    public $subcategory;
    /** @var string The current sub-sub-category. It is useful for the type 'front' */
    public $subsubcategory;
    /** @var null|array used to identify the type of route. */
    protected $identify=['api'=>'api','ws'=>'ws','controller'=>''];
    /** @var null|string the current server name. If not set then it is calculated by $_SERVER['SERVER_NAME'] */
    public $serverName;
    /**
     * @var boolean
     */
    public $isPostBack = false;

    /** @var array the queries fetched, excluding "req","_extra" and "_event" */
    public $queries = [];
    /**
     * @var string|null=['api','ws','controller','front'][$i]
     */
    private $forceType;
    private $defController;
    private $defAction;
    private $defCategory;
    private $defSubCategory;
    private $defSubSubCategory;
    private $isModule;

    /**
     * RouteOne constructor.
     *
     * @param string $base       base url with or without trailing slash (it's removed if its set).<br>
     *                           Example: ".","http://domain.dom", "http://domain.dom/subdomain"<br>
     * @param string $forcedType =['api','ws','controller','front'][$i]<br>
     *                           <b>api</b> then it expects a path as api/controller/action/id/idparent<br>
     *                           <b>ws</b> then it expects a path as ws/controller/action/id/idparent<br>
     *                           <b>controller</b> then it expects a path as controller/action/id/idparent<br>
     *                           <b>front</b> then it expects a path as /category/subc/subsubc/id<br>
     * @param bool   $isModule   if true then the route start reading a module name<br>
     *                           <b>false</b> controller/action/id/idparent<br>
     *                           <b>true</b> module/controller/action/id/idparent<br>
     */
    public function __construct($base = '', $forcedType = null, $isModule = false) {
        $this->base = rtrim($base, '/');
        $this->forceType = $forcedType;
        if ($forcedType !== null) {
            $this->type = $forcedType;
        }
        $this->isModule = $isModule;
        if (@$_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->isPostBack = true;
        } else {
            $this->isPostBack = false;
        }
        $this->setDefaultValues();
    }

    /**
     * It sets the default controller and action (if they are not entered in the route)<br>
     * It is uses to set a default route.
     *
     * @param string $defController Default Controller
     * @param string $defAction     Default action
     *
     * @return $this
     */
    public function setDefaultValues($defController = 'Home', $defAction = 'index',$defCategory='Home'
        ,$defSubCategory='',$defSubSubCategory='') {
        $this->defController = $defController;
        $this->defAction = $defAction;
        $this->defCategory=$defCategory;
        $this->defSubCategory=$defSubCategory;
        $this->defSubSubCategory=$defSubSubCategory;
        return $this;
    }



    /**
     * If the subdomain is empty or different to www, then it redirect to www.domain.com.<br>
     * <b>Note: It doesn't work with localhost, domain without TLD (netbios) or ip domains. It is on purpose.</b><br>
     * <b>Note: If this code needs to redirect, then it stops the execution of the code. Usually,
     * it must be called at the top of the code</b>
     *
     * @param bool $https If true the it also redirect to https
     */
    public function alwaysWWW($https = false) {
        if (strpos(@$_SERVER['HTTP_HOST'], '.') === false || ip2long(@$_SERVER['HTTP_HOST'])) {
            if ($https) {
                $this->alwaysHTTPS();
            }
            return;
        }
        if (strpos(@$_SERVER['HTTP_HOST'], 'www.') === false) {
            $location=$this->getLocation($https);
            $location .= '//www.' . @$_SERVER['HTTP_HOST'] . @$_SERVER['REQUEST_URI'];
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $location);
            die(1);
        }

        if ($https) {
            $this->alwaysHTTPS();
        }
    }

    /**
     * If the subdomain is www (example www.domain.dom) then it redirect to a naked domain domain.dom<br>
     * <b>Note: It doesn't work with localhost, domain without TLD (netbios) or ip domains. It is on purpose.</b><br>
     * <b>Note: If this code needs to redirect, then it stops the execution of the code. Usually,
     * it must be called at the top of the code</b>
     *
     * @param bool $https If true the it also redirect to https
     */
    public function alwaysNakedDomain($https = false) {
        if (strpos(@$_SERVER['HTTP_HOST'], '.') === false || ip2long(@$_SERVER['HTTP_HOST'])) {
            if ($https) {
                $this->alwaysHTTPS();
            }
            return;
        }
        if (strpos(@$_SERVER['HTTP_HOST'], 'www.') === 0) {
            $host=substr(@$_SERVER['HTTP_HOST'],4); // we remove the www. at first
            $location=$this->getLocation($https);
            $location .= '//' . $host . @$_SERVER['REQUEST_URI'];
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $location);
            die(1);
        }

        if ($https) {
            $this->alwaysHTTPS();
        }
    }
    private function getLocation($https) {
        if ($https) {
            $port = $_SERVER['HTTP_PORT'] ?? '443';
            $location = 'https:';
            if ($port !== '443' && $port !== '80') {
                $location .= $port;
            }
        } else {
            $port = $_SERVER['HTTP_PORT'] ?? '80';
            $location = 'http:';
            if ($port !== '80') {
                $location .= $port;
            }
        }
        return $location;
    }
    /**
     * If the page is loaded as http, then it redirects to https<br>
     * <b>Note: It doesn't work with localhost, domain without TLD (netbios) or ip domains. It is on purpose.</b><br>
     * <b>Note: If this code needs to redirect, then it stops the execution of the code. Usually,
     * it must be called at the top of the code</b>
     */
    public function alwaysHTTPS() {
        if (strpos(@$_SERVER['HTTP_HOST'], '.') === false || ip2long(@$_SERVER['HTTP_HOST'])) {
            return;
        }

        if (empty(@$_SERVER['HTTPS']) || @$_SERVER['HTTPS'] === 'off') {
            $port = $_SERVER['HTTP_PORT'] ?? '443';

            $port = ($port === '443' || $port === '80') ? '' : $port;
            $location = 'https:' . $port . '//' . @$_SERVER['HTTP_HOST'] . @$_SERVER['REQUEST_URI'];
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $location);
            die(1);
        }
    }

    /**
     * It creates and object (for example, a Controller object) and calls the method.<br>
     * <b>Example:</b> (type controller,api,ws)
     * <pre>
     * $this->callObject('cocacola\controller\%sController'); // %s is replaced by the name of the current controller
     * $this->callObject('namespace/%2s/%1sClass'); // it calls namespace/Module/ExampleClass (only if module is able)
     * $this->callObject('namespace/%2s/%3s%/%1sClass'); // %3s is for the type of path
     * </pre>
     * <b>Note:</b> The method called should be written as (static or not)<br>
     * <pre>
     * public function *nameaction*Action($id="",$idparent="",$event="") { }
     * </pre>
     *
     * @param string $classStructure structure of the class.<br>
     *                               <b>Type=controller,api,ws</b><br>
     *                               The <b>first %s</b> (or %1s) is the name of the controller.<br>
     *                               The <b>second %s</b> (or %2s) is the name of the module (if any and if ->isModule=true)<br>
     *                               The <b>third %s</b> (or %3s) is the type of the path (i.e. controller,api,ws,front)<br>
     *                               <b>Type=front</b><br>
     *                               The <b>first %s</b> (or %1s) is the name of the category.<br>
     *                               The <b>second %s</b> (or %2s) is the name of the subcategory<br>
     *                               The <b>third %s</b> (or %3s) is the type of the subsubcategory<br>
     * @param bool   $throwOnError   [optional] Default:true,  if true then it throws an exception. If false then it returns the error (if any)
     * @param string $method         [optional] Default value='%sAction'. The name of the method to call (get/post).
     *                               If method does not exists then it will use $methodGet or $methodPost
     * @param string $methodGet      [optional] Default value='%sAction'. The name of the method to call (get) but only
     *                               if the method defined by $method is not defined.
     * @param string $methodPost     [optional] Default value='%sAction'. The name of the method to call (post) but only
     *                               if the method defined by $method is not defined.
     * @param array  $arguments      [optional] Default value=['id','idparent','event'] the arguments to pass to the function
     *
     * @return string|null null if the operation was correct, or the message of error if it failed.
     * @throws Exception
     */
    public function callObject(
        $classStructure = '%sController', $throwOnError = true
        , $method = '%sAction', $methodGet = '%sActionGet', $methodPost = '%sActionPost'
        , $arguments = ['id', 'idparent', 'event']
    ) {
        if ($this->type !== 'front') {
            $op = sprintf($classStructure, $this->controller, $this->module, $this->type);
        } else {
            $op = sprintf($classStructure, $this->category, $this->subcategory, $this->subsubcategory);
        }
        if (!class_exists($op, true)) {
            if ($throwOnError) {
                throw new RuntimeException("Class $op doesn't exist");
            }
            return "Class $op doesn't exist";
        }
        try {
            $controller = new $op();
            if ($this->type !== 'front') {
                $actionRequest = sprintf($method, $this->action);
            } else {
                $actionRequest = sprintf($method, $this->subcategory, $this->subsubcategory);
            }
            $actionGetPost = (!$this->isPostBack) ? sprintf($methodGet, $this->action)
                : sprintf($methodPost, $this->action);
        } catch (Exception $ex) {
            if ($throwOnError) {
                throw $ex;
            }
            return $ex->getMessage();
        }

        $args = [];
        foreach ($arguments as $a) {
            $args[] = $this->{$a};
        }
        if (method_exists($controller, $actionRequest)) {
            try {
                $controller->{$actionRequest}(...$args);
            } catch (Exception $ex) {
                if ($throwOnError) {
                    throw $ex;
                }
                return $ex->getMessage();
            }
        } elseif (method_exists($controller, $actionGetPost)) {
            try {
                $controller->{$actionGetPost}(...$args);
            } catch (Exception $ex) {
                if ($throwOnError) {
                    throw $ex;
                }
                return $ex->getMessage();
            }
        } else {
            $pb = $this->isPostBack ? '(POST)' : '(GET)';
            $msgError = "Action [{$actionRequest} or {$actionGetPost}] $pb not found for class [{$op}]";
            $msgError = strip_tags($msgError);
            if ($throwOnError) {
                throw new UnexpectedValueException($msgError);
            }

            return $msgError;
        }
        return null;
    }

    /**
     * It creates and object (for example, a Controller object) and calls the method.<br>
     * Note: It is an advanced version of this::callObject()<br>
     * This method uses {} to replace values.<br>
     * <ul>
     * <li><b>{controller}</b> The name of the controller</li>
     * <li><b>{action}</b> The current action</li>
     * <li><b>{event}</b> The current event</li>
     * <li><b>{type}</b> The current type of path (ws,controller,front,api)</li>
     * <li><b>{module}</b> The current module (if module is active)</li>
     * <li><b>{id}</b> The current id</li>
     * <li><b>{idparent}</b> The current idparent</li>
     * <li><b>{category}</b> The current category</li>
     * <li><b>{subcategory}</b> The current subcategory</li>
     * <li><b>{subsubcategory}</b> The current subsubcategory</li>
     * </ul>
     * <b>Example:</b>
     * <pre>
     * // controller example http://somedomain/Customer/Insert/23
     * $this->callObjectEx('cocacola\controller\{controller}Controller');
     * // it calls the method cocacola\controller\Customer::InsertAction(23,'','');
     *
     * // front example: http://somedomain/product/coffee/nescafe/1
     * $this->callObjectEx('cocacola\controller\{category}Controller',false,'{subcategory}',null,null,['subsubcategory','id']);
     * // it calls the method cocacola\controller\product::coffee('nescafe','1');
     * </pre>
     *
     * @param string $classStructure [optional] Default value='{controller}Controller'
     * @param bool   $throwOnError   [optional] Default:true,  if true then it throws an exception. If false then it returns the error (if any)
     * @param string $method         [optional] Default value='{action}Action'. The name of the method to call (get/post).
     *                               If method does not exists then it will use $methodGet or $methodPost
     * @param string $methodGet      [optional] Default value='{action}Action'. The name of the method to call (get) but only
     *                               if the method defined by $method is not defined.
     * @param string $methodPost     [optional] Default value='{action}Action'. The name of the method to call (post) but only
     *                               if the method defined by $method is not defined.
     * @param array  $arguments      [optional] Default value=['id','idparent','event'] the arguments to pass to the function
     *
     * @return string|null
     * @throws Exception
     */
    public function callObjectEx(
        $classStructure = '{controller}Controller', $throwOnError = true
        , $method = '{action}Action', $methodGet = '{action}ActionGet'
        , $methodPost = '{action}ActionPost', $arguments = ['id', 'idparent', 'event']
    ) {
        $op = $this->replaceNamed($classStructure);
        if (!class_exists($op, true)) {
            if ($throwOnError) {
                throw new RuntimeException("Class $op doesn't exist");
            }
            return "Class $op doesn't exist";
        }
        try {
            $controller = new $op();
            $actionRequest = $this->replaceNamed($method);
            $actionGetPost = (!$this->isPostBack) ? $this->replaceNamed($methodGet)
                : $this->replaceNamed($methodPost);
        } catch (Exception $ex) {
            if ($throwOnError) {
                throw $ex;
            }
            return $ex->getMessage();
        }
        $args = [];
        foreach ($arguments as $a) {
            $args[] = $this->{$a};
        }
        if (method_exists($controller, $actionRequest)) {
            try {
                $controller->{$actionRequest}(...$args);
            } catch (Exception $ex) {
                if ($throwOnError) {
                    throw $ex;
                }
                return $ex->getMessage();
            }
        } elseif (method_exists($controller, $actionGetPost)) {
            try {
                $controller->{$actionGetPost}(...$args);
            } catch (Exception $ex) {
                if ($throwOnError) {
                    throw $ex;
                }
                return $ex->getMessage();
            }
        } else {
            $pb = $this->isPostBack ? '(POST)' : '(GET)';
            $msgError = "Action ex [{$actionRequest} or {$actionGetPost}] $pb not found for class [{$op}]";
            $msgError = strip_tags($msgError);
            if ($throwOnError) {
                throw new UnexpectedValueException($msgError);
            }

            return $msgError;
        }
        return null;
    }

    /**
     * Return a formatted string like vsprintf() with named placeholders.
     *
     * When a placeholder doesn't have a matching key in `$args`,
     *   the placeholder is returned as is to see missing args.
     *
     * @param string $format
     * @param string $pattern
     *
     * @return string
     */
    private function replaceNamed($format, $pattern = "/\{(\w+)\}/") {
        return preg_replace_callback($pattern, function ($matches) {
            $nameField = $matches[1];
            return @$this->{$nameField} ?: $matches[0];
        }, $format);
    }

    /**
     * It calls (include) a file using the current controller.
     *
     * @param string $fileStructure  It uses sprintf<br>
     *                               The first %s (or %1s) is the name of the controller.<br>
     *                               The second %s (or %2s) is the name of the module (if any and if ->isModule=true)<br>
     *                               The third %s (or %3s) is the type of the path (i.e. controller,api,ws,front)<br>
     *                               Example %s.php => controllername.php<br>
     *                               Example %s3s%/%1s.php => controller/controllername.php
     * @param bool   $throwOnError
     *
     * @return string|null
     * @throws Exception
     */
    public function callFile($fileStructure = '%s.php', $throwOnError = true) {
        $op = sprintf($fileStructure, $this->controller, $this->module, $this->type);
        try {
            /**
             * @noinspection PhpIncludeInspection
             */
            include $op;
        } catch (Exception $ex) {
            if ($throwOnError) {
                throw $ex;
            }
            return $ex->getMessage();
        }
        return null;
    }

    /**
     * Returns the current base url without traling space, paremters or queries/b<br>
     * <b>Note:</b> If $this->setCurrentServer() is not set, then it uses $_SERVER['SERVER_NAME'] and
     * it could be modified by the user.
     *
     * @param bool $withoutFilename if true then it doesn't include the filename
     *
     * @return string
     */
    public function getCurrentUrl($withoutFilename = true) {
        if ($withoutFilename) {
            return dirname($this->getCurrentServer() . @$_SERVER['SCRIPT_NAME']);
        }
        return $this->getCurrentServer() . @$_SERVER['SCRIPT_NAME'];
    }

    /**
     * It returns the current server without trailing slash.<br>
     * <b>Note:</b> If $this->setCurrentServer() is not set, then it uses $_SERVER['SERVER_NAME'] and
     * it could be modified by the user.
     *
     * @return string
     */
    public function getCurrentServer() {
        $server_name = $this->serverName ?? @$_SERVER['SERVER_NAME'];
        $port = !in_array(@$_SERVER['SERVER_PORT'], ['80', '443'], true)
            ? ':' . @$_SERVER['SERVER_PORT'] . ''
            : '';
        if (!empty(@$_SERVER['HTTPS']) && (strtolower(@$_SERVER['HTTPS']) === 'on'
                || @$_SERVER['HTTPS'] === '1')) {
            $scheme = 'https';
        } else {
            $scheme = 'http';
        }
        return $scheme . '://' . $server_name . $port;
    }

    /**
     * It sets the current server name.  It is used by getCurrentUrl() and getCurrentServer()
     *
     * @param string $serverName Example: "localhost", "127.0.0.1", "www.site.com", etc.
     *
     * @see \eftec\routeone\RouteOne::getCurrentUrl
     * @see \eftec\routeone\RouteOne::getCurrentServer()
     */
    public function setCurrentServer($serverName) {
        $this->serverName=$serverName;
    }

    /**
     * It builds an url using custom values.
     *
     * @param null $module     Name of the module
     * @param null $controller Name of the controller.
     * @param null $action     Name of the action
     * @param null $id         Name of the id
     * @param null $idparent   Name of the idparent
     *
     * @return $this
     */
    public function url(
        $module = null,
        $controller = null,
        $action = null,
        $id = null,
        $idparent = null
    ) {
        if ($module) {
            $this->module = $module;
        }
        if ($controller) {
            $this->controller = $controller;
        }
        if ($action) {
            $this->action = $action;
        }
        if ($id) {
            $this->id = $id;
        }
        if ($idparent) {
            $this->id = $idparent;
        }
        $this->extra = null;
        $this->event = null;
        return $this;
    }

    public function urlFront(
        $module = null,
        $category = null,
        $subcategory = null,
        $subsubcategory = null,
        $id = null
    ) {
        if ($module) {
            $this->module = $module;
        }
        if ($category) {
            $this->category = $category;
        }
        if ($subcategory) {
            $this->subcategory = $subcategory;
        }
        if ($subsubcategory) {
            $this->subsubcategory = $subsubcategory;
        }
        if ($id) {
            $this->id = $id;
        }
        $this->extra = null;
        $this->event = null;
        return $this;
    }

    public function reset() {
        // $this->base=''; base is always keep
        $this->defController = '';
        $this->defCategory='';
        $this->defSubCategory='';
        $this->defSubSubCategory='';
        $this->forceType = null;
        $this->defAction = '';
        $this->isModule = '';
        $this->id = null;
        $this->event = null;
        $this->idparent = null;
        $this->extra = null;
        return $this;
    }


    /**
     * This function its used to identify the type automatically. If the url is empty then it is marked as default<br>
     * It returns the first one that matches.
     * <b>Example:</b><br>
     * <pre>
     * $this->setIdentifyType([
     *      'controller' =>'backend', // domain.dom/backend/controller/action => controller type
     *      'api'=>'api',             // domain.dom/api/controller => api type
     *      'ws'=>'api/ws'            // domain.dom/api/ws/controller => ws type
     *      'front'=>''               // domain.dom/* =>front (any other that does not match)
     * ]);
     * </pre>
     *
     * @param $array
     */
    public function setIdentifyType($array) {
        $this->identify=$array;
    }

    protected function str_replace_ex($search, $replace, $subject,$limit=99999) {
        return implode($replace, explode($search, $subject, $limit+1));
    }

    // .htaccess:
    // RewriteRule ^(.*)$ index.php?req=$1 [L,QSA]
    /**
     *
     *
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
        $urlFetched = @$_GET['req']; // controller/action/id/..
        // nginx returns a path as /aaa/bbb apache aaa/bbb
        if($urlFetched !== '') {
            $urlFetched =ltrim($urlFetched,'/');
        }
        $this->queries = $_GET;
        unset($this->queries['req'], $this->queries['_event'], $this->queries['_extra']);
        $urlFetched=filter_var($urlFetched, FILTER_SANITIZE_URL);
        if(is_array($this->identify)) {
            foreach($this->identify as $ty=>$path) {
                if ($path==='') {
                    $this->type=$ty;
                    break;
                }
                if(strpos($urlFetched,$path)===0) {
                    $urlFetched=ltrim($this->str_replace_ex($path,'',$urlFetched,1),'/');
                    $this->type=$ty;
                    break;
                }
            }
        }
        $path = explode('/', $urlFetched);
        $first = $path[0] ?? $this->defController;
        $id = 0;
        if ($this->isModule) {
            $this->module = @$path[$id++];
        } else {
            $this->module = null;
        }
        if ($this->forceType!==null) {
            $this->type=$this->forceType;
        }
        if (!$this->type) {
            $this->type = 'controller';
            $this->controller = @(!$path[$id]) ? $this->defController : $path[$id];
            $id++;
        }          
        switch ($this->type) {
            case 'ws':
            case 'api':
                $id++;
                $this->controller = @$path[$id++] ?? $this->defController;
                break;
            case 'controller':
                $this->controller = @$path[$id++] ?? $this->defController;
                break;
            case 'front':
                // it is processed differently.
                $this->category = @$path[$id] ? $path[$id] : $this->defCategory;
                $id++;
                $this->subcategory = @$path[$id] ? $path[$id] : $this->defSubCategory;
                $id++;
                /** @noinspection PhpUnusedLocalVariableInspection */
                $this->subsubcategory = @$path[$id++] ?? $this->defSubSubCategory;
                $this->id = end($path); // id is the last element of the path
                $this->event = $this->request('_event');
                $this->extra = $this->request('_extra');
                return;
        }
  
        $this->action = @$path[$id++];
        $this->action = ($this->action) ?: $this->defAction;
        $this->id = @$path[$id++];
        /** @noinspection PhpUnusedLocalVariableInspection */
        $this->idparent = @$path[$id++];
        $this->event = $this->request('_event');
        $this->extra = $this->request('_extra');
    }

    private function request($id, $numeric = false, $default = null) {
        $v = $_POST[$id] ?? $_GET[$id] ?? $default;
        if ($numeric && is_numeric($v)) {
            return $v;
        }
        if (!$numeric && ctype_alnum($v)) {
            return $v;
        }
        return $default;
    }

    /**
     * It returns a non route url based in the base url.<br>
     * <b>Example:</b><br>
     * $this->getNonRouteUrl('login.php'); // http://baseurl.com/login.php
     *
     * @param string $urlPart
     *
     * @return string
     * @see \eftec\routeone\RouteOne::$base
     */
    public function getNonRouteUrl($urlPart) {
        return $this->base . '/' . $urlPart;
    }

    /**
     * It reconstruct an url using the current information.<br>
     * <b>Note:<b>. It discards any information outside of the type
     * (/controller/action/id/idparent/<cutcontent>?arg=1&arg=2)
     *
     * @param string $extraQuery   If we want to add extra queries
     * @param bool   $includeQuery If true then it includes the queries in $this->queries
     *
     * @return string
     */
    public function getUrl($extraQuery = '', $includeQuery = false) {
        $url = $this->base . '/';
        if ($this->isModule) {
            $url .= $this->module . '/';
        }
        switch ($this->type) {
            case 'api':
                $url .= '';
                break;
            case 'ws':
                $url .= '';
                break;
            case 'controller':
                $url .= '';
                break;
            case 'front':
                $url .= "{$this->category}/{$this->subcategory}/{$this->subsubcategory}/";
                if ($this->id) {
                    $url .= $this->id . '/';
                }
                if ($this->idparent) {
                    $url .= $this->idparent . '/';
                }
                return $url;
                break;
            default:
                trigger_error('type [' . $this->type . '] not defined');
                break;
        }
        $url .= $this->controller . '/'; // Controller is always visible, even if it is empty
        $url .= $this->action . '/'; // action is always visible, even if it is empty
        $sepQuery = '?';
        if (($this->id !== null && $this->id !== '') || $this->idparent !== null) {
            $url .= $this->id . '/'; // id is visible if id is not empty or if idparent is not empty.
        }
        if ($this->idparent !== null && $this->idparent !== '') {
            $url .= $this->idparent . '/'; // idparent is only visible if it is not empty (zero is not empty)
        }
        if ($this->event !== null && $this->event !== '') {
            $url .= '?_event=' . $this->event;
            $sepQuery = '&';
        }
        if ($this->extra !== null && $this->extra !== '') {
            $url .= $sepQuery . '_extra=' . $this->extra;
            $sepQuery = '&';
        }
        if ($extraQuery !== null && $extraQuery !== '') {
            $url .= $sepQuery . $extraQuery;
            $sepQuery = '&';
        }
        if ($includeQuery && count($this->queries)) {
            $url .= $sepQuery . http_build_query($this->queries);
        }
        return $url;
    }

    /**
     * It returns the current type.
     *
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * It returns the current name of the module
     *
     * @return string
     */
    public function getModule() {
        return $this->module;
    }

    /**
     * @param string     $key
     * @param null|mixed $valueIfNotFound
     *
     * @return mixed
     */
    public function getQuery($key, $valueIfNotFound = null) {
        return $this->queries[$key] ?? $valueIfNotFound;
    }

    /**
     * It sets a query value
     *
     * @param string     $key
     * @param null|mixed $value
     */
    public function setQuery($key, $value) {
        $this->queries[$key] = $value;
    }

    /**
     * It returns the current name of the controller.
     *
     * @return string
     */
    public function getController() {
        return $this->controller;
    }

    /**
     *
     *
     * @param  $controller
     *
     * @return RouteOne
     */
    public function setController($controller) {
        $this->controller = $controller;
        return $this;
    }

    /**
     *
     *
     * @return string
     */
    public function getAction() {
        return $this->action;
    }

    /**
     *
     *
     * @param  $action
     *
     * @return RouteOne
     */
    public function setAction($action) {
        $this->action = $action;
        return $this;
    }

    /**
     *
     *
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    /**
     *
     *
     * @param  $id
     *
     * @return RouteOne
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     *
     *
     * @return string
     */
    public function getEvent() {
        return $this->event;
    }

    /**
     *
     *
     * @param  $event
     *
     * @return RouteOne
     */
    public function setEvent($event) {
        $this->event = $event;
        return $this;
    }

    /**
     *
     *
     * @return string
     */
    public function getIdparent() {
        return $this->idparent;
    }

    /**
     * @param  $idParent
     *
     * @return RouteOne
     */
    public function setIdParent($idParent) {
        $this->idparent = $idParent;
        return $this;
    }

    /**
     *
     *
     * @return string
     */
    public function getExtra() {
        return $this->extra;
    }

    /**
     * @param string $extra
     *
     * @return RouteOne
     */
    public function setExtra($extra) {
        $this->extra = $extra;
        return $this;
    }

    /**
     *
     *
     * @return mixed
     */
    public function getCategory() {
        return $this->category;
    }

    /**
     *
     *
     * @return mixed
     */
    public function getSubcategory() {
        return $this->subcategory;
    }

    /**
     *
     *
     * @return mixed
     */
    public function getSubsubcategory() {
        return $this->subsubcategory;
    }

    /**
     * @return bool
     */
    public function isPostBack(): bool {
        return $this->isPostBack;
    }

    /**
     * @param bool $isPostBackg
     *
     * @return RouteOne
     */
    public function setIsPostBack(bool $isPostBack): RouteOne {
        $this->isPostBack = $isPostBack;
        return $this;
    }
}
