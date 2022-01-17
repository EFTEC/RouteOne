<?php
/** @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 * @noinspection PhpMissingParamTypeInspection
 * @noinspection PhpMissingReturnTypeInspection
 * @noinspection PrintfScanfArgumentsInspection
 * @noinspection ReturnTypeCanBeDeclaredInspection
 */

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
 * @version   1.20 2021-04-24
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
    /** @var string. It's the event (such as "click on button"). */
    public $extra;
    /** @var string The current category. It is useful for the type 'front' */
    public $category;
    /** @var string The current sub-category. It is useful for the type 'front' */
    public $subcategory;
    /** @var string The current sub-sub-category. It is useful for the type 'front' */
    public $subsubcategory;
    /** @var null|string the current server name. If not set then it is calculated by $_SERVER['SERVER_NAME'] */
    public $serverName;
    /** @var boolean it's true if the page is POST, otherwise (GET,DELETE or PUT) it is false. */
    public $isPostBack = false;
    /** @var string The current HTML METHOD. It is always uppercase and only inside the array $allowedVerbs */
    public $verb = 'GET';
    /** @var string[] The list of allowed $verb. In case of error, the $verb is equals to GET */
    public $allowedVerbs = ['GET', 'POST', 'PUT', 'DELETE'];
    /** @var string[] Allowed fields to be read and parsed by callObjectEx() */
    public $allowedFields = ['controller', 'action', 'verb', 'event', 'type', 'module', 'id', 'idparent', 'category'
        , 'subcategory', 'subsubcategory'];
    /** @var bool if true then the whitelist validation failed and the value is not allowed */
    public $notAllowed = false;
    public $lastError = [];
    /** @var string|null it stores the current path calculated by */
    public $currentPath;
    public $path = [];
    /** @var array the queries fetched, excluding "req","_extra" and "_event" */
    public $queries = [];
    public $httpHost = '';
    public $requestUri = '';
    /** @var null|array It is an associative array that helps to identify the api and ws route. */
    protected $identify = ['api' => 'api', 'ws' => 'ws', 'controller' => ''];
    /** @var array[] it holds the whitelist. Ex: ['controller'=>['a1','a2','a3']] */
    protected $whitelist = [
        'controller' => null,
        'category' => null,
        'action' => null,
        'subcategory' => null,
        'subsubcategory' => null,
        'module' => null
    ];
    protected $whitelistLower = [
        'controller' => null,
        'category' => null,
        'action' => null,
        'subcategory' => null,
        'subsubcategory' => null,
        'module' => null
    ];
    /**
     * @var string|null=['api','ws','controller','front'][$i]
     */
    private $forceType;
    private $defController;
    private $defAction;
    private $defCategory;
    private $defSubCategory;
    private $defSubSubCategory;
    private $defModule;
    /** @var array|bool it stores the list of path used for the modules */
    private $moduleList;
    private $isModule;
    /**
     * <ul>
     * <li><b>none:</b>if the path uses a module then the <b>type</b> is calculated normally (default)</li>
     * <li><b>modulefront:</b>if the path uses a module then the <b>type</b> is <b>front</b>. If it doesn't use a module
     * then it is a <b>controller, api or ws</b></li>
     * <li><b>nomodulefront:</b>if the path uses a module then the <b>type</b> is <b>controller, api or ws</b>.
     * If it doens't use module then it is <b>front</b></li>
     * </ul>
     * @var string=['none','modulefront','nomodulefront'][$i]
     */
    private $moduleStrategy;
    private $isFetched = false;

    /**
     * RouteOne constructor.
     *
     * @param string     $base           base url with or without trailing slash (it's removed if its set).<br>
     *                                   Example: ".","http://domain.dom", "http://domain.dom/subdomain"<br>
     * @param string     $forcedType     =['api','ws','controller','front'][$i]<br>
     *                                   <b>api</b> then it expects a path as api/controller/action/id/idparent<br>
     *                                   <b>ws</b> then it expects a path as ws/controller/action/id/idparent<br>
     *                                   <b>controller</b> then it expects a path as controller/action/id/idparent<br>
     *                                   <b>front</b> then it expects a path as /category/subc/subsubc/id<br>
     * @param bool|array $isModule       if true then the route start reading a module name<br>
     *                                   <b>false</b> controller/action/id/idparent<br>
     *                                   <b>true</b> module/controller/action/id/idparent<br>
     *                                   <b>array</b> if the value is an array then the value is determined if the
     *                                   first
     *                                   part of the path is in the array. Example
     *                                   ['modulefolder1','modulefolder2']<br>
     * @param bool       $fetchValues    (default false), if true then it also calls the method fetch()
     * @param string     $moduleStrategy =['none','modulefront','nomodulefront'][$i] <br>
     *                                   it changes the strategy to determine the type of url determined if the path has
     *                                   a module or not.<br>
     *                                   <b>$forcedType</b> must be null, otherwise this value is not calculated.<br>
     *                                   <ul>
     *                                   <li><b>none:</b>if the path uses a module then the <b>type</b> is calculated
     *                                   normally (default)</li>
     *                                   <li><b>modulefront:</b>if the path uses a module then the <b>type</b> is
     *                                   <b>front</b>. If it doesn't use a module then it is a <b>controller, api or
     *                                   ws</b></li>
     *                                   <li><b>nomodulefront:</b>if the path uses a module then the <b>type</b> is
     *                                   <b>controller, api or ws</b>. If it doens't use module then it is
     *                                   <b>front</b></li>
     *                                   </ul>
     */
    public function __construct($base = '', $forcedType = null, $isModule = false, $fetchValues = false, $moduleStrategy = 'none')
    {
        $this->base = rtrim($base, '/');
        $this->forceType = $forcedType;
        $this->moduleStrategy = $moduleStrategy;
        if ($forcedType !== null) {
            $this->type = $forcedType;
        }
        if (is_bool($isModule)) {
            $this->moduleList = null;
            $this->isModule = $isModule;
        } else {
            $this->moduleList = $isModule;
            $this->isModule = false;
        }
        $this->isPostBack = false;

        if (isset($_SERVER['REQUEST_METHOD']) && in_array($_SERVER['REQUEST_METHOD'], $this->allowedVerbs, true)) {
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
     * @param string $defController     Default Controller
     * @param string $defAction         Default action
     * @param string $defCategory       Default category
     * @param string $defSubCategory    Default subcategory
     * @param string $defSubSubCategory The default sub-sub-category
     * @param string $defModule         The default module.
     * @return $this
     */
    public function setDefaultValues($defController = '', $defAction = '', $defCategory = ''
        ,                            $defSubCategory = '', $defSubSubCategory = '', $defModule = '')
    {
        if ($this->isFetched) {
            throw new RuntimeException("RouteOne: you can't call setDefaultValues() after fetch()");
        }
        $this->defController = $defController;
        $this->defAction = $defAction;
        $this->defCategory = $defCategory;
        $this->defSubCategory = $defSubCategory;
        $this->defSubSubCategory = $defSubSubCategory;
        $this->defModule = $defModule;
        return $this;
    }

    public function clearPath() {
        $this->path=[];
    }
    public function addPath($path,$name=null)
    {
        if($name===null) {
            $this->path[] = $path;
        } else {
            $this->path[$name] = $path;
        }
    }

    /**
     * @return int|string|null return false if not path is evaluated,<br>
     *                     otherwise it returns the number/name of the path
     */
    public function fetchPath()
    {
        $this->lastError = [];
        $this->currentPath=null;
        $urlFetchedOriginal = $this->getUrlFetchedOriginal();
        $this->queries = $_GET;
        unset($this->queries['req'], $this->queries['_event'], $this->queries['_extra']);
        foreach ($this->path as $pnum => $pattern) {

            $bigs = explode('?', $pattern, 2); // aaa/bbb/{ccc}?dd=2  [/aaa/bbb/{ccc},dd=2]
            $p0 = $bigs[0]; // aaa/bbb/{ccc}
            $posBase = strpos($p0, '{');
            if ($posBase === false) {
                // path does not contain { }
                $base=$p0; // aaa/bbb/ccc
                $p0b='';

            } else {
                $base = $posBase === 0 ? '' : substr($p0, 0, $posBase - 1); // aaa/bbb/
                $p0b = substr($p0, $posBase); // {ccc}
            }
            if ($base !== '' && strpos($urlFetchedOriginal, $base) !== 0) {
                // base url does not match.
                $this->lastError[$pnum] = "Pattern [$pnum], base url does not match";
                continue;
            }
            $urlFetched = substr($urlFetchedOriginal, strlen($base));
            // nginx returns a path as /aaa/bbb apache aaa/bbb
            if ($urlFetched !== '') {
                $urlFetched = ltrim($urlFetched, '/');
            }
            $path = $this->getExtracted($urlFetched);
            $partTmps = ($p0b!=='') ? explode('/', $p0b) : [];
            if (count($path) > count($partTmps)) {
                $this->lastError[$pnum] = "Pattern [$pnum] is too big to the current url";
                continue;
            }
            foreach ($partTmps as $key => $v) {
                $p = trim($v, '{}' . " \t\n\r\0\x0B");
                $tmp = explode(':', $p, 2); // we separate by fieldname:default value
                if (count($tmp) < 2) {
                    if (!array_key_exists($key, $path) || !isset($path[$key])) {
                        // the field is required but there we don't find any value
                        $this->lastError[$pnum] = "Pattern [$pnum] required field ($v) not found in url";
                        continue;
                    }
                    $name = $p;
                    $value = $path[$key];
                } else {
                    $name = $tmp[0];
                    if (isset($path[$key]) && $path[$key]) {
                        $value = $path[$key];
                    } else {
                        // value not found, set default value
                        $value = $tmp[1];
                    }
                }
                // 'controller', 'action', 'verb', 'event', 'type', 'module', 'id', 'idparent', 'category'
                //        , 'subcategory', 'subsubcategory'
                switch ($name) {
                    case 'controller':
                        $this->controller =preg_replace('/[^a-zA-Z0-9_]/s', "", $value);
                        break;
                    case 'action':
                        $this->action = preg_replace('/[^a-zA-Z0-9_]/s', "",$value);
                        break;
                    case 'module':
                        $this->module = preg_replace('/[^a-zA-Z0-9_]/s', "",$value);
                        break;
                    case 'id':
                        $this->id = $value;
                        break;
                    case 'idparent':
                        $this->idparent = $value;
                        break;
                    case 'category':
                        $this->category = preg_replace('/[^a-zA-Z0-9_]/s', "",$value);
                        break;
                    case 'subcategory':
                        $this->subcategory = preg_replace('/[^a-zA-Z0-9_]/s', "",$value);
                        break;
                    case 'subsubcategory':
                        $this->subsubcategory = preg_replace('/[^a-zA-Z0-9_]/s', "",$value);
                        break;
                    default:
                        throw new RuntimeException('pattern incorrecto [$name:$value]');
                }
            }
            $this->event = $this->request('_event');
            $this->extra = $this->request('_extra');
            $this->currentPath=$pnum;
            break;
        }

        return $this->currentPath;
    }

    /**
     *
     * It uses the next strategy to obtain the parameters;<br>
     * <ul>
     * <li><b>If the type is not frontend:</b></li>
     * <li><b>api:</b>. Expected path: api/Controller/action/id/idparent?_event=xx&extra=xxx</li>
     * <li><b>ws:</b>. Expected path: ws/Controller/action/id/idparent?_event=xx&extra=xxx</li>
     * <li><b>controller:</b>. Expected path: Controller/action/id/idparent?_event=xx&extra=xxx</li>
     * <li><b>controller (using module):</b>. Module/Controller/action/id/idparent?_event=xx&extra=xxx</li>
     * <li><b>api (using module):</b>. Module/api/ControllerApi/action/id/idparent/?_event=xx&extra=xxx</li>
     * <li><b>ws (using module):</b>. Module/ws/ControllerWS/action/id/idparent/?_event=xx&extra=xxx</li>
     * <li><b>If the type is frontend:</b></li>
     * <li><b>frontend:</b>. Expected path: category/subcategory/subsubcategory/id/idparent?_event=xx&extra=xxx</li>
     * <li><b>frontend (using module):</b>.
     * Module/category/subcategory/subsubcategory/id/idparent?_event=xx&extra=xxx</li>
     * </ul>
     */
    public function fetch()
    {
        //$urlFetched = $_GET['req'] ?? null; // controller/action/id/..
        $urlFetched = $this->getUrlFetchedOriginal(); // // controller/action/id/..
        $this->isFetched = true;

        unset($_GET['req']);
        /** @noinspection HostnameSubstitutionInspection */
        $this->httpHost = $_SERVER['HTTP_HOST'] ?? '';
        $this->requestUri = $_SERVER['REQUEST_URI'] ?? '';
        // nginx returns a path as /aaa/bbb apache aaa/bbb
        if ($urlFetched !== '') {
            $urlFetched = ltrim($urlFetched, '/');
        }
        $this->queries = $_GET;
        unset($this->queries['req'], $this->queries['_event'], $this->queries['_extra']);
        $path = $this->getExtracted($urlFetched,true);
        //$first = $path[0] ?? $this->defController;
        if (isset($path[0]) && $this->moduleList !== null) {
            // if moduleArray has values then we find if the current path is a module or not.
            $this->isModule = in_array($path[0], $this->moduleList, true);
        }
        $id = 0;
        if ($this->isModule) {
            $this->module = $path[$id] ?? $this->defModule;
            $id++;
            if ($this->moduleStrategy === 'modulefront') {
                // the path is not a module, then type is set as front.
                $this->type = 'front';
            }
        } else {
            $this->module = $this->defModule;
            if ($this->moduleStrategy === 'nomodulefront') {
                // the path is not a module, then type is set as front.
                $this->type = 'front';
            }
        }
        if ($this->forceType !== null) {
            $this->type = $this->forceType;
        }
        if (!$this->type) {
            $this->type = 'controller';
            $this->setController((!$path[$id]) ? $this->defController : $path[$id]);
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

        $this->action = $path[$id] ?? null;
        $id++;
        $this->action = $this->action ?: $this->defAction; // $this->action is never undefined, so we don't need isset
        $this->id = $path[$id] ?? null;
        $id++;
        $this->idparent = $path[$id] ?? null;
        /** @noinspection PhpUnusedLocalVariableInspection */
        $id++;
        $this->event = $this->request('_event');
        $this->extra = $this->request('_extra');
    }

    protected function str_replace_ex($search, $replace, $subject, $limit = 99999)
    {
        return implode($replace, explode($search, $subject, $limit + 1));
    }

    private function request($id)
    {
        return $_POST[$id] ?? $_GET[$id] ?? null;
    }

    /**
     * It is an associative array with the allowed paths or null (default behaviour) to allows any path.<br>
     * The comparison ignores cases but the usage is "case-sensitive" and it uses the case used here<br>
     * For example: if we allowed the controller called "Controller1" then:<br>
     * <ul>
     * <li>somedomain.dom/Controller1 is accepted</li>
     * <li>somedomain.dom/controller1  is also accepted (and controller is equals as "Controller1")</li>
     * </ul>
     * <b>Example:</b>
     * <pre>
     * // we only want to allow the controllers called Purchase, Invoice and Customer.
     * $this->setWhiteList('controller',['Purchase','Invoice','Customer']);
     * </pre>
     * <b>Note:</b> this must be executed before fetch()
     * @param string     $type  =['controller','category','action','subcategory','subsubcategory','module'][$i]
     * @param array|null $array if null (default value) then we don't validate the information.
     */
    public function setWhiteList($type, $array)
    {
        if ($this->isFetched && $array !== null) {
            throw new RuntimeException("RouteOne: you can't call setWhiteList() after fetch()");
        }
        $type = strtolower($type);
        $this->whitelist[$type] = $array;
        $this->whitelistLower[$type] = is_array($array) ? array_map('strtolower', $array) : null;
    }

    /**
     * If the subdomain is empty or different to www, then it redirects to www.domain.com.<br>
     * <b>Note: It doesn't work with localhost, domain without TLD (netbios) or ip domains. It is on purpose.</b><br>
     * <b>Note: If this code needs to redirect, then it stops the execution of the code. Usually,
     * it must be called at the top of the code</b>
     *
     * @param bool $https    If true then it also redirects to https
     * @param bool $redirect if true (default) then it redirects the header. If false, then it returns the new full url
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

    private function getLocation($https)
    {
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
     * @param bool $redirect if true (default) then it redirects the header. If false, then it returns the new url
     * @return string|null It returns null if the operation failed (no correct url or no need to redirect)<br>
     *                       Otherwise, if $redirect=false, it returns the url to redirect.
     */
    public function alwaysHTTPS($redirect = true)
    {
        if (strpos($this->httpHost, '.') === false || ip2long($this->httpHost)) {
            return null;
        }
        $https = $_SERVER['HTTPS'] ?? '';
        if (empty($https) || $https === 'off') {
            $port = $_SERVER['HTTP_PORT'] ?? '443';
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
     * If the subdomain is www (example www.domain.dom) then it redirects to a naked domain "domain.dom"<br>
     * <b>Note: It doesn't work with localhost, domain without TLD (netbios) or ip domains. It is on purpose.</b><br>
     * <b>Note: If this code needs to redirect, then we should stop the execution of any other code. Usually,
     * it must be called at the top of the code</b>
     *
     * @param bool $https    If true then it also redirects to https
     * @param bool $redirect if true (default) then it redirects the header. If false, then it returns the new url
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
     *                               If method does not exist then it will use $methodGet or $methodPost
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
        if ($this->notAllowed === true) {
            throw new UnexpectedValueException('Input is not allowed');
        }
        if ($this->type !== 'front') {
            if ($this->controller === null) {
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
            $msgError = "Action [$actionRequest or $actionGetPost] $pb not found for class [$op]";
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
        if ($this->notAllowed === true) {
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
            $msgError = "Action ex [$actionRequest or $actionGetPost] $pb not found for class [$op]";
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
     * is not modified, and it is returned as is.<br>
     * If the name starts with uc_,lc_,u_,l_ then it is converted into ucfirst,lcfirst,uppercase or lowercase.
     *
     * @param string $format
     *
     * @return string
     */
    private function replaceNamed($format)
    {
        return preg_replace_callback("/{(\w+)}/", function ($matches) {
            $nameField = $matches[1];
            if (in_array($nameField, $this->allowedFields, true) === false) {
                return '{' . $nameField . '}';
            }
            $result = $this->{$nameField};
            if (strpos($result, '_') > 0) {
                $x = explode('_', $result)[0];
                switch ($x) {
                    case 'uc':
                        $result = ucfirst(strtolower($result));
                        break;
                    case 'lc':
                        $result = lcfirst(strtoupper($result));
                        break;
                    case 'u':
                        $result = strtoupper($result);
                        break;
                    case 'l':
                        $result = strtolower($result);
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
        $sn = $_SERVER['SCRIPT_NAME'] ?? '';
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
        $server_name = $this->serverName ?? $_SERVER['SERVER_NAME'] ?? null;
        $sp = $_SERVER['SERVER_PORT'] ?? 80;
        $port = !in_array($sp, ['80', '443'], true) ? ':' . $sp : '';
        $https = $_SERVER['HTTPS'] ?? '';
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

    // .htaccess:
    // RewriteRule ^(.*)$ index.php?req=$1 [L,QSA]

    public function reset()
    {
        // $this->base=''; base is always keep
        $this->isFetched = false;
        $this->defController = '';
        $this->defCategory = '';
        $this->defSubCategory = '';
        $this->defSubSubCategory = '';
        $this->defModule = '';
        $this->forceType = null;
        $this->defAction = '';
        $this->isModule = '';
        $this->moduleStrategy = 'none';
        $this->moduleList = null;
        $this->id = null;
        $this->event = null;
        $this->idparent = null;
        $this->extra = null;
        $this->verb = 'GET';
        $this->notAllowed = false;
        $this->clearPath();
        $this->currentPath=null;
        return $this;
    }

    /**
     * This function is used to identify the type automatically. If the url is empty then it is marked as default<br>
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
     * It reconstructsan url using the current information.<br>
     * <b>Note:<b>. It discards any information outside the type
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
                $url .= "$this->category/$this->subcategory/$this->subsubcategory/";
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
        return $this->queries[$key] ?? $valueIfNotFound;
    }

    /**
     * It gets the current header (if any)
     *
     * @param string     $key
     * @param null|mixed $valueIfNotFound
     * @return mixed|null
     */
    public function getHeader($key, $valueIfNotFound = null)
    {
        $keyname = 'HTTP_' . $key;
        return $_SERVER[$keyname] ?? $valueIfNotFound;
    }

    /**
     * It gets the body of a request.
     *
     * @param bool $jsonDeserialize
     * @param bool $asAssociative
     * @return false|mixed|string
     */
    public function getBody($jsonDeserialize = false, $asAssociative = true)
    {
        $entityBody = file_get_contents('php://input');
        if (!$jsonDeserialize) {
            return $entityBody;
        }
        return json_decode($entityBody, $asAssociative);
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
        if (is_array($this->whitelist['controller'])) { // there is a whitelist
            if (in_array(strtolower($controller), $this->whitelistLower['controller'], true)) {
                $p = array_search($controller, $this->whitelistLower['controller'], true);
                $this->controller = $this->whitelist['controller'][$p]; // we returned the same value but with the right case.
                return $this;
            }
            // and this value is not found there.
            $this->controller = $this->defController;
            $this->notAllowed = true;
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
     * It gets the current category
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * It sets the current category
     *
     * @param string $category
     *
     * @return RouteOne
     */
    public function setCategory($category)
    {
        if (is_array($this->whitelist['category'])) { // there is a whitelist
            if (in_array(strtolower($category), $this->whitelistLower['category'], true)) {
                $p = array_search($category, $this->whitelistLower['category'], true);

                $this->category = $this->whitelist['category'][$p]; // we returned the same value but with the right case.
                return $this;
            }
            // and this value is not found there.
            $this->category = $this->defCategory;

            $this->notAllowed = true;
            return $this;
        }
        $this->category = $category;
        return $this;
    }

    /**
     * It gets the current sub category
     *
     * @return string
     */
    public function getSubcategory()
    {
        return $this->subcategory;
    }

    /**
     * It gets the current sub-sub-category
     *
     * @return string
     */
    public function getSubsubcategory()
    {
        return $this->subsubcategory;
    }

    /**
     * Returns true if the current web method is POST.
     *
     * @return bool
     */
    public function isPostBack()
    {
        return $this->isPostBack;
    }

    /**
     * It sets if the current state is postback
     *
     * @param bool $isPostBack
     *
     * @return RouteOne
     */
    public function setIsPostBack($isPostBack)
    {
        $this->isPostBack = $isPostBack;
        return $this;
    }

    /**
     * It gets the current list of module lists or null if there is none.
     *
     * @return array|bool
     * @noinspection PhpUnused
     */
    public function getModuleList()
    {
        return $this->moduleList;
    }

    /**
     * It sets the current list of modules or null to assigns nothing.
     *
     * @param array|bool $moduleList
     * @noinspection PhpUnused
     *
     * @return RouteOne
     */
    public function setModuleList($moduleList)
    {
        $this->moduleList = $moduleList;
        return $this;
    }

    /**
     * It gets the current strategy of module.
     *
     * @return string=['none','modulefront','nomodulefront'][$i]
     * @see \eftec\routeone\RouteOne::setModuleStrategy
     */
    public function getModuleStrategy()
    {
        return $this->moduleStrategy;
    }

    /**
     * it changes the strategy to determine the type of url determined if the path has a module or not.<br>
     * <b>$forcedType</b> must be null, otherwise this value is not used.<br>
     * <ul>
     * <li><b>none:</b>if the path uses a module then the <b>type</b> is calculated normally (default)</li>
     * <li><b>modulefront:</b>if the path uses a module then the <b>type</b> is <b>front</b>. If it doesn't use a module
     * then it is a <b>controller, api or ws</b></li>
     * <li><b>nomodulefront:</b>if the path uses a module then the <b>type</b> is <b>controller, api or ws</b>.
     * If it doens't use module then it is <b>front</b></li>
     * </ul>
     * @param string $moduleStrategy
     *
     * @return RouteOne
     */
    public function setModuleStrategy($moduleStrategy)
    {
        $this->moduleStrategy = $moduleStrategy;
        return $this;
    }

    /**
     * @return mixed|null
     */
    private function getUrlFetchedOriginal()
    {
        $this->notAllowed = false; // reset
        $this->isFetched = true;
        $urlFetchedOriginal = $_GET['req'] ?? null; // controller/action/id/..
        unset($_GET['req']);
        /** @noinspection HostnameSubstitutionInspection */
        $this->httpHost = isset($_SERVER['HTTP_HOST']) ? filter_var($_SERVER['HTTP_HOST'], FILTER_SANITIZE_URL) : '';
        $this->requestUri = isset($_SERVER['REQUEST_URI']) ? filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL) : '';
        return $urlFetchedOriginal;
    }

    /**
     * @param string $urlFetched
     * @param bool   $sanitize
     * @return array
     */
    private function getExtracted($urlFetched,$sanitize=false): array
    {
        if($sanitize) {
            $urlFetched = filter_var($urlFetched, FILTER_SANITIZE_URL);
        }
        if (is_array($this->identify) && $this->type === '') {
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
        return explode('/', $urlFetched);
    }
}
