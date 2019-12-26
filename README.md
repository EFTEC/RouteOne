# RouteOne
Route for PHP

It reads the url route and parses the values, so it could be interpreted manually or automatically.

[![Build Status](https://travis-ci.org/EFTEC/RouteOne.svg?branch=master)](https://travis-ci.org/EFTEC/RouteOne)
[![Packagist](https://img.shields.io/packagist/v/eftec/routeone.svg)](https://packagist.org/packages/eftec/routeone)
[![Total Downloads](https://poser.pugx.org/eftec/routeone/downloads)](https://packagist.org/packages/eftec/routeone)
[![Maintenance](https://img.shields.io/maintenance/yes/2019.svg)]()
[![composer](https://img.shields.io/badge/composer-%3E1.6-blue.svg)]()
[![php](https://img.shields.io/badge/php-7.x-green.svg)]()
[![CocoaPods](https://img.shields.io/badge/docs-70%25-yellow.svg)]()

## Usage

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
route()->callObject('somenamespace\\controller\\%sController'); // where it will call the  class \somenamespace\controller\CustomerController  
```

## Routes

### API route

> https://localhost/api/controller/{action}/{id}/{idparent}

where 
* **https://localhost** is the base (it could be changed on the constructor)
* **api** indicates we are calling an "api". This value is fixed
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
* **ws** indicates we are calling an "ws". This value is fixed
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

The front route (for the front-end) is different than other routes. Syntactically it is distributed on category/subcategory and subsubcategory. 

> This route is not identified automatically so it must be set in the constructor

> https://localhost/category/{subcategory}/{subsubcategory}/{id}

where 
* **https://localhost** is the base (it could be changed on the constructor)
* **category** The category that we are calling.
* **subcategory**. (optional) The subcategory
* **subsubcategory**. (optional) The sub-subcategory
* **id**. Some unique identifier. (**id** is always the last element of the chain, so /category/20, category/subc/20 and /category/subc/subc/20 always returns 20).

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

### fetch()

Fetch the values from the route, and the values are processed.

### callObject($classStructure='%sController',$throwOnError=true)

Call a method inside an object using the current route.

* **$classStructure** The current name of the controller. "%s" is the name of the current controller. Example :/Customer/Insert -> calls the controller CustomerController and the method InsertAction
* **throwOnError** if true then it throws an error. If false then it only returns the error message.


The name of the method is obtained via the current **action**

1) **{nameaction}Action** exists then it's called.
2) Otherwise, if $istpostback=false then it calls the method **{nameaction}ActionGet**
3) Otherwise, if $istpostback=true then it calls the method **{nameaction}ActionPost**

### callFile($fileStructure='%s.php',$throwOnError=true)

It calls (include) a php file using the current name of the controller

* **$fileStructure** The current name of the controller. "%s" is the name of the current controller. Example :/Customer/Insert -> calls the file Customer.php
* **throwOnError** if true then it throws an error. If false then it only returns the error message.

### $isPostBack (field)

if true then the form is called as POST (i.e. a submit button).


