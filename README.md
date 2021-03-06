# RouteOne
It reads the URL route and parses the values of path, so it could be interpreted manually or automatically in the fastest way possible (for example, to implement a MVC system).  

[![Packagist](https://img.shields.io/packagist/v/eftec/routeone.svg)](https://packagist.org/packages/eftec/routeone)
[![Total Downloads](https://poser.pugx.org/eftec/routeone/downloads)](https://packagist.org/packages/eftec/routeone)
[![Maintenance](https://img.shields.io/maintenance/yes/2021.svg)]()
[![composer](https://img.shields.io/badge/composer-%3E1.6-blue.svg)]()
[![php](https://img.shields.io/badge/php-%3E5.6-green.svg)]()
[![coverage](https://img.shields.io/badge/coverage-80%25-green)]()
[![compatible](https://img.shields.io/badge/compatible-linux%7Cwindows%7Cmacos-green)]()

Unlikely other libraries, this library does not have dependencies and it is contained in a single class, so it is compatible with any PHP project, for example Wordpress, Laravel, Drupal, a custom PHP project, etc.

This library is based in **CoC Convention over Configuration**. It reduces the boilerplate but it has fixed  functionalities.  This library does not allow to use custom "routes" but it covers practically all cases, so it increases the performance and usability while it sacrifices flexibility.

## Table of contents

- [RouteOne](#routeone)
  - [Example:](#example)
  - [What it does?](#what-it-does)
  - [Getting started](#getting-started)
  - [Routes](#routes)
  - [Methods](#methods)
  - [fields](#fields)
  - [Changelog](#changelog)


## Example:

Let's say we have the next URL http://somedomain.dom/Customer/Update/2 This library converts this URL into:

```php
use eftec\routeone\RouteOne;
$route=new RouteOne('http://somedomain.dom',null,false,true); // base url, type of route (null default), has module (false), fetch values (true)
echo "our route is:";
echo $route->controller; // Customer
echo $route->action; // Update
echo $route->id; // 2
    
// It could also calls a method of a class automatically
$this->callObjectEx('cocacola\controller\{controller}Controller'); // calling the method "UpdateAction" from the class cocacola\controller\CustomerController

// it is our class
class CustomerController {
    public function indexAction($id= '',$idparent= '',$event= '') {
        // calling the method
    }
}
    
```



## What it does?

Let's say we do the next operation:

An user calls the next website http://somedomain.com/Customer/Insert, he wants want to show a form to insert a customer

```php
use \eftec\routeone\RouteOne;
$route=new RouteOne('.',null,null); // Create the RouteOneClass
$route->fetch(); // fetch all the input values (from the route, get, post and such).
$route->callObject('somenamespace\\controller\\%sController'); // where it will call the  class CustomerController* 
```

or

```php
use eftec\routeone\RouteOne;
$route=new RouteOne('.',null,null); // Create the RouteOneClass
$route->fetch(); // fetch all the input values (from the route, get, post and such).
$route->callObjectEx('somenamespace\\controller\\{controller}Controller'); // where it will call the  class CustomerController* 
```


This code calls to the method **InsertActionGet** (GET), **InsertActionPost** (POST) or **InsertAction** (GET/POST)
inside the class **Customer**

The method called is written as follow:

```php
class Customer {
    public function insertAction($id="",$idparent="",$event="") {
        // here we do our operation.
    }
}
```

### What is **$id**, **$idparent** and **$event**?

### **id**

Let's se we want to **Update** a **Customer** number **20**, then we could call the next page

> http://somedomain.com/Customer/Update/20 

where 20 is the "$id" of the customer to edit (it could be a number of a string)

### **idparent**

And what if we want to **Update** a **Customer** number **20** of the business **APPL**

> http://somedomain.com/Customer/Update/20/APPL

Where APPL is the **idparent** 

### **event**

Now, let's say we click on some button or we do some action.  It could be captured by the field **_event** and it is read by the argument **$event**. This variable could be send via GET or POST.

> http://somedomain.com/Customer/Update/20/APPL?_event=click

### **Module**

Now, let's say our system is modular, and we have several customers (interna customers, external, etc.)

```php

$route=new RouteOne('.',null,true); // true indicates it is modular 
$route->fetch(); 
$route->callObject('somenamespace\\%2s%\\controller\\%1sController');
```

> http://somedomain.com/Internal/Customer/Update/20/APPL?_event=click

Then, the first ramification is the name of the module (**Internal**) and it calls the class **somenamespace\Internal\controller\CustomerController**


## Getting started

### 1) Create a .htaccess file in the folder root (Apache)

```
<IfModule mod_rewrite.c>
Options +FollowSymLinks
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ router.php?req=$1 [L,QSA]

</IfModule>
```

> If your web host doesn't allow the FollowSymlinks option, try replacing it with Options +SymLinksIfOwnerMatch.   


> The important line is:    
> RewriteRule ^(.*)$ router.php?req=$1 [L,QSA]   # The router to call.

### Or configure nginx.conf (Nginx) Linux

```
server {
    listen 80;
    server_name localhost;
    root /example.com/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.html index.htm index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /router.php?req=$document_uri&$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

```

> The important line is:  
> try_files $uri $uri/ /router.php?req=$document_uri&$query_string;

### Or configure nginx.conf (Nginx) Windows

```
server {
    listen 80;
    server_name localhost;
    root c:/www;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.html index.htm index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /router.php?req=$document_uri&$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass   127.0.0.1:9000;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

```
> The important line is:  
> try_files $uri $uri/ /router.php?req=$document_uri&$query_string;


where **router.php** is the file that it will work as router.  ?req=$1 is important because the system will read the route from "req"

```php 
// router.php
$route=new RouteOne(); // Create the RouteOneClass
$route->fetch(); // fetch all the input values (from the route, get, post and such).
$route->callObject('somenamespace\\controller\\%sController'); // where it will call the  class \somenamespace\controller\CustomerController  
```

## Routes

### API route

> https://localhost/api/controller/{action}/{id}/{idparent}

where 
* **https://localhost** is the base (it could be changed on the constructor)
* **api** indicates we are calling an "api". This value could be changed via **$this->setIdentifyType()**
* **Controller**. It's the controller class to call. 
* **Action**. It's the action (method) to call
* **id**. Some unique identifier.
* **idparent**.  Some unique identifier (of the parent of object)

```php 
// router.php https://locahost/api/Customer/Get/1
$route=new RouteOne(); // Create the RouteOneClass
$route->fetch(); // fetch all the input values (from the route, get, post and such).
if ($route->getType()=='api') {
   var_dump($route->getController()); // Customer
   var_dump($route->getAction()); // Get
   var_dump($route->getId()); // 1
   var_dump($route->getIdparent()); // null
   $route->callFile("api/%s.php",true); // we call the file Customer.php   
} 
```

### WS route

WS is an alternative to API. We could use API/WS or both.  The difference is how is it called (/api/ versus /ws/)

> https://localhost/ws/controller/{action}/{id}/{idparent}

where 
* **https://localhost** is the base (it could be changed on the constructor)
* **ws** indicates we are calling an "ws". This value could be changed via **$this->setIdentifyType()**
* **Controller**. It's the controller class to call. 
* **Action**. It's the action (method) to call
* **id**. Some unique identifier.
* **idparent**.  Some unique identifier (of the parent of object)

```php 
// router.php https://locahost/ws/Customer/Get/1
$route=new RouteOne(); // Create the RouteOne Class
$route->fetch(); // fetch all the input values (from the route, get, post and such).
if ($route->getType()=='ws') {
   var_dump($route->getController()); // Customer
   var_dump($route->getAction()); // Get
   var_dump($route->getId()); // 1
   var_dump($route->getIdparent()); // null
   $route->callFile("ws/%s.php",true); // we call the file Customer.php   
} 
```

### Controller route

Unlikely "api" and "ws" route, the controller route doesn't have a prefix in the route.

> https://localhost/controller/{action}/{id}/{idparent}

where 
* **https://localhost** is the base (it could be changed on the constructor)
* **Controller**. It's the controller class to call. 
* **Action**. It's the action (method) to call
* **id**. Some unique identifier.
* **idparent**.  Some unique identifier (of the parent of object)

router.php:
```php 
// router.php https://locahost/Customer/Get/1
$route=new RouteOne(); // Create the RouteOne Class
$route->fetch(); // fetch all the input values (from the route, get, post and such).
if ($route->getType()=='controller') {
   var_dump($route->getController()); // Customer
   var_dump($route->getAction()); // Get
   var_dump($route->getId()); // 1
   var_dump($route->getIdparent()); // null
   $route->callObject('\\somenamespace\\controller\\%sController'); // we call CustomerController class and we call the method "getAction" / "getActionGet" or "getActionPost"
} 
```

file CustomerController.php:
```php 
namespace somenamespace\controller;
class CustomerController {
    // any action GET or POST
    public function GetAction($id="",$idparent="",$event="") {
        // **my code goes here.**
        // $event (optional) is read from REQUEST or POST
    }
    // GET only action (optional)
    public function GetActionGet($id="",$idparent="",$event="") {
        // **my code goes here.**
    }    
    // POST only action (optional)
    public function GetActionPOST($id="",$idparent="",$event="") {
        // **my code goes here.**
    }        
}

```

### FRONT route

The front route (for the front-end) is different than other routes. Syntactically it is distributed on category, subcategory and sub-subcategory. 

> This route is not identified automatically so it must be set in the constructor

> https://localhost/category/{subcategory}/{subsubcategory}/{id}

where 
* **https://localhost** is the base (it could be changed on the constructor)
* **category** The category that we are calling.
* **subcategory**. (optional) The subcategory
* **subsubcategory**. (optional) The sub-subcategory
* **id**. Some unique identifier. (**id** is always the last element of the chain, so /category/20, category/subc/20 and /category/subc/subc/20 always returns 20).

Example: (isModule=false)

> http://localhost/Toys/GoodSmileCompany/Nendoroid/Thanos

* **Category** = toys
* **Subcategory** = GoodSmileCompany
* **Subsubcategory** = Nendoroid
* **id** = Thanos

Example: (isModule=true)

> http://localhost/Retail/Toys/GoodSmileCompany/Nendoroid/Thanos

* **Module** = Retail
* **Category** = toys
* **Subcategory** = GoodSmileCompany
* **Subsubcategory** = Nendoroid
* **id** = Thanos (**id** is always the last element)
* **idparent** = (it does not work on frontal)

Example: (isModule=false)

> http://localhost/Toys/GoodSmileCompany/Thanos

* **Category** = toys
* **Subcategory** = GoodSmileCompany
* **Subsubcategory** = Thanos
* **id** = Thanos



```php 
// router.php https://locahost/Products/New/123
$route=new RouteOne('.','front'); // Create the RouteOne Class for the front end.  It is required to indicate the type as "front". Otherwise it will be interpreted as a "controller route".
$route->fetch(); // fetch all the input values.
if ($route->getType()=='front') {
   var_dump($route->getCategory()); // Products
   var_dump($route->getSubCategory()); // New
   var_dump($route->getSubSubCategory()); // null
   var_dump($route->getId()); // 123  
} 
```



## Methods

### __construct($base='', $forcedType=null, $isModule=false)

* string $base base url
* string $forcedType=['api','ws','controller','front'][$i]<br>
    <b>api</b> then it expects a path as api/controller/action/id/idparent<br>
    <b>ws</b> then it expects a path as ws/controller/action/id/idparent<br>
    <b>controller</b> then it expects a path as controller/action/id/idparent<br>
    <b>front</b> then it expects a path as /category/subcategory/subsubcategory/id<br>
* bool   $isModule if true then the route start reading a module name<br>
    <b>false</b> controller/action/id/idparent<br>
    <b>true</b> module/controller/action/id/idparent<br>       

### getQuery($key,$valueIfNotFound=null)

It gets a query value (URL).

>Note: This query does not include the values "req","_event" and "_extra"

Example:

```php 
// http://localhost/..../?id=hi
$id=$router->getQuery("id"); // hi
$nf=$router->getQuery("something","not found"); // not found
```

### setQuery($key,$value)

It sets a query value

Example:

```php 
$route->setQuery("id","hi");
$id=$router->getQuery("id"); // hi
```


### fetch()

Fetch the values from the route, and the values are processed.

### callObject($classStructure='%sController',$throwOnError=true)

Call a method inside an object using the current route.

* **$classStructure**  

     * The first %s (or %1s) is the name of the controller.<br>
     * The second %s (or %2s) is the name of the module (if any and if ->isModule=true)<br>
     * Example: namespace/%sClass if the controller=Example then it calls namespace/ExampleClass<br>
     * Example: namespace/%2s/%1sClass it calls namespace/Module/ExampleClass<br>
* **throwOnError** if true and it fails then it throws an error. If false then it only returns the error message.

The name of the method is obtained via the current **action**

1) **{nameaction}Action** exists then it's called.
2) Otherwise, if $istpostback=false then it calls the method **{nameaction}ActionGet**
3) Otherwise, if $istpostback=true then it calls the method **{nameaction}ActionPost**

### callObjectEx($classStructure, $throwOnError, $method, $methodGet, $methodPost,$arguments

It creates an new instance of an object (for example, a Controller object) and calls the method.<br>
Note: It is an advanced version of this::callObject()<br>
This method uses {} to replace values based in the next variables:<br>

| Tag              | Description                                        |
|------------------|----------------------------------------------------|
| {controller}     | The name of the controller                         |
| {action}         | The current action                                 |
| {event}          | The current event                                  |
| {type}           | The current type of path (ws,controller,front,api) |
| {module}         | The current module (if module is active)           |
| {id}             | The current id                                     |
| {idparent}       | The current idparent                               |
| {category}       | The current category                               |
| {subcategory}    | The current subcategory                            |
| {subsubcategory} | The current subsubcategory                         |


<b>Example:</b> 

```php
// controller example http://somedomain/Customer/Insert/23
$this->callObjectEx('cocacola\controller\{controller}Controller');
// it calls the method cocacola\controller\Customer::InsertAction(23,'','');

// front example: http://somedomain/product/coffee/nescafe/1
$this->callObjectEx('cocacola\controller\{category}Controller' // the class to call
        ,false // if error then it throw an error
        ,'{subcategory}' // the method to call (get or post)
        ,null // the method to call (method get)
        ,null // the method to call (method post)
        ,['subsubcategory','id']); // the arguments to call the method
// it calls the method cocacola\controller\product::coffee('nescafe','1');
```

Call a method inside an object using the current route.

### callFile($fileStructure='%s.php',$throwOnError=true)

It calls (include) a php file using the current name of the controller

* **$fileStructure** The current name of the controller. "%s" is the name of the current controller. Example :/Customer/Insert -> calls the file Customer.php
* **throwOnError** if true then it throws an error. If false then it only returns the error message.


### getCurrentUrl($withoutFilename = true)

Returns the current base url without traling space, paremters or queries

> <b>Note</b>: this function relies on $_SERVER['SERVER_NAME'] and  it could be modified by the end-user

### getCurrentServer()

It returns the current server without trailing slash.

```php 
$route->getCurrentServer(); // http://somedomain
```

### setCurrentServer($serverName)

It sets the current server name.  It is used by getCurrentUrl() and getCurrentServer().    
**Note:** If $this->setCurrentServer() is not set, then it uses $_SERVER['SERVER_NAME'] and it could be modified
 by the user.

```php 
$route->setCurrentServer('localhost'); 
$route->setCurrentServer('127.0.0.1'); 
$route->setCurrentServer('domain.dom'); 
```

### getUrl($extraQuery = '',$includeQuery=false)

It gets the (full) url based in the information in the class.

```php 
$route->getUrl(); // http://somedomain/controller/action/id
$route->getUrl('id=20'); // http://somedomain/controller/action/id?id=20
$route->getUrl('id=20',true); // http://somedomain/controller/action/id?id=20&field=20&field2=40
```

### url($module,$controller,$action,$id,$idparent)

It builds an url based in custom values

```php 
$route->url(null,"Customer","Update",20); // Customer/Update/20
```

### urlFront($module,$category,$subcategory,$subsubcategory,$id)

It builds an url (front) based in custom values

```php 
$route->url(null,"Daily","Milk",20); // Daily/Milk/20
```

### alwaysWWW($https = false) 

If the subdomain is empty or different to www, then it redirect to www.domain.com.<br>
<b>Note: It doesn't work with localhost, domain without TLD (netbios) or ip domains. It is on purpose.</b><br>
<b>Note: If this code needs to redirect, then it stops the execution of the code. Usually it must be called at the 
top of the code</b>

```php 
$route->alwaysWWW();  // if the domain is somedomain.dom/url, then it redirects to www.somedomain.dom/url
$route->alwaysWWW(true);  // if the domain is http: somedomain.dom/url, then it redirects to https: www.somedomain.dom/url

```

### alwaysHTTPS() 

If the page is loaded as http, then it redirects to https.    
<b>Note: It doesn't work with localhost, domain without TLD (netbios) or ip domains. It is on purpose.</b><br>
<b>Note: If this code needs to redirect, then it stops the execution of the code. Usually it must be called at 
the top of the code</b>

```php 
$route->alwaysHTTPS(); // http://somedomain.com ---> https://somedomain.com
$route->alwaysHTTPS(); // http://localhost ---> // http://localhost
$route->alwaysHTTPS(); // http://127.0.0.1 ---> // http://127.0.0.1
$route->alwaysHTTPS(); // http://mypc ---> // http://mypc
```

### alwaysNakedDomain($https = false) 

If the subdomain is www (example www.domain.dom) then it redirect to a naked domain domain.dom<br>   
<b>Note: It doesn't work with localhost, domain without TLD (netbios) or ip domains. It is on purpose.</b><br>  
<b>Note: If this code needs to redirect, then it stops the execution of the code. Usually,
it must be called at the top of the code</b>   

```php 
$route->alwaysNakedDomain();  // if the domain is www.somedomain.dom/url, then it redirects to somedomain.dom/url
$route->alwaysNakedDomain(true);  // if the domain is http: www.somedomain.dom/url, then it redirects to https: somedomain.dom/url

```

## fields

| Field           | Description                                                  | Example                                                      |
| --------------- | ------------------------------------------------------------ | ------------------------------------------------------------ |
| $base           | It is the base url.                                          | $this->base=0;                                               |
| $type           | It is the type of url (api,ws,controller or front)           | echo $this->type; // api                                     |
| $module         | It's the current module                                      | echo $this->module;                                          |
| $controller     | It's the controller.                                         | echo $this->controller;                                      |
| $action         | It's the action.                                             | echo $this->action;                                          |
| $id             | It's the identifier                                          | echo $this->id;                                              |
| $event          | It's the event (such as "click on button).                   | echo$this->event;                                            |
| $idparent       | It is the current parent id (if any)                         | echo $this->idparent;                                        |
| $extra          | It's the event (such as "click on button)                    | echo $this->extra;                                           |
| $category       | The current category. It is useful for the type 'front'      | echo $this->category;                                        |
| $subcategory    | The current sub-category. It is useful for the type 'front'  | echo $this->subcategory;                                     |
| $subsubcategory | The current sub-sub-category. It is useful for the type  'front' | echo $this->subsubcategory;                                  |
| $identify       | It is an associative array that helps to identify the api and  ws route. | $this->identify=['api'=>'apiurl','ws'=>'webservices','controller'=>'']; |
| $isPostBack     | its true if the page is POST, otherwise false.                                | if ($this->isPostBack) { ... };                             |
| $verb           | The current verb, it could be GET,POST,PUT and DELETE.       | if ($this->verb) { ... };                             |

## Whitelist

| Field          | Description                                                  | Example                                                      |
| -------------- | ------------------------------------------------------------ | ------------------------------------------------------------ |
| $allowedVerbs  | A list with allowed verbs                                    | $this->allowedVerbs=['GET', 'POST', 'PUT', 'DELETE'];        |
| $allowedFields | A list with allowed fields used by **callObjectEx()**        | $this->allowedFields=['controller', 'action', 'verb', 'event', 'type', 'module', 'id'<br />, 'idparent','category', 'subcategory', 'subsubcategory']; |
| setWhitelist() | It sets an associative array with the whitelist to **controller**, **action**, **category**, **subcategory**, **subsubcategory** and **module**. <br />If not set (null default value), then it allows any entry.<br />Currently it only work with **controller** and **category** | $this->setWhitelist('controller','Purchase','Invoice','Customer');<br />$this->setWhitelist('controller',null) // allows any controller; |

### Whitelist input.
Whitelisting a method allows two operations:

* To whitelist an input, for example, only allowing "controllers" that they are inside a list.
* Also, it allows to define the case of an element.

For example:

```php
// Example, value not in the whitelist: someweb.dom/customer/list
$this->setWhiteList('controller',['Product','Client']);
$this->fetch();
var_dump($this->controller); // null or the default value
var_dump($this->notAllowed); // true (whitelist error)


// Example, value in the whitelist but with the wrong case: someweb.dom/customer/list
$this->setWhiteList('controller',['Customer']);
$this->fetch();
var_dump($this-controller) // it shows "Customer" instead of "customer"
var_dump($this->notAllowed); // false (not error with the validation of the whitelist)    
    
// reset whitelist for controllers
$this->setWhiteList('controller',null);    
    

```



### $type 

it returns the current type of URL used.

> Also obtained via getType()

|type|url expected|description|
|----|------------|------------|
| api |domain.dom/api/controller/action/id | {module}\api\controller\action\id\{idparent}?_event=event    |
| ws |domain.dom/ws/controller/action/id | {module}\ws\controller\action\id\{idparent}?_event=event     |
| controller |domain.dom/controller/action/id | {module}\controller\action\id\{idparent}?_event=event     |
| front |domain.dom/cat/subcat/subsubcat/id | {module}\category\subcategory\subsubcategory\id?_event=event    |

Example:

```php 
$route=new RouteOne('.',null,false);  // null means automatic type
$route->fetch(); 
if($route->type==='api') {
    $route->callObject('somenamespace\\api\\%sApi');
} else {
    $route->callObject('somenamespace\\controller\\%sController');
}
```

Example:

```php 
$route=new RouteOne('.',null,false);  // null means automatic type
$route->fetch(); 

$route->callObject('somenamespace\\%3s%\\%sController'); // somespace/api/UserController , somespace/controller/UserController, etc.
```

## Changelog

* 2021-02-26 1.19
   * **setWhiteList()** now works with **controller** and **category**
   * **setWhiteList()** also works to define the correct proper case of the elements.
   * The method **callObjectEx()** allows to define the case. 
* 2021-02-26 1.18
   * new fields $verb (it gets the current verb, example GET, POST, etc.)
   * new whitelist elements:
     * $allowedVerbs The list of allowed verbs.
     * $allowedFields The list of allowed fields used by **callObjectEx()**
     * $allowedControllers The list of allowed controllers. If this list is set and the controller is not in the whitelist
       , then the controller is set as null
   * The method **callObjectEx()** allows to use the verb. The verb is always ucfirst.
     * Example $this->callObjectEx('cocacola\controller\{controller}Controller','{action}Action{verb}');
* 2021-02-16 1.17
   * removed all @ and replaced by **isset()**. Since this library is compatible with PHP 5.6, then it doesn't use "??" 
     operators.
   * **setDefaultValues()** trigger an error if it is called after fetch()
* 2021-02.11 1.16.1
    * fixed a problem with "api" and "ws" that it doesn't read the controller in the right position.  
* 2021-02-11 1.16
    * Removed Travis.
    * Lowered the requirement. Now, this library works in PHP 5.6 and higher (instead of PHP 7.0 and higher)
    * Constructor has a new argument, it could fetch() the values
    * alwaysHTTPS() has a new argument that it could returns the full URL (if it requires redirect) or null
    * alwaysWWW() has a new argument that it could returns the full URL (if it requires redirect) or null
    * alwaysNakedDomain() has a new argument that it could returns the full URL (if it requires redirect) or null
* 2020-06-14 1.15
    * Added default values in setDefaultValues().     
    * Method fetch() now it unset the value.    
    * Fixed Method url().  
* 2020-06-07 1.14.2
    * Bug fixed: Delete an echo (used for debug)
* 2020-06-07 1.14.1
    * Solved a small bug. it keeps the compatibility.   
* 2020-06-07 1.14
    * added defcategory,defsubcategory and defsubsubcategory
    * new method setIdentifyType()
* 2020-04-23 1.13
    * Lots of cleanups. 
* 2020-04-04 1.12 
    * added support for nginx.
    * updated the documentation for .htaccess
    * new method setCurrentServer()
* 2020-03-27 1.11
    * added alwaysNakedDomain()
* 2020-03-27 1.10.1
    * a small fix for alwaysHTTPS() 
* 2020-03-27 1.10
    * added method alwaysHTTPS() and alwaysWWW()
* 2020-02-15 1.9
    * added new arguments to callObject()
    * new method callObjectEx()
* 2020-02-03 1.8
    * new method getNonRouteUrl()
    * new method setExtra()
    * new method isPostBack()
    * new method setIsPostBack()
    * Some fixes for getUrl() 