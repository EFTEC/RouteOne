<?php /** @noinspection PhpMissingParamTypeInspection */
/** @noinspection PhpMissingReturnTypeInspection */
/** @noinspection PrintfScanfArgumentsInspection */

/** @noinspection ReturnTypeCanBeDeclaredInspection */

namespace eftec\routeone;

use Exception;
use RuntimeException;
use UnexpectedValueException;

/**
 * Class RouteOne
 *
 * @package   RouteOne
 * @copyright 2019-2021 Jorge Castro Castillo
 * @license   (dual licence lgpl v3 and commercial)
 * @version   1.19 2021-02-26
 * @link      https://github.com/EFTEC/RouteOne
 */
class RouteOne
{
    /** @var string It is the base url.<br> */
    public $base = '';
    /** @var string=['api','ws','controller','front'][$i] It is the type url. */
    public $type = '';
    /** @var string It's the current module (if we are using a module). */
    public $module = '';
    /** @var string It's the controller. */
    public $controller;
    /** @var string It's the current action. */
    public $action;
    /** @var string It's the identifier. */
    public $id;
    /** @var string. It's the event (such as "click" on a button). */
    public $event;
    /** @var string. It is the current parent id (if any) */
    public $idparent;
    /** @var string. It's the event (such as "click on button). */
    public $extra;
    /** @var string The current category. It is useful for the type 'front' */
    public $category;
    /** @var string The current sub-category. It is useful for the type 'front' */
    public $subcategory;
    /** @var string The current sub-sub-category. It is useful for the type 'front' */
    public $subsubcategory;
    /** @var null|array It is an associative array that helps to identify the api and ws route. */
    protected $identify = ['api' => 'api', 'ws' => 'ws', 'controller' => ''];
    /** @var null|string the current server name. If not set then it is calculated by $_SERVER['SERVER_NAME'] */
    public $serverName;
    /** @var boolean its true if the page is POST, otherwise (GET,DELETE or PUT) it is false. */
    public $isPostBack = false;
    /** @var string The current HTML METHOD. It is always uppercase and only inside of the array $allowedVerbs */
    public $verb = 'GET';
    /** @var string[] The list of allowed $verb. In case of error, the $verb is equals to GET */
    public $allowedVerbs = ['GET', 'POST', 'PUT', 'DELETE'];
    /** @var string[] Allowed fields to be read and parsed by callObjectEx() */
    public $allowedFields = ['controller', 'action', 'verb', 'event', 'type', 'module', 'id', 'idparent', 'category'
        , 'subcategory', 'subsubcategory'];
    /** @var array[] it holds the whitelist. Ex: ['controller'=>['a1','a2','a3']]  */
    protected $whitelist=[
        'controller'=>null,
        'category'=>null,
        'action'=>null,
        'subcategory'=>null,
        'subsubcategory'=>null,
        'module'=>null
    ];
    protected $whitelistLower=[
        'controller'=>null,
        'category'=>null,
        'action'=>null,
        'subcategory'=>null,
        'subsubcategory'=>null,
        'module'=>null
    ];

    /** @var bool if true then the whitelist validation failed and the value is not allowed  */
    public $notAllowed=false;

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
    private $isFetched = false;

    public $httpHost = '';
    public $requestUri = '';

    /**
     * RouteOne constructor.
     *
     * @param string $base        base url with or without trailing slash (it's removed if its set).<br>
     *                            Example: ".","http://domain.dom", "http://domain.dom/subdomain"<br>
     * @param string $forcedType  =['api','ws','controller','front'][$i]<br>
     *                            <b>api</b> then it expects a path as api/controller/action/id/idparent<br>
     *                            <b>ws</b> then it expects a path as ws/controller/action/id/idparent<br>
     *                            <b>controller</b> then it expects a path as controller/action/id/idparent<br>
     *                            <b>front</b> then it expects a path as /category/subc/subsubc/id<br>
     * @param bool   $isModule    if true then the route start reading a module name<br>
     *                            <b>false</b> controller/action/id/idparent<br>
     *                            <b>true</b> module/controller/action/id/idparent<br>
     * @param bool   $fetchValues (default false), if true then it also calls the method fetch()
     */
    public function __construct($base = '', $forcedType = null, $isModule = false, $fetchValues = false)
    {
        $this->base = rtrim($base, '/');
        $this->forceType = $forcedType;
        if ($forcedType !== null) {
            $this->type = $forcedType;
        }
        $this->isModule = $isModule;
        $this->isPostBack = false;

        if (isset($_SERVER['REQUEST_METHOD']) && in_array($_SERVER['REQUEST_METHOD'],$this->allowedVerbs, true)) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->isPostBack = true;
            }
            $this->verb = $_SERVER['REQUEST_METHOD'];
        } else {
            $this->verb = 'GET';
        }
        $this->setDefaultValues();
        if ($fetchValues) {
            $this->fetch();
        }
    }

    /**
     * It sets the default controller and action (if they are not entered in the route)<br>
     * It is uses to set a default route if the value is empty or its missing.<br>
     * <b>Note:It must be set before fetch().</b>
     *
     * @param string $defController Default Controller
     * @param string $defAction     Default action
     * @param string $defCategory
     * @param string $defSubCategory
     * @param string $defSubSubCategory
     *
     * @return $this
     */
    public function setDefaultValues($defController = '', $defAction = '', $defCategory = ''
        , $defSubCategory = '', $defSubSubCategory = '')
    {
        if ($this->isFetched) {
            throw new RuntimeException("RouteOne: you can't call setDefaultValues() after fetch()");
        }
        $this->defController = $defController;
        $this->defAction = $defAction;
        $this->defCategory = $defCategory;
        $this->defSubCategory = $defSubCategory;
        $this->defSubSubCategory = $defSubSubCategory;
        return $this;
    }


    /**
     * It is an associative array with name of controllers allowed or null to allows any controller.<br>
     * <b>Example:</b>
     * <pre>
     * $this->setWhiteList('controller',['Purchase','Invoice','Customer']);
     * </pre>
     * <b>Note:</b> this must be executed before fetch()
     * @param string $type=['controller','category','action','subcategory','subsubcategory','module'][$i]
     * @param array|null $array if null (default value) then we don't validate the information.
     */
    public function setWhiteList($type,$array) {
        if ($this->isFetched && $array!==null) {
            throw new RuntimeException("RouteOne: you can't call setWhiteList() after fetch()");
        }
        $type=strtolower($type);
        $this->whitelist[$type]=$array;
        $this->whitelistLower[$type]=is_array($array) ? array_map('strtolower', $array) : null;
    }


    /**
     * If the subdomain is empty or different to www, then it redirect to www.domain.com.<br>
     * <b>Note: It doesn't work with localhost, domain without TLD (netbios) or ip domains. It is on purpose.</b><br>
     * <b>Note: If this code needs to redirect, then it stops the execution of the code. Usually,
     * it must be called at the top of the code</b>
     *
     * @param bool $https    If true the it also redirect to https
     * @param bool $redirect if true (default) then it redirect the header. If false, then it returns the new full url
     * @return string|null   It returns null if the operation failed (no correct url or no need to redirect)<br>
     *                       Otherwise, if $redirect=false, it returns the full url to redirect.
     */
    public function alwaysWWW($https = false, $redirect = true)
    {
        $url = $this->httpHost;
        //if (strpos($url, '.') === false || ip2long($url)) {
        //}
        if (strpos($url, 'www.') === false) {
            $location = $this->getLocation($https);
            $location .= '//www.' . $url . $this->requestUri;
            if ($redirect) {
                header('HTTP/1.1 301 Moved Permanently');
                header('Location: ' . $location);
                die(1);
            }
            return $location;
        }
        if ($https) {
            return $this->alwaysHTTPS(false);
        }
        return null;
    }

    /**
     * If the subdomain is www (example www.domain.dom) then it redirect to a naked domain domain.dom<br>
     * <b>Note: It doesn't work with localhost, domain without TLD (netbios) or ip domains. It is on purpose.</b><br>
     * <b>Note: If this code needs to redirect, then we should stop stops the execution of any other code. Usually,
     * it must be called at the top of the code</b>
     *
     * @param bool $https    If true the it also redirect to https
     * @param bool $redirect if true (default) then it redirect the header. If false, then it returns the new url
     * @return string|null   It returns null if the operation failed (no correct url or no need to redirect)<br>
     *                       Otherwise, if $redirect=false, it returns the full url to redirect.
     */
    public function alwaysNakedDomain($https = false, $redirect = true)
    {
        $url = $this->httpHost;
        if (strpos($url, 'www.') === 0) {
            $host = substr($url, 4); // we remove the www. at first
            $location = $this->getLocation($https);
            $location .= '//' . $host . $this->requestUri;
            if ($redirect) {
                header('HTTP/1.1 301 Moved Permanently');
                header('Location: ' . $location);
                die(1);
            }
            return $location;
        }
        if ($https) {
            return $this->alwaysHTTPS(false);
        }
        return null;
    }

    private function getLocation($https)
    {
        if ($https) {
            $port = isset($_SERVER['HTTP_PORT']) ? $_SERVER['HTTP_PORT'] : '443';
            $location = 'https:';
            if ($port !== '443' && $port !== '80') {
                $location .= $port;
            }
        } else {
            $port = isset($_SERVER['HTTP_PORT']) ? $_SERVER['HTTP_PORT'] : '80';
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
     * @param bool $redirect if true (default) then it redirect the header. If false, then it returns the new url
     * @return string|null It returns null if the operation failed (no correct url or no need to redirect)<br>
     *                       Otherwise, if $redirect=false, it returns the url to redirect.
     */
    public function alwaysHTTPS($redirect = true)
    {
        if (strpos($this->httpHost, '.') === false || ip2long($this->httpHost)) {
            return null;
        }
        $https = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : '';
        if (empty($https) || $https === 'off') {
            $port = isset($_SERVER['HTTP_PORT']) ? $_SERVER['HTTP_PORT'] : '443';
            $port = ($port === '443' || $port === '80') ? '' : $port;
            $location = 'https:' . $port . '//' . $this->httpHost . $this->requestUri;
            if ($redirect) {
                header('HTTP/1.1 301 Moved Permanently');
                header('Location: ' . $location);
                die(1);
            }
            return $location;
        }
        return null;
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
     *                               The <b>second %s</b> (or %2s) is the name of the module (if any and if
     *                               ->isModule=true)<br> The <b>third %s</b> (or %3s) is the type of the path (i.e.
     *                               controller,api,ws,front)<br>
     *                               <b>Type=front</b><br>
     *                               The <b>first %s</b> (or %1s) is the name of the category.<br>
     *                               The <b>second %s</b> (or %2s) is the name of the subcategory<br>
     *                               The <b>third %s</b> (or %3s) is the type of the subsubcategory<br>
     * @param bool   $throwOnError   [optional] Default:true,  if true then it throws an exception. If false then it
     *                               returns the error (if any)
     * @param string $method         [optional] Default value='%sAction'. The name of the method to call (get/post).
     *                               If method does not exists then it will use $methodGet or $methodPost
     * @param string $methodGet      [optional] Default value='%sAction'. The name of the method to call (get) but only
     *                               if the method defined by $method is not defined.
     * @param string $methodPost     [optional] Default value='%sAction'. The name of the method to call (post) but
     *                               only
     *                               if the method defined by $method is not defined.
     * @param array  $arguments      [optional] Default value=['id','idparent','event'] the arguments to pass to the
     *                               function
     *
     * @return string|null null if the operation was correct, or the message of error if it failed.
     * @throws Exception
     */
    public function callObject(
        $classStructure = '%sController', $throwOnError = true
        , $method = '%sAction', $methodGet = '%sActionGet', $methodPost = '%sActionPost'
        , $arguments = ['id', 'idparent', 'event']
    )
    {
        if($this->notAllowed===true) {
            throw new UnexpectedValueException('Input is not allowed');
        }
        if ($this->type !== 'front') {
            if($this->controller===null) {
                throw new UnexpectedValueException('Controller is not set or it is not allowed');
            }
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
     * <li><b>{controller}</b> The name of the controller. Example:/web/<b>controller</b>/action </li>
     * <li><b>{action}</b> The current action. Example:/web/controller/<b>action</b> </li>
     * <li><b>{verb}</b> The current verb (GET, POST,PUT or DELETE).  Example:<b>[GET]</b> /web/controller/action<br>
     *                   The verb is ucfirst (Get instead of GET)</li>
     * <li><b>{event}</b> The current event. Example:/web/controller/action?_event=<b>click</b></li>
     * <li><b>{type}</b> The current type of path (ws,controller,front,api)</li>
     * <li><b>{module}</b> The current module (if module is active). Example:/web/<b>module1</b>/controller/action
     * </li>
     * <li><b>{id}</b> The current id. Example:/web/controller/action/<b>20</b></li>
     * <li><b>{idparent}</b> The current idparent. Example:/web/controller/action/10/<b>20</b></li>
     * <li><b>{category}</b> The current category (type of path is front). Example: /web/<b>food</b>/fruit/season</li>
     * <li><b>{subcategory}</b> The current subcategory (type of path is front). Example:
     * /web/food/<b>fruit</b>/season</li>
     * <li><b>{subsubcategory}</b> The current subsubcategory (type of path is front). Example:
     * /web/food/fruit/<b>season</b></li>
     * </ul>
     * <b>Note:</b> You can also convert the case
     * <ul>
     * <li><b>{uc_*tag*}</b> uppercase first</li>
     * <li><b>{lc_*tag*}</b> lowercase first</li>
     * <li><b>{u_*tag*}</b> uppercase</li>
     * <li><b>{l_*tag*}</b> lowercase</li>
     * </ul>
     * <b>Example:</b>
     * <pre>
     * // controller example http://somedomain/Customer/Insert/23
     * $this->callObjectEx('cocacola\controller\{controller}Controller');
     * // it calls the method cocacola\controller\Customer::InsertAction(23,'','');
     *
     * $this->callObjectEx('cocacola\controller\{controller}Controller','{action}Action{verb}');
     * // it calls the method cocacola\controller\Customer::InsertActionGet(23,'',''); or InsertActionPost, etc.
     *
     * // front example: http://somedomain/product/coffee/nescafe/1
     * $this->callObjectEx('cocacola\controller\{category}Controller',false,'{subcategory}',null,null,['subsubcategory','id']);
     * // it calls the method cocacola\controller\product::coffee('nescafe','1');
     * </pre>
     *
     * @param string $classStructure [optional] Default value='{controller}Controller'
     * @param bool   $throwOnError   [optional] Default:true,  if true then it throws an exception. If false then it
     *                               returns the error (if any)
     * @param string $method         [optional] Default value='{action}Action'. The name of the method to call
     *                               (get/post). If method does not exists then it will use $methodGet or $methodPost
     * @param string $methodGet      [optional] Default value='{action}Action{verb}'. The name of the method to call
     *                               (get) but only if the method defined by $method is not defined.
     * @param string $methodPost     [optional] Default value='{action}Action{verb}'. The name of the method to call
     *                               (post) but only if the method defined by $method is not defined.
     * @param array  $arguments      [optional] Default value=['id','idparent','event'] the arguments to pass to the
     *                               function
     *
     * @return string|null
     * @throws Exception
     */
    public function callObjectEx(
        $classStructure = '{controller}Controller', $throwOnError = true
        , $method = '{action}Action', $methodGet = '{action}Action{verb}'
        , $methodPost = '{action}Action{verb}', $arguments = ['id', 'idparent', 'event']
    )
    {
        if($this->notAllowed===true) {
            throw new UnexpectedValueException('Input is not allowed');
        }

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
     * Return a formatted string like vsprintf() with named placeholders.<br>
     * When a placeholder doesn't have a matching key (it's not in the whitelist <b>$allowedFields</b>), then the value
     * is not modified and it is returned as is.<br>
     * If the name starts with uc_,lc_,u_,l_ then it is converted into ucfirst,lcfirst,uppercase or lowercase.
     *
     * @param string $format
     * @param string $pattern
     *
     * @return string
     */
    private function replaceNamed($format, $pattern = "/\{(\w+)\}/")
    {
        return preg_replace_callback($pattern, function ($matches) {
            $nameField = $matches[1];
            if (in_array($nameField, $this->allowedFields, true) === false) {
                return '{' . $nameField . '}';
            }
            $result=$this->{$nameField};
            if(strpos($result,'_')>0) {
                $x=explode('_',$result)[0];
                switch ($x) {
                    case 'uc':
                        $result=ucfirst(strtolower($result));
                        break;
                    case 'lc':
                        $result=lcfirst(strtoupper($result));
                        break;
                    case 'u':
                        $result=strtoupper($result);
                        break;
                    case 'l':
                        $result=strtolower($result);
                        break;
                }
            }

            return $result;

        }, $format);
    }

    /**
     * It calls (include) a file using the current controller.
     *
     * @param string $fileStructure  It uses sprintf<br>
     *                               The first %s (or %1s) is the name of the controller.<br>
     *                               The second %s (or %2s) is the name of the module (if any and if
     *                               ->isModule=true)<br> The third %s (or %3s) is the type of the path (i.e.
     *                               controller,api,ws,front)<br> Example %s.php => controllername.php<br> Example
     *                               %s3s%/%1s.php => controller/controllername.php
     * @param bool   $throwOnError
     *
     * @return string|null
     * @throws Exception
     */
    public function callFile($fileStructure = '%s.php', $throwOnError = true)
    {
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
    public function getCurrentUrl($withoutFilename = true)
    {
        $sn = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
        if ($withoutFilename) {
            return dirname($this->getCurrentServer() . $sn);
        }
        return $this->getCurrentServer() . $sn;
    }

    /**
     * It returns the current server without trailing slash.<br>
     * <b>Note:</b> If $this->setCurrentServer() is not set, then it uses $_SERVER['SERVER_NAME'] and
     * it could be modified by the user.
     *
     * @return string
     */
    public function getCurrentServer()
    {
        if (isset($this->serverName)) {
            $server_name = $this->serverName;
        } else {
            $server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : null;
        }
        $sp = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '';
        $port = !in_array($sp, ['80', '443'], true) ? ':' . $sp . '' : '';
        $https = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : '';
        $scheme = !empty($https) && (strtolower($https) === 'on' || $https === '1') ? 'https' : 'http';
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
    public function setCurrentServer($serverName)
    {
        $this->serverName = $serverName;
    }

    /**
     * It builds an url using custom values.<br>
     * If the values are null, then it keeps the current values.
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
    )
    {
        if ($module !== null) {
            $this->module = $module;
        }
        if ($controller !== null) {
            $this->setController($controller);
        }
        if ($action !== null) {
            $this->action = $action;
        }
        if ($id !== null) {
            $this->id = $id;
        }
        if ($idparent !== null) {
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
    )
    {
        if ($module) {
            $this->module = $module;
        }
        if ($category) {
            $this->setCategory($category);
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

    public function reset()
    {
        // $this->base=''; base is always keep
        $this->isFetched = false;
        $this->defController = '';
        $this->defCategory = '';
        $this->defSubCategory = '';
        $this->defSubSubCategory = '';
        $this->forceType = null;
        $this->defAction = '';
        $this->isModule = '';
        $this->id = null;
        $this->event = null;
        $this->idparent = null;
        $this->extra = null;
        $this->verb = 'GET';
        $this->notAllowed=false;
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
    public function setIdentifyType($array)
    {
        $this->identify = $array;
    }

    protected function str_replace_ex($search, $replace, $subject, $limit = 99999)
    {
        return implode($replace, explode($search, $subject, $limit + 1));
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
    public function fetch()
    {
        $this->notAllowed=false; // reset
        $this->isFetched = true;
        $urlFetched = isset($_GET['req']) ? $_GET['req'] : null; // controller/action/id/..
        unset($_GET['req']);
        /** @noinspection HostnameSubstitutionInspection */
        $this->httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $this->requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        // nginx returns a path as /aaa/bbb apache aaa/bbb
        if ($urlFetched !== '') {
            $urlFetched = ltrim($urlFetched, '/');
        }
        $this->queries = $_GET;
        unset($this->queries['req'], $this->queries['_event'], $this->queries['_extra']);
        $urlFetched = filter_var($urlFetched, FILTER_SANITIZE_URL);
        if (is_array($this->identify) && $this->type==='') {
            foreach ($this->identify as $ty => $path) {
                if ($path === '') {
                    $this->type = $ty;
                    break;
                }
                if (strpos($urlFetched, $path) === 0) {
                    $urlFetched = ltrim($this->str_replace_ex($path, '', $urlFetched, 1), '/');
                    $this->type = $ty;
                    break;
                }
            }
        }
        $path = explode('/', $urlFetched);
        //$first = $path[0] ?? $this->defController;
        $id = 0;
        if ($this->isModule) {
            $this->module = isset($path[$id]) ? $path[$id] : null;
            $id++;
        } else {
            $this->module = null;
        }
        if ($this->forceType !== null) {
            $this->type = $this->forceType;
        }
        if (!$this->type) {
            $this->type = 'controller';
            $this->setController( (!$path[$id]) ? $this->defController : $path[$id]);
            $id++;
        }
        switch ($this->type) {
            case 'ws':
            case 'api':
                //$id++; [fixed]
                $this->setController(isset($path[$id]) && $path[$id] ? $path[$id] : $this->defController);
                $id++;
                break;
            case 'controller':
                $this->setController(isset($path[$id]) && $path[$id] ? $path[$id] : $this->defController);
                $id++;
                break;
            case 'front':
                // it is processed differently.
                $this->setCategory(isset($path[$id]) && $path[$id] ? $path[$id] : $this->defCategory);
                $id++;
                $this->subcategory = isset($path[$id]) && $path[$id] ? $path[$id] : $this->defSubCategory;
                $id++;
                $this->subsubcategory = isset($path[$id]) && $path[$id] ? $path[$id] : $this->defSubSubCategory;
                /** @noinspection PhpUnusedLocalVariableInspection */
                $id++;
                $this->id = end($path); // id is the last element of the path
                $this->event = $this->request('_event');
                $this->extra = $this->request('_extra');
                return;
        }

        $this->action = isset($path[$id]) ? $path[$id] : null;
        $id++;
        $this->action = $this->action ?: $this->defAction; // $this->action is never undefined, so we don't need isset
        $this->id = isset($path[$id]) ? $path[$id] : null;
        $id++;
        $this->idparent = isset($path[$id]) ? $path[$id] : null;
        /** @noinspection PhpUnusedLocalVariableInspection */
        $id++;
        $this->event = $this->request('_event');
        $this->extra = $this->request('_extra');
    }

    private function request($id, $numeric = false, $default = null)
    {
        if (isset($_POST[$id])) {
            $v = $_POST[$id];
        } else {
            $v = isset($_GET[$id]) ? $_GET[$id] : $default;
        }
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
    public function getNonRouteUrl($urlPart)
    {
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
    public function getUrl($extraQuery = '', $includeQuery = false)
    {

        $url = $this->base . '/';
        if ($this->isModule) {
            $url .= $this->module . '/';
        }
        switch ($this->type) {
            case 'ws':
            case 'controller':
            case 'api':
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
    public function getType()
    {
        return $this->type;
    }

    /**
     * It returns the current name of the module
     *
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @param string     $key
     * @param null|mixed $valueIfNotFound
     *
     * @return mixed
     */
    public function getQuery($key, $valueIfNotFound = null)
    {
        return isset($this->queries[$key]) ? $this->queries[$key] : $valueIfNotFound;
    }

    /**
     * It sets a query value
     *
     * @param string     $key
     * @param null|mixed $value
     */
    public function setQuery($key, $value)
    {
        $this->queries[$key] = $value;
    }

    /**
     * It returns the current name of the controller.
     *
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     *
     * @param  $controller
     *
     * @return RouteOne
     */
    public function setController($controller)
    {
        if(is_array($this->whitelist['controller']) ) { // there is a whitelist
            if (in_array(strtolower($controller),$this->whitelistLower['controller'],true)) {
                $p=array_search($controller,$this->whitelistLower['controller'],true);
                $this->controller=$this->whitelist['controller'][$p]; // we returned the same value but with the right case.
                return $this;
            }
            // and this value is not found there.
            $this->controller=$this->defController;
            $this->notAllowed=true;
            return $this;
        }
        $this->controller = $controller;
        return $this;
    }

    /**
     *
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     *
     *
     * @param  $action
     *
     * @return RouteOne
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     *
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     *
     *
     * @param  $id
     *
     * @return RouteOne
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     *
     *
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     *
     *
     * @param  $event
     *
     * @return RouteOne
     */
    public function setEvent($event)
    {
        $this->event = $event;
        return $this;
    }

    /**
     *
     *
     * @return string
     */
    public function getIdparent()
    {
        return $this->idparent;
    }

    /**
     * @param  $idParent
     *
     * @return RouteOne
     */
    public function setIdParent($idParent)
    {
        $this->idparent = $idParent;
        return $this;
    }

    /**
     *
     *
     * @return string
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param string $extra
     *
     * @return RouteOne
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;
        return $this;
    }

    /**
     *
     *
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }
    /**
     *
     * @param  $category
     *
     * @return RouteOne
     */
    public function setCategory($category)
    {
        if(is_array($this->whitelist['category']) ) { // there is a whitelist
            if (in_array(strtolower($category),$this->whitelistLower['category'],true)) {
                $p=array_search($category,$this->whitelistLower['category'],true);

                $this->category=$this->whitelist['category'][$p]; // we returned the same value but with the right case.
                return $this;
            }
            // and this value is not found there.
            $this->category=$this->defCategory;

            $this->notAllowed=true;
            return $this;
        }
        $this->category = $category;
        return $this;
    }

    /**
     *
     *
     * @return mixed
     */
    public function getSubcategory()
    {
        return $this->subcategory;
    }

    /**
     *
     *
     * @return mixed
     */
    public function getSubsubcategory()
    {
        return $this->subsubcategory;
    }

    /**
     * @return bool
     */
    public function isPostBack()
    {
        return $this->isPostBack;
    }

    /**
     * @param bool $isPostBack
     *
     * @return RouteOne
     */
    public function setIsPostBack($isPostBack)
    {
        $this->isPostBack = $isPostBack;
        return $this;
    }
}
