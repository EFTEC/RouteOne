<?php

namespace eftec\tests;

use eftec\routeone\RouteOne;
use PHPUnit\Framework\TestCase;
class categoryController {
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
        $_GET['req']='aaa/bbb/ccc'; // bbb is for api (but we are forcing it the definition of it.
        $this->ro=new RouteOne('http://www.example.dom','api',true,true);
        self::assertEquals(true,$this->ro->isPostBack());
        self::assertEquals('aaa',$this->ro->module);
        self::assertEquals('ccc',$this->ro->controller);
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
     * @throws \Exception
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
     * @throws \Exception
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
    public function testNewVar2()
    {
        $_GET['req']='Module/MyController/Action2/id/parentid';
        $_GET['_event']='Event';
        $_GET['_extra']='Extra';
        $this->ro=new RouteOne('http://www.example.dom','controller',true);
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
        $this->ro=new RouteOne('http://www.example.dom','front',true);
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
