# RouteOne
Route for PHP

It reads the url route and parses the values, so it could be interpreted manually or automatically.

Unlikely other libraries, this library does not have dependencies and it is contained in a single class.  

This library is based in **CoC Convention over Configuration**. It reduces the boilerplate but it fixes the functionality.  In this case, this library does not allow to change the "route" but it covers practically all cases, so it increases the performance and usability while it sacrifices flexibility.


[![Build Status](https://travis-ci.org/EFTEC/RouteOne.svg?branch=master)](https://travis-ci.org/EFTEC/RouteOne)
[![Packagist](https://img.shields.io/packagist/v/eftec/routeone.svg)](https://packagist.org/packages/eftec/routeone)
[![Total Downloads](https://poser.pugx.org/eftec/routeone/downloads)](https://packagist.org/packages/eftec/routeone)
[![Maintenance](https://img.shields.io/maintenance/yes/2020.svg)]()
[![composer](https://img.shields.io/badge/composer-%3E1.6-blue.svg)]()
[![php](https://img.shields.io/badge/php-7.x-green.svg)]()
[![CocoaPods](https://img.shields.io/badge/docs-70%25-yellow.svg)]()

## What it does?

Let's say we do the next operation:

An user calls the next website http://somedomain.com/Customer/Insert, he wants want to show a form to insert a customer

```php
$route=new RouteOne('.',null,null); // Create the RouteOneClass
$route->fetch(); // fetch all the input values (from the route, get, post and such).
$route->callObject('somenamespace\\controller\\%sController'); // where it will call the  class CustomerController* 
```

or

```php
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

Now, let's say our system is modular and we have several customers (interna customers, external, etc.)

```php
$route=new RouteOne('.',null,true); // true indicates it is modular 
$route->fetch(); 
$route->callObject('somenamespace\\%2s%\\controller\\%1sController');
```

> http://somedomain.com/Internal/Customer/Update/20/APPL?_event=click

Then, the first ramification is the name of the module (**Internal**) and it calls the class **somenamespace\Internal\controller\CustomerController**


## Getting started

1) Create a .htaccess file

```
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_URI} !-f
RewriteCond %{REQUEST_URI} !-d
RewriteCond %{REQUEST_URI} !-L
# l = last
RewriteRule ^(example|test|css|vendors|vendor|js|img|upload)($|/) - [L]
RewriteRule ^(.*)$ router.php?req=$1 [L,QSA]

</IfModule>
```

where test1.php is the file that it will work as router.  ?req=$1 is important because the system will read the route from "req"

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
* **api** indicates we are calling an "api". This value could be changed via **$this->setPath()**
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
* **ws** indicates we are calling an "ws". This value could be changed via **$this->setPath()**
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

The front route (for the front-end) is different than other routes. Syntactically it is distributed on category, subcategory and subsubcategory. 

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

It creates and object (for example, a Controller object) and calls the method.<br>
Note: It is an advanced version of this::callObject()<br>
This method uses {} to replace values.<br>
<ul>
<li><b>{controller}</b> The name of the controller</li>
<li><b>{action}</b> The current action</li>
<li><b>{event}</b> The current event</li>
<li><b>{type}</b> The current type of path (ws,controller,front,api)</li>
<li><b>{module}</b> The current module (if module is active)</li>
<li><b>{id}</b> The current id</li>
<li><b>{idparent}</b> The current idparent</li>
<li><b>{category}</b> The current category</li>
<li><b>{subcategory}</b> The current subcategory</li>
<li><b>{subsubcategory}</b> The current subsubcategory</li>
</ul>
<b>Example:</b> 

```php
// controller example http://somedomain/Customer/Insert/23
$this->callObjectEx('cocacola\controller\{controller}Controller');
// it calls the method cocacola\controller\Customer::InsertAction(23,'','');

// front example: http://somedomain/product/coffee/nescafe/1
$this->callObjectEx('cocacola\controller\{category}Controller',false,'{subcategory}',null,null,['subsubcategory','id']);
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


## fields


### $isPostBack (field)

if true then the form is called as POST (i.e. a submit button).

### $type 

it returns the current type

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

* 2020-02-15 1.9
    * added new arguments to callObject()
    * new method callObjectEx()
* 2020-02-03 1.8
    * new method getNonRouteUrl()
    * new method setExtra()
    * new method isPostBack()
    * new method setIsPostBack()
    * Some fixes for getUrl() 