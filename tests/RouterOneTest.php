<?php /** @noinspection PhpUnusedLocalVariableInspection */

/** @noinspection HttpUrlsUsage */

namespace eftec\tests;

use eftec\routeone\RouteOne;
use Exception;
use PHPUnit\Framework\TestCase;
class categoRYController {
    /** @noinspection PhpUnused */
    public function actiontestAction($id, $idparent='', $event='') {
        echo 'action test called';
    }
}

/**
 * Class RouterOneTest
 * @package eftec\tests
 * @copyright Jorge Castro Castillo
 * 2019-feb-24 5:06 PM
 */
class RouterOneTest extends TestCase
{
    /** @var RouteOne */
    public $ro;

    public function setUp()
    {
        
    }
    public function testNaked() {
        $_SERVER['HTTP_HOST']='www.example.dom';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->fetch();

        $url=$this->ro->alwaysNakedDomain(false,false);
        self::assertEquals('http://example.dom',$url);

        $_SERVER['HTTP_HOST']='www.example.dom';
        $_SERVER['HTTPS']='';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->fetch();
        $url=$this->ro->alwaysNakedDomain(true,false);
        self::assertEquals('https://example.dom',$url);
    }
    public function testMisc1() {
        $_SERVER['HTTP_HOST']='www.example.dom';
        $_SERVER['REQUEST_METHOD']='POST';
        $_GET['req']='module1/controller1/action1'; // bbb is for api (but we are forcing it the definition of it.
        $this->ro=new RouteOne('http://www.example.dom','api',['module1'],true);
        self::assertEquals(true,$this->ro->isPostBack());
        self::assertEquals('module1',$this->ro->module);
        self::assertEquals('controller1',$this->ro->controller);
        self::assertEquals('action1',$this->ro->action);
        self::assertEquals('POST',$this->ro->verb);
        unset($_SERVER['REQUEST_METHOD']);
    }
    public function testMisc2() {
        $_SERVER['HTTP_HOST']='www.example.dom';
        $_SERVER['REQUEST_METHOD']='PUT';
        $_GET['req']='module1/controller1/action1'; // bbb is for api (but we are forcing it the definition of it.
        $this->ro=new RouteOne('http://www.example.dom','api',['module1'],true);
        self::assertEquals(false,$this->ro->isPostBack());
        self::assertEquals('module1',$this->ro->module);
        self::assertEquals('controller1',$this->ro->controller);
        self::assertEquals('action1',$this->ro->action);
        self::assertEquals('PUT',$this->ro->verb);
        unset($_SERVER['REQUEST_METHOD']);
    }
    public function testAPIWS() {
        $_SERVER['HTTP_HOST']='www.example.dom';
        $_SERVER['REQUEST_METHOD']='POST';
        $_GET['_event']='event1';
        $_GET['_extra']='world';
        $_GET['req']='api/controller1/action1/id1/parent1/?_event=event1&_extra=world';
        $this->ro=new RouteOne('http://www.example.dom',null,false,true);
        self::assertEquals(true,$this->ro->isPostBack());
        self::assertEquals('api',$this->ro->type);
        self::assertEquals('controller1',$this->ro->controller);
        self::assertEquals('action1',$this->ro->action);
        self::assertEquals('id1',$this->ro->id);
        self::assertEquals('parent1',$this->ro->idparent);
        self::assertEquals('event1',$this->ro->event);
        self::assertEquals('world',$this->ro->extra);


        $_GET['req']='ws/controller1/action1/id1/parent1'; // bbb is for api (but we are forcing it the definition of it.
        $this->ro=new RouteOne('http://www.example.dom',null,false,true);
        self::assertEquals('ws',$this->ro->type);
        self::assertEquals(true,$this->ro->isPostBack());
        self::assertEquals('controller1',$this->ro->controller);
        self::assertEquals('action1',$this->ro->action);
        self::assertEquals('id1',$this->ro->id);
        self::assertEquals('parent1',$this->ro->idparent);
        unset($_SERVER['REQUEST_METHOD']);
    }
    public function testwww() {
        $_SERVER['HTTP_HOST']='example.dom';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->fetch();

        $url=$this->ro->alwaysWWW(false,false);
        self::assertEquals('http://www.example.dom',$url);

        $_SERVER['HTTP_HOST']='example.dom';
        $_SERVER['HTTPS']='';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->fetch();
        $url=$this->ro->alwaysWWW(true,false);
        self::assertEquals('https://www.example.dom',$url);

        $_SERVER['HTTP_HOST']='www.example.dom';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->fetch();

        $url=$this->ro->alwaysWWW(false,false);
        self::assertEquals(null,$url);

        $_SERVER['HTTP_HOST']='www.example.dom';
        $_SERVER['HTTPS']='';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->fetch();
        $url=$this->ro->alwaysWWW(true,false);
        self::assertEquals('https://www.example.dom',$url);
    }

    /**
     * @throws Exception
     */
    public function testNewVar()
    {
        $this->ro=new RouteOne('http://www.example.dom');
        $_GET['req']='MyController/Action2/id/parentid';
        $url=$this->ro->getCurrentUrl();
        self::assertNotEmpty($url);
        $this->ro->fetch();
        self::assertEquals('Action2', $this->ro->getAction());
        self::assertEquals(null, $this->ro->getCategory());
        self::assertEquals('id', $this->ro->getId());
        self::assertEquals('parentid', $this->ro->getIdparent());
        self::assertEquals('MyController', $this->ro->getController());
        $this->ro->callFile(__DIR__ .'/%s.php');
        
        self::assertEquals('http://www.example.dom/dummy.php',$this->ro->getNonRouteUrl('dummy.php'));
        
        //$this->ro->callObject();
    }

    /**
     * @throws Exception
     */
    public function testCall() {
        
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->setCurrentServer('www.example.dom');
        $_GET['req']='category/actiontest/id/parentid';
        $this->ro->fetch();
        $r=$this->ro->callObject('eftec\tests\%sController');
        self::assertEquals(null,$r,'no error');
        $r=$this->ro->callObjectEx('eftec\tests\{controller}Controller',true,'{action}Action',null,null
            ,['id']);
        self::assertEquals(null,$r,'no error');
        $r=$this->ro->callObjectEx('eftec\tests\{controller}ControllerX',false,'{action}Action',null,null
            ,['id']);
        self::assertEquals('Class eftec\tests\categoryControllerX doesn\'t exist',$r,'error');
        $r=$this->ro->callObjectEx('eftec\tests\{controller}Controller',false,'{action}ActionX',null,null
            ,['id']);
        self::assertEquals('Action ex [actiontestActionX or ] (GET) not found for class [eftec\tests\categoryController]'
            ,$r,'error');
        self::assertInstanceOf(RouteOne::class,$this->ro->setAction(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setController(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->reset());

    }
    /**
     * @throws Exception
     */
    public function testCallNotAllowed() {

        // it must not fail
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->setCurrentServer('www.example.dom');
        $this->ro->setWhitelist('controller',['categoRY']);
        $_GET['req']='CAtegory/actiontest/id/parentid';
        $this->ro->fetch();
        $falla=false;
        try {
            $this->ro->callObject('eftec\tests\%sController');
        } catch(Exception $ex) {
            $falla=true;
        }
        self::assertEquals(false,$falla);
        self::assertEquals('categoRY',$this->ro->controller);

        $falla=false;
        try {
            $this->ro->callObjectEx('eftec\tests\{controller}Controller');
        } catch(Exception $ex) {
            $falla=true;
        }
        self::assertEquals(false,$falla);

        // it must fails
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->setCurrentServer('www.example.dom');
        $this->ro->setWhitelist('controller',['aaa']);
        $_GET['req']='category/actiontest/id/parentid';
        $this->ro->fetch();

        $falla=false;
        try {
            $this->ro->callObject('eftec\tests\%sController');
        } catch(Exception $ex) {
            $falla=true;
        }
        self::assertEquals(true,$falla);

        $falla=false;
        try {
            $this->ro->callObjectEx('eftec\tests\{controller}Controller');
        } catch(Exception $ex) {
            $falla=true;
        }
        self::assertEquals(true,$falla);

        $this->ro->setWhiteList('controller',null);

    }
    /**
     * @throws Exception
     */
    public function testCallNotAllowedCategory() {

        // it must not fail
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->type='front';
        $this->ro->setCurrentServer('www.example.dom');
        $this->ro->setWhitelist('category',['categoRY']);
        $_GET['req']='CAtegory/actiontest/id/parentid';
        $this->ro->fetch();
        $falla=false;
        try {
            $this->ro->callObject('eftec\tests\%sController');
        } catch(Exception $ex) {
            $falla=true;
        }
        self::assertEquals(false,$falla);
        self::assertEquals('categoRY',$this->ro->category);

        $falla=false;
        $_GET['req']='CAtegory/actiontest/id/parentid';
        $this->ro->fetch();
        try {
            $this->ro->callObjectEx('eftec\tests\{category}Controller',true,'{subcategory}Action');
        } catch(Exception $ex) {

            $falla=true;
        }
        self::assertEquals(false,$falla);

        // it must fails
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->setCurrentServer('www.example.dom');
        $this->ro->setWhitelist('category',['aaa']);
        $_GET['req']='category/actiontest/id/parentid';
        $this->ro->fetch();

        $falla=false;

        self::assertEquals(false,$this->ro->notAllowed);

        $this->ro->fetch();
        $falla=false;
        try {
            $this->ro->callObjectEx('eftec\tests\{category}Controller');
        } catch(Exception $ex) {
            $falla=true;
        }
        self::assertEquals(true,$falla);

        $this->ro->setWhiteList('category',null);

    }

    public function testCallFail() {

        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->setCurrentServer('www.example.dom');
        $_GET['req']='category/actiontest/id/parentid';
        $this->ro->fetch();
        $r=$this->ro->callObject('eftec\tests\%sControllerX',false);
        self::assertEquals('Class eftec\tests\categoryControllerX doesn\'t exist',$r);


    }
    public function testNoFront()
    {
        $_GET['req']='Module/MyController/Action2/id/parentid';
        $_GET['_event']='Event';
        $_GET['_extra']='Extra';
        $this->ro=new RouteOne('http://www.example.dom',null,['Module'],false,'nomodulefront');
        $url=$this->ro->getCurrentUrl();
        self::assertNotEmpty($url);
        $this->ro->fetch();
        self::assertEquals('Action2', $this->ro->getAction());
        self::assertEquals(null, $this->ro->getCategory());
        self::assertEquals('id', $this->ro->getId());
        self::assertEquals('parentid', $this->ro->getIdparent());
        self::assertEquals('MyController', $this->ro->getController());
        self::assertEquals('Module', $this->ro->getModule());
        self::assertEquals('Event', $this->ro->getEvent());
        self::assertEquals('Extra', $this->ro->getExtra());
        self::assertEquals(false, $this->ro->isPostBack());
        self::assertInstanceOf(RouteOne::class,$this->ro->setController(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setAction(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setId(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setEvent(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setIdParent(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setExtra(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setIsPostBack(false));

        self::assertEquals('http://www.example.dom/Module////',$this->ro->getUrl('',false));

        //$this->ro->callObject();
        $_GET['req']='Module/MyController/Action2/id/parentid';
        $_GET['_event']='Event';
        $_GET['_extra']='Extra';
        $this->ro=new RouteOne('http://www.example.dom',null,['Module'],false,'modulefront');
        $url=$this->ro->getCurrentUrl();
        self::assertNotEmpty($url);
        $this->ro->fetch();
        self::assertEquals('', $this->ro->getAction());
        self::assertEquals('MyController', $this->ro->getCategory());
        self::assertEquals('Action2', $this->ro->getSubcategory());
        self::assertEquals('id', $this->ro->getSubsubcategory());
        self::assertEquals('parentid', $this->ro->getId());
        self::assertEquals(null, $this->ro->getIdparent());
        self::assertEquals('', $this->ro->getController());
        self::assertEquals('Module', $this->ro->getModule());
        self::assertEquals('Event', $this->ro->getEvent());
        self::assertEquals('Extra', $this->ro->getExtra());
        self::assertEquals(false, $this->ro->isPostBack());
        self::assertInstanceOf(RouteOne::class,$this->ro->setController(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setAction(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setId(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setEvent(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setIdParent(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setExtra(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setIsPostBack(false));

        self::assertEquals('http://www.example.dom/Module/MyController/Action2/id/',$this->ro->getUrl('',false));
    }

    public function testNewVar2()
    {
        $_GET['req']='Module/MyController/Action2/id/parentid';
        $_GET['_event']='Event';
        $_GET['_extra']='Extra';
        $this->ro=new RouteOne('http://www.example.dom','controller',['Module']);
        $url=$this->ro->getCurrentUrl();
        self::assertNotEmpty($url);
        $this->ro->fetch();
        self::assertEquals('Action2', $this->ro->getAction());
        self::assertEquals(null, $this->ro->getCategory());
        self::assertEquals('id', $this->ro->getId());
        self::assertEquals('parentid', $this->ro->getIdparent());
        self::assertEquals('MyController', $this->ro->getController());
        self::assertEquals('Module', $this->ro->getModule());
        self::assertEquals('Event', $this->ro->getEvent());
        self::assertEquals('Extra', $this->ro->getExtra());
        self::assertEquals(false, $this->ro->isPostBack());
        self::assertInstanceOf(RouteOne::class,$this->ro->setController(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setAction(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setId(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setEvent(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setIdParent(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setExtra(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setIsPostBack(false));
        
        self::assertEquals('http://www.example.dom/Module////',$this->ro->getUrl('',false));
 
        //$this->ro->callObject();
    }
    public function testNewVar3()
    {

        $_GET['req']='category/subc/subsubc/id';
        $_GET['_event']='Event';
        $_GET['_extra']='Extra';
        $this->ro=new RouteOne('http://www.example.dom','front');
        $url=$this->ro->getCurrentUrl();
        self::assertNotEmpty($url);
        $this->ro->fetch();
        self::assertEquals('front', $this->ro->type);
        self::assertEquals('category', $this->ro->getCategory());
        self::assertEquals('subc', $this->ro->getSubcategory());
        self::assertEquals('subsubc', $this->ro->getSubsubcategory());
        self::assertEquals('id', $this->ro->getId());
        $this->ro->setController('');
        $this->ro->setAction('');
        $this->ro->setId('');
        $this->ro->setEvent('');
        $this->ro->setIdParent('');

        self::assertEquals('front', $this->ro->getType());
        self::assertEquals('def', $this->ro->getQuery('id', 'def'));
        $this->ro->setQuery('id','123');
        self::assertEquals('123', $this->ro->getQuery('id', 'def'));

        //$this->ro->callObject();
    }
    public function testDefault() {
        $_SERVER['HTTP_HOST']='www.example.dom';
        $_GET['req']='';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->setDefaultValues('controller','action');
        $this->ro->fetch();
        self::assertEquals('controller',$this->ro->controller);
        self::assertEquals('action',$this->ro->action);

        $_SERVER['HTTP_HOST']='www.example.dom';
        $_GET['req']='controller2';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->setDefaultValues('controller','action');
        $this->ro->fetch();
        self::assertEquals('controller2',$this->ro->controller);
        self::assertEquals('action',$this->ro->action);

        $_SERVER['HTTP_HOST']='www.example.dom';
        $_GET['req']='';
        $this->ro=new RouteOne('http://www.example.dom','front');
        $this->ro->setDefaultValues('controller','index','cat','subc','subcc');
        $this->ro->fetch();
        self::assertEquals('cat',$this->ro->category);
        self::assertEquals('subc',$this->ro->subcategory);
        self::assertEquals('subcc',$this->ro->subsubcategory);
        $this->ro->reset();

        // default values to front (category is set)
        $_SERVER['HTTP_HOST']='www.example.dom';
        $_GET['req']='cat2';
        $this->ro=new RouteOne('http://www.example.dom','front');
        $this->ro->setDefaultValues('controller','index','cat','subc','subcc');
        $this->ro->fetch();

        self::assertEquals('cat2',$this->ro->category);
        self::assertEquals('subc',$this->ro->subcategory);
        self::assertEquals('subcc',$this->ro->subsubcategory);
        $this->ro->reset();
    }


    public function testNewVar3b()
    {

        $_GET['req']='front/category/subc/subsubc/id';
        $_GET['_event']='Event';
        $_GET['_extra']='Extra';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->setIdentifyType(['front'=>'front','controller'=>'']);
        $url=$this->ro->getCurrentUrl();
        self::assertNotEmpty($url);
        $this->ro->fetch();
        self::assertEquals('front', $this->ro->type);
        self::assertEquals('category', $this->ro->getCategory());
        self::assertEquals('subc', $this->ro->getSubcategory());
        self::assertEquals('subsubc', $this->ro->getSubsubcategory());
        self::assertEquals('id', $this->ro->getId());
        $this->ro->setController('');
        $this->ro->setAction('');
        $this->ro->setId('');
        $this->ro->setEvent('');
        $this->ro->setIdParent('');

        self::assertEquals('front', $this->ro->getType());
        self::assertEquals('def', $this->ro->getQuery('id', 'def'));
        $this->ro->setQuery('id','123');
        self::assertEquals('123', $this->ro->getQuery('id', 'def'));

        //$this->ro->callObject();
    }


    public function testNewVar4()
    {

        $_GET['req']='module/category/subc/subsubc/id';
        $_GET['_event']='Event';
        $_GET['_extra']='Extra';
        $this->ro=new RouteOne('http://www.example.dom','front',['module']);
        $url=$this->ro->getCurrentUrl();
        self::assertNotEmpty($url);
        $this->ro->fetch();
        self::assertEquals('category', $this->ro->getCategory());
        self::assertEquals('subc', $this->ro->getSubcategory());
        self::assertEquals('subsubc', $this->ro->getSubsubcategory());
        self::assertEquals('id', $this->ro->getId());
        $this->ro->setController('');
        $this->ro->setAction('');
        $this->ro->setId('');
        $this->ro->setEvent('');
        $this->ro->setIdParent('');

        self::assertEquals('front', $this->ro->getType());
        self::assertEquals('def', $this->ro->getQuery('id', 'def'));
        $this->ro->setQuery('id','123');
        self::assertEquals('123', $this->ro->getQuery('id', 'def'));
    }

    public function testNewVar5()
    {
        $this->ro=new RouteOne('http://www.example.dom','front');
        $this->ro->urlFront('mod','cat','subc','subsubc',20);
        self::assertEquals('http://www.example.dom/cat/subc/subsubc/20/',$this->ro->getUrl());
        $this->ro=new RouteOne('http://www.example.dom','front',true);
        $this->ro->urlFront('mod','cat','subc','subsubc',20);
        self::assertEquals('http://www.example.dom/mod/cat/subc/subsubc/20/',$this->ro->getUrl());
        $this->ro=new RouteOne('http://www.example.dom','controller');
        $this->ro->url('mod','controller','action',20);
        self::assertEquals('http://www.example.dom/controller/action/20/',$this->ro->getUrl());
        $this->ro=new RouteOne('http://www.example.dom','controller',true);
        $this->ro->url('mod','controller','action',20);
        self::assertEquals('http://www.example.dom/mod/controller/action/20/',$this->ro->getUrl());
        //$this->ro->callObject();
    }
}
