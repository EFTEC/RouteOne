<?php /** @noinspection PrintfScanfArgumentsInspection */

/** @noinspection PhpUnused
 * @noinspection UnknownInspectionInspection
 */

namespace eftec\routeone;

use Exception;
use RuntimeException;
use UnexpectedValueException;

/**
 * Class RouteOne
 *
 * @package   RouteOne
 * @copyright 2019-2023 Jorge Castro Castillo
 * @license   (dual licence lgpl v3 and commercial)
 * @version   1.27 2023-02-15
 * @link      https://github.com/EFTEC/RouteOne
 */
class RouteOne
{
    public const VERSION = '1.27';
    /** @var string The name of the argument used by apache and nginx (by default it is req) */
    public $argumentName = 'req';
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
    /**
     * @var string|null it stores the current path (name) calculated by fetchPath() or null if no path is set.
     */
    public $currentPath;

    /** @var array  */
    public $pathName = [];
    /** @var array  */
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
     * If it doesn't use module then it is <b>front</b></li>
     * </ul>
     * @var string=['none','modulefront','nomodulefront'][$i]
     */
    private $moduleStrategy;
    private $isFetched = false;

    /**
     * RouteOne constructor.
     *
     * @param string      $base           base url with or without trailing slash (it's removed if its set).<br>
     *                                    Example: ".","http://domain.dom", "http://domain.dom/subdomain"<br>
     * @param string|null $forcedType     =['api','ws','controller','front'][$i]<br>
     *                                    <b>api</b> then it expects a path as api/controller/action/id/idparent<br>
     *                                    <b>ws</b> then it expects a path as ws/controller/action/id/idparent<br>
     *                                    <b>controller</b> then it expects a path as controller/action/id/idparent<br>
     *                                    <b>front</b> then it expects a path as /category/subc/subsubc/id<br>
     * @param bool|array  $isModule       if true then the route start reading a module name<br>
     *                                    <b>false</b> controller/action/id/idparent<br>
     *                                    <b>true</b> module/controller/action/id/idparent<br>
     *                                    <b>array</b> if the value is an array then the value is determined if the
     *                                    first
     *                                    part of the path is in the array. Example
     *                                    ['modulefolder1','modulefolder2']<br>
     * @param bool        $fetchValues    (default false), if true then it also calls the method fetch()
     * @param string      $moduleStrategy =['none','modulefront','nomodulefront'][$i] <br>
     *                                    it changes the strategy to determine the type of url determined if the path
     *                                    has a module or not.<br>
     *                                    <b>$forcedType</b> must be null, otherwise this value is not calculated.<br>
     *                                    <ul>
     *                                    <li><b>none:</b>if the path uses a module then the <b>type</b> is calculated
     *                                    normally (default)</li>
     *                                    <li><b>modulefront:</b>if the path uses a module then the <b>type</b> is
     *                                    <b>front</b>. If it doesn't use a module then it is a <b>controller, api or
     *                                    ws</b></li>
     *                                    <li><b>nomodulefront:</b>if the path uses a module then the <b>type</b> is
     *                                    <b>controller, api or ws</b>. If it doesn't use module then it is
     *                                    <b>front</b></li>
     *                                    </ul>
     */
    public function __construct(string $base = '', ?string $forcedType = null, $isModule = false,
                                bool   $fetchValues = false, string $moduleStrategy = 'none')
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
    public function setDefaultValues(string $defController = '', string $defAction = '', string $defCategory = '',
                                     string $defSubCategory = '', string $defSubSubCategory = '',
                                     string $defModule = ''): self
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

    /**
     * It clears all the paths defined
     *
     * @return void
     */
    public function clearPath(): void
    {
        $this->path = [];
        $this->pathName = [];
    }
    // 'controller', 'action', 'verb', 'event', 'type', 'module', 'id', 'idparent', 'category'
    //        , 'subcategory', 'subsubcategory'
    /**
     * It adds a paths that could be evaluated using fetchPath()<br>
     * <b>Example:</b><br>
     * <pre>
     * $this->addPath('api/{controller}/{action}/{id:0}','apipath');
     * $this->addPath('{controller}/{action}/{id:0}','webpath');
     * $this->addPath('{controller:root}/{action}/{id:0}','webpath'); // root path using default
     * </pre>
     * <b>Note:</b><br>
     * The first part of the path, before the "{" is used to determine which path will be used.<br>
     * Example "path/{controller}" and "path/{controller}/{id}", the system will consider that are the same path
     * @param string      $path The path, example "aaa/{controller}/{action:default}/{id}"<br>
     *                          Where <b>default</b> is the optional default value.
     *                          <ul>
     *                          <li><b>{controller}</b>: The controller (class) to call</li>
     *                          <li><b>{action}</b>: The action (method) to call</li>
     *                          <li><b>{verb}</b>: The verb of the action (GET/POST,etc)</li>
     *                          <li><b>{type}</b>: The type (value)</li>
     *                          <li><b>{module}</b>: The module (value)</li>
     *                          <li><b>{id}</b>: The id (value)</li>
     *                          <li><b>{idparent}</b>: The id parent (value)</li>
     *                          <li><b>{category}</b>: The category (value)</li>
     *                          <li><b>{subcategory}</b>: The subcategory (value)</li>
     *                          <li><b>{subsubcategory}</b>: The subsubcategory (value)</li>
     *                          </ul>
     * @param string|null $name (optional), the name of the path
     * @return $this
     */
    public function addPath(string $path, ?string $name = null): RouteOne
    {
        if (!$path) {
            throw new RuntimeException('Path must not be empty, use default value to set a root path');
        }
        $x0=strpos($path,'{');
        if($x0===false) {
            $partStr='';
            $base=$path;
        } else {
            $base=substr($path,0,$x0);
            $partStr=substr($path,$x0);
        }
        $items=explode('/',$partStr);
        $itemArr=[];
        foreach($items as $v) {
            $p= trim($v, '{}' . " \t\n\r\0\x0B");
            $itemAdd = explode(':', $p, 2);
            if(count($itemAdd)===1) {
                $itemAdd[]=null; // add a default value
            }
            $itemArr[]=$itemAdd;
        }
        if ($name === null) {
            $this->pathName[]=$base;
            $this->path[] = $itemArr;
        } else {
            $this->pathName[$name]=$base;
            $this->path[$name] = $itemArr;
        }
        return $this;
    }

    /**
     * It fetches the path previously defined by addPath.
     *
     * @return int|string|null return null if not path is evaluated,<br>
     *                     otherwise, it returns the number/name of the path. It could return the value 0 (first path)
     * @noinspection NotOptimalRegularExpressionsInspection
     */
    public function fetchPath()
    {
        $this->lastError = [];
        $this->currentPath = null;
        $urlFetchedOriginal = $this->getUrlFetchedOriginal();
        $this->queries = $_GET;
        unset($this->queries[$this->argumentName], $this->queries['_event'], $this->queries['_extra']);
        foreach ($this->path as $pnum => $pattern) {

            if ($this->pathName[$pnum] !== '' && strpos($urlFetchedOriginal ?? '', $this->pathName[$pnum]) !== 0) {
                // basePath url does not match.
                $this->lastError[$pnum] = "Pattern [$pnum], base url does not match";
                continue;
            }
            $urlFetched = substr($urlFetchedOriginal ?? '', strlen($this->pathName[$pnum]));
            // nginx returns a path as /aaa/bbb apache aaa/bbb
            if ($urlFetched !== '') {
                $urlFetched = ltrim($urlFetched, '/');
            }
            $path = $this->getExtracted($urlFetched);

            foreach ($this->path[$pnum] as $key => $v) {
                if ($v[1]===null) {
                    if (!array_key_exists($key, $path) || !isset($path[$key])) {
                        // the field is required but there we don't find any value
                        $this->lastError[$pnum] = "Pattern [$pnum] required field ($v[0]) not found in url";
                        continue;
                    }
                    $name = $v[0];
                    $value = $path[$key];
                } else {
                    $name = $v[0];
                    if (isset($path[$key]) && $path[$key]) {
                        $value = $path[$key];
                    } else {
                        // value not found, set default value
                        $value = $v[1];
                    }
                }
                // 'controller', 'action', 'verb', 'event', 'type', 'module', 'id', 'idparent', 'category'
                //        , 'subcategory', 'subsubcategory'
                switch ($name) {
                    case 'controller':
                        $this->controller = preg_replace('/[^a-zA-Z0-9_]/', "", $value);
                        break;
                    case 'action':
                        $this->action = preg_replace('/[^a-zA-Z0-9_]/', "", $value);
                        break;
                    case 'module':
                        $this->module = preg_replace('/[^a-zA-Z0-9_]/', "", $value);
                        break;
                    case 'id':
                        $this->id = $value;
                        break;
                    case 'idparent':
                        $this->idparent = $value;
                        break;
                    case 'category':
                        $this->category = preg_replace('/[^a-zA-Z0-9_]/', "", $value);
                        break;
                    case 'subcategory':
                        $this->subcategory = preg_replace('/[^a-zA-Z0-9_]/', "", $value);
                        break;
                    case 'subsubcategory':
                        $this->subsubcategory = preg_replace('/[^a-zA-Z0-9_]/', "", $value);
                        break;
                    default:
                        throw new RuntimeException('pattern incorrect [$name:$value]');
                }
            }

            $this->event = $this->getRequest('_event');
            $this->extra = $this->getRequest('_extra');
            $this->currentPath = $pnum;
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
    public function fetch(): void
    {
        //$urlFetched = $_GET['req'] ?? null; // controller/action/id/..
        $urlFetched = $this->getUrlFetchedOriginal(); // // controller/action/id/..
        $this->isFetched = true;
        unset($_GET[$this->argumentName]);
        /** @noinspection HostnameSubstitutionInspection */
        $this->httpHost = $_SERVER['HTTP_HOST'] ?? '';
        $this->requestUri = $_SERVER['REQUEST_URI'] ?? '';
        // nginx returns a path as /aaa/bbb apache aaa/bbb
        if ($urlFetched !== '') {
            $urlFetched = ltrim($urlFetched, '/');
        }
        $this->queries = $_GET;
        unset($this->queries[$this->argumentName], $this->queries['_event'], $this->queries['_extra']);
        $path = $this->getExtracted($urlFetched, true);
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
                $this->event = $this->getRequest('_event');
                $this->extra = $this->getRequest('_extra');
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
        $this->event = $this->getRequest('_event');
        $this->extra = $this->getRequest('_extra');
    }

    /**
     * @param string       $search
     * @param array|string $replace
     * @param string       $subject
     * @param int          $limit
     * @return string
     */
    protected function str_replace_ex(string $search, $replace, string $subject, int $limit = 99999): string
    {
        return implode($replace, explode($search, $subject, $limit + 1));
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
    public function setWhiteList(string $type, ?array $array): void
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
    public function alwaysWWW(bool $https = false, bool $redirect = true): ?string
    {
        $url = $this->httpHost;
        //if (strpos($url, '.') === false || ip2long($url)) {
        //}
        if (strpos($url ?? '', 'www.') === false) {
            $location = $this->getLocation($https);
            $location .= '//www.' . $url . $this->requestUri;
            if ($redirect) {
                header('HTTP/1.1 301 Moved Permanently');
                header('Location: ' . $location);
                if(http_response_code()) {
                    die(1);
                }
            }
            return $location;
        }
        if ($https) {
            return $this->alwaysHTTPS(false);
        }
        return null;
    }

    private function getLocation($https): string
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
    public function alwaysHTTPS(bool $redirect = true): ?string
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
                if(http_response_code()) {
                    die(1);
                }
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
    public function alwaysNakedDomain(bool $https = false, bool $redirect = true): ?string
    {
        $url = $this->httpHost;
        if (strpos($url ?? '', 'www.') === 0) {
            $host = substr($url, 4); // we remove the www. at first
            $location = $this->getLocation($https);
            $location .= '//' . $host . $this->requestUri;
            if ($redirect) {
                header('HTTP/1.1 301 Moved Permanently');
                header('Location: ' . $location);
                if(http_response_code()) {
                    die(1);
                }
                return '';
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
     * @deprecated
     * @see self::callObjectEx Use callObjectEx('{controller}Controller'); instead of callObject('%sController);
     */
    public function callObject(
        string $classStructure = '%sController', bool $throwOnError = true,
        string $method = '%sAction', string $methodGet = '%sActionGet',
        string $methodPost = '%sActionPost',
        array  $arguments = ['id', 'idparent', 'event']
    ): ?string
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
        if (!class_exists($op)) {
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
                /** @noinspection PrintfScanfArgumentsInspection */
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
     * @param string|null $classStructure  [optional] Default value='{controller}Controller'.<br>
     *                                     If the class contains a namespace, then it must includes it:<br>
     *                                     "namespace\subnamespace\{controller}Controller"
     * @param bool        $throwOnError    [optional] Default:true,  if true then it throws an exception. If false then
     *                                     it returns the error as a string (if any)
     * @param string|null $method          [optional] Default value='{action}Action'. The name of the method to call
     *                                     (get/post). If method does not exist then it will use $methodGet or
     *                                     $methodPost
     * @param string|null $methodGet       [optional] Default value='{action}Action{verb}'. The name of the method to
     *                                     call
     *                                     (get) but only if the method defined by $method is not defined.
     * @param string|null $methodPost      [optional] Default value='{action}Action{verb}'. The name of the method to
     *                                     call
     *                                     (post) but only if the method defined by $method is not defined.
     * @param array       $arguments       [optional] Default value=['id','idparent','event'] the arguments to pass to
     *                                     the function
     * @param array       $injectArguments [optional] You can inject arguments into the constructor of the instance.
     * @return string|null
     * @throws Exception
     */
    public function callObjectEx(
        ?string $classStructure = '{controller}Controller', bool $throwOnError = true,
        ?string $method = '{action}Action', ?string $methodGet = '{action}Action{verb}',
        ?string $methodPost = '{action}Action{verb}', array $arguments = ['id', 'idparent', 'event'],
        array   $injectArguments = []
    ): ?string
    {
        if ($this->notAllowed === true) {
            throw new UnexpectedValueException('Input method is not allowed',403);
        }
        $op = $this->replaceNamed($classStructure);
        if (!class_exists($op)) {
            if ($throwOnError) {
                throw new RuntimeException("Class $op doesn't exist",404);
            }
            return "Class $op doesn't exist";
        }
        try {
            $controller = new $op(...$injectArguments);
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
                throw new UnexpectedValueException($msgError,400);
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
     * @param string|null $format
     *
     * @return string
     */
    private function replaceNamed(?string $format): string
    {
        if($format===null) {
            return '';
        }
        return preg_replace_callback("/{(\w+)}/", function ($matches) {
            $nameField = $matches[1];
            $result = '';
            if (strpos($nameField ?? '', '_') > 0) {
                [$x, $nf] = explode('_', $nameField, 2);
                if (in_array($nf, $this->allowedFields, true) === false) {
                    return '{' . $nameField . '}';
                }
                switch ($x) {
                    case 'uc':
                        $result = ucfirst(strtolower($this->{$nf}));
                        break;
                    case 'lc':
                        $result = lcfirst(strtoupper($this->{$nf}));
                        break;
                    case 'u':
                        $result = strtoupper($this->{$nf});
                        break;
                    case 'l':
                        $result = strtolower($this->{$nf});
                        break;
                }
            } else {
                if (in_array($nameField, $this->allowedFields, true) === false) {
                    return '{' . $nameField . '}';
                }
                $result = $this->{$nameField};
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
    public function callFile(string $fileStructure = '%s.php', bool $throwOnError = true): ?string
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
    public function getCurrentUrl(bool $withoutFilename = true): string
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
    public function getCurrentServer(): string
    {
        $server_name = $this->serverName ?? $_SERVER['SERVER_NAME'] ?? null;
        $c=filter_var($server_name,FILTER_VALIDATE_DOMAIN,FILTER_FLAG_HOSTNAME);
        $server_name=$c?$server_name:$_SERVER['SERVER_ADDR']??'127.0.0.1';
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
     * @see RouteOne::getCurrentUrl
     * @see RouteOne::getCurrentServer
     */
    public function setCurrentServer(string $serverName): void
    {
        $this->serverName = $serverName;
    }

    /**
     * It sets the values of the route using customer values<br>
     * If the values are null, then it keeps the current values (if any)
     *
     * @param null|string $module         Name of the module
     * @param null|string $controller     Name of the controller.
     * @param null|string $action         Name of the action
     * @param null|string $id             Name of the id
     * @param null|string $idparent       Name of the idparent
     * @param null|string $category       Value of the category
     * @param string|null $subcategory    Value of the subcategory
     * @param string|null $subsubcategory Value of the sub-subcategory
     * @return $this
     */
    public function url(
        ?string $module = null,
        ?string $controller = null,
        ?string $action = null,
        ?string $id = null,
        ?string $idparent = null,
        ?string $category=null,
        ?string $subcategory=null,
        ?string $subsubcategory=null

    ): self
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
            $this->idparent = $idparent;
        }
        if ($category !== null) {
            $this->category = $category;
        }
        if ($subcategory !== null) {
            $this->subcategory = $subcategory;
        }
        if ($subsubcategory !== null) {
            $this->subsubcategory = $subsubcategory;
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
    ): RouteOne
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
    public function reset(): RouteOne
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
        $this->currentPath = null;
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
    public function setIdentifyType($array): void
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
     * @see RouteOne
     */
    public function getNonRouteUrl(string $urlPart): string
    {
        return $this->base . '/' . $urlPart;
    }

    /**
     * It reconstructs an url using the current information.<br>
     * <b>Example:</b><br>
     * <pre>
     * $currenturl=$this->getUrl();
     * $buildurl=$this->url('mod','controller','action',20)->getUrl();
     * </pre>
     * <b>Note:</b>. It discards any information outside the values pre-defined
     * (example: /controller/action/id/idparent/<cutcontent>?arg=1&arg=2)<br>
     * It does not consider the path() structure but the type of url.
     * @param string $extraQuery   If we want to add extra queries
     * @param bool   $includeQuery If true then it includes the queries in $this->queries
     *
     * @return string
     */
    public function getUrl(string $extraQuery = '', bool $includeQuery = false): string
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
     * Returns the url using the path and current values<br>
     * The trail "/" is always removed.
     * @param string|null $idPath If null then it uses the current path obtained by fetchUrl()<br>
     *                            If not null, then it uses the id path to obtain the path.
     * @return string
     */
    public function getUrlPath(?string $idPath=null):string
    {
        $idPath=$idPath??$this->currentPath;
        if(!isset($this->path[$idPath])) {
            throw new RuntimeException("Path $idPath not defined");
        }
        $patternItems=$this->path[$idPath];
        $url=$this->base.'/'.$this->pathName[$idPath];
        $final=[];
        foreach($patternItems as $vArr) {
            [$idx,$def]=$vArr;
            $value=$this->{$idx}??$def;
            $final[]=$value;
        }
        $url.=implode('/',$final);
        return rtrim($url,'/');
    }

    /**
     * It returns the current type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * It returns the current name of the module
     *
     * @return string
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * @param string     $key
     * @param null|mixed $valueIfNotFound
     *
     * @return mixed
     */
    public function getQuery(string $key, $valueIfNotFound = null)
    {
        return $this->queries[$key] ?? $valueIfNotFound;
    }

    /**
     * It gets the current header (if any)
     *
     * @param string     $key The key to read
     * @param null|mixed $valueIfNotFound
     * @return mixed|null
     */
    public function getHeader(string $key, $valueIfNotFound = null)
    {
        $keyname = 'HTTP_' . strtoupper($key);
        return $_SERVER[$keyname] ?? $valueIfNotFound;
    }

    /**
     * It gets the Post value if not the Get value
     *
     * @param string     $key The key to read
     * @param null|mixed $valueIfNotFound
     * @return mixed|null
     */
    public function getRequest(string $key, $valueIfNotFound = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $valueIfNotFound;
    }

    /**
     * It gets the Post value or returns the default value if not found
     *
     * @param string     $key The key to read
     * @param null|mixed $valueIfNotFound
     * @return mixed|null
     */
    public function getPost(string $key, $valueIfNotFound = null)
    {
        return $_POST[$key] ?? $valueIfNotFound;
    }

    /**
     * It gets the Get (url parameter) value or returns the default value if not found
     *
     * @param string     $key The key to read
     * @param null|mixed $valueIfNotFound
     * @return mixed|null
     */
    public function getGet(string $key, $valueIfNotFound = null)
    {
        return $_GET[$key] ?? $valueIfNotFound;
    }

    /**
     * It gets the body of a request.
     *
     * @param bool $jsonDeserialize if true then it de-serialize the values.
     * @param bool $asAssociative   if true (default value) then it returns as an associative array.
     * @return false|mixed|string
     */
    public function getBody(bool $jsonDeserialize = false, bool $asAssociative = true)
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
    public function setQuery(string $key, $value): void
    {
        $this->queries[$key] = $value;
    }

    /**
     * It returns the current name of the controller.
     *
     * @return string|null
     */
    public function getController(): ?string
    {
        return $this->controller;
    }

    /**
     *
     * @param  $controller
     *
     * @return RouteOne
     */
    public function setController($controller): RouteOne
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
     * @return string|null
     */
    public function getAction(): ?string
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
    public function setAction($action): RouteOne
    {
        $this->action = $action;
        return $this;
    }


    /**
     *
     *
     * @return string
     */
    public function getId(): string
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
    public function setId($id): RouteOne
    {
        $this->id = $id;
        return $this;
    }

    /**
     *
     *
     * @return string
     */
    public function getEvent(): string
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
    public function setEvent($event): RouteOne
    {
        $this->event = $event;
        return $this;
    }

    /**
     *
     *
     * @return string|null
     */
    public function getIdparent(): ?string
    {
        return $this->idparent;
    }

    /**
     * @param  $idParent
     *
     * @return RouteOne
     */
    public function setIdParent($idParent): RouteOne
    {
        $this->idparent = $idParent;
        return $this;
    }

    /**
     *
     *
     * @return string
     */
    public function getExtra(): string
    {
        return $this->extra;
    }

    /**
     * @param string $extra
     *
     * @return RouteOne
     */
    public function setExtra(string $extra): RouteOne
    {
        $this->extra = $extra;
        return $this;
    }

    /**
     * It gets the current category
     *
     * @return string|null
     */
    public function getCategory(): ?string
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
    public function setCategory(string $category): RouteOne
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
    public function getSubcategory(): string
    {
        return $this->subcategory;
    }

    /**
     * It gets the current sub-sub-category
     *
     * @return string
     */
    public function getSubsubcategory(): string
    {
        return $this->subsubcategory;
    }

    /**
     * Returns true if the current web method is POST.
     *
     * @return bool
     */
    public function isPostBack(): bool
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
    public function setIsPostBack(bool $isPostBack): RouteOne
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
    public function setModuleList($moduleList): RouteOne
    {
        $this->moduleList = $moduleList;
        return $this;
    }

    /**
     * It gets the current strategy of module.
     *
     * @return string=['none','modulefront','nomodulefront'][$i]
     * @see RouteOne::setModuleStrategy
     */
    public function getModuleStrategy(): string
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
     * If it doesn't use module then it is <b>front</b></li>
     * </ul>
     * @param string $moduleStrategy
     *
     * @return RouteOne
     */
    public function setModuleStrategy(string $moduleStrategy): RouteOne
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
        $urlFetchedOriginal = $_GET[$this->argumentName] ?? null; // controller/action/id/..
        if($urlFetchedOriginal!==null) {
            $urlFetchedOriginal=rtrim($urlFetchedOriginal,'/');
        }
        unset($_GET[$this->argumentName]);
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
    private function getExtracted(string $urlFetched, bool $sanitize = false): array
    {
        if ($sanitize) {
            $urlFetched = filter_var($urlFetched, FILTER_SANITIZE_URL);
        }
        if (is_array($this->identify) && $this->type === '') {
            foreach ($this->identify as $ty => $path) {
                if ($path === '') {
                    $this->type = $ty;
                    break;
                }
                if (strpos($urlFetched ?? '', $path) === 0) {
                    $urlFetched = ltrim($this->str_replace_ex($path, '', $urlFetched, 1), '/');
                    $this->type = $ty;
                    break;
                }
            }
        }
        return explode('/', $urlFetched);
    }
    public function redirect(string $url,int $statusCode = 303): void
    {
        header('Location: ' . $url, true, $statusCode);
        if(http_response_code()) {
            die(1);
        }
    }
    //<editor-fold desc="cli">

    /**
     * @param $filename
     * @return false|string
     */
    protected function openTemplate($filename)
    {
        $template = @file_get_contents($filename);
        if ($template === false) {
            throw new RuntimeException("Unable to read template file $filename");
        }
        // we delete and replace the first line.
        return substr($template, strpos($template, "\n") + 1);
    }

    /**
     * @param string $key the name of the flag to read
     * @param string|null $default is the default value is the parameter is set
     *                             without value.
     * @param bool        $set     it is the value returned when the argument is set but there is no value assigned
     * @return string
     */
    public static function getParameterCli(string $key, ?string $default = '', bool $set = true)
    {
        global $argv;
        $p = array_search('-' . $key, $argv, true);
        if ($p === false) {
            return $default;
        }
        if (isset($argv[$p + 1])) {
            return self::removeTrailSlash($argv[$p + 1]);
        }
        return $set;
    }

    public static function isAbsolutePath($path): bool
    {
        if (!$path) {
            return true;
        }
        if (DIRECTORY_SEPARATOR === '/') {
            // linux and macos
            return $path[0] === '/';
        }
        return $path[1] === ':';
    }

    protected static function removeTrailSlash($txt): string
    {
        return rtrim($txt, '/\\');
    }

    protected function cliInit($param = 'route.php'): void
    {
        if ($param === true) {
            $param = 'route.php';
        }
        echo "Creating .htaccess file: ";
        $content= $this->openTemplate( __DIR__ . '/templates/htaccess_template.php');
        $content=str_replace('route.php',$param,$content);
        $this->validateWriteFile('.htaccess',$content);
        echo "Creating PHP file: ";
        $content= $this->openTemplate( __DIR__ . '/templates/route_template.php');
        $this->validateWriteFile($param,$content);

    }
    protected function validateWriteFile(string $file,string $content):bool {
        $fail=false;
        $exists=@file_exists(getcwd().'/'.$file);
        if($exists) {
            echo self::colorLog(getcwd().'/'."$file file exists, skipping\n",'w');
            $fail=true;
        } else {
            $result = @file_put_contents(getcwd().'/'.$file, $content);
            if (!$result) {
                echo self::colorLog("Unable to write ".getcwd().'/'."$file file\n", 'e');
                $fail=true;
            } else {
                echo self::colorLog("OK\n", 's');
            }
        }
        return $fail;
    }

    /**
     * @param string $str
     * @param string $type =['i','e','s','w'][$i]
     * @return string
     */
    protected static function colorLog(string $str, string $type = 'i'): string
    {
        switch ($type) {
            case 'e': //error
                return "\033[31m$str\033[0m";
            case 's': //success
                return "\033[32m$str\033[0m";
            case 'w': //warning
                return "\033[33m$str\033[0m";
            case 'i': //info
                return "\033[36m$str\033[0m";
            case 'b':
                return "\e[01m$str\e[22m";
            default:
                return $str;
        }
    }

    public function cliEngine(): void
    {
        $check = self::getParameterCli('init');
        echo "  _____             _        ____             \n";
        echo " |  __ \           | |      / __ \            \n";
        echo " | |__) |___  _   _| |_ ___| |  | |_ __   ___ \n";
        echo " |  _  // _ \| | | | __/ _ \ |  | | '_ \ / _ \\\n";
        echo " | | \ \ (_) | |_| | ||  __/ |__| | | | |  __/\n";
        echo " |_|  \_\___/ \__,_|\__\___|\____/|_| |_|\___| " . self::VERSION . "\n\n";
        echo "\n";
        $done = false;
        if ($check) {
            $done = true;
            $this->cliInit($check);
        }
        if (!$done) {
            echo " Syntax:\n";
            echo " " . self::colorLog("-init", "b") . " <route.php>(optional) generates the initial placeholder.\n";
            echo "    Default value: 'routeonecli -init route.php'\n";
        }
    }
    //</editor-fold>


}
