<?php

namespace eftec\tests;

use eftec\routeone\RouteOne;
use PHPUnit\Framework\TestCase;
class categoryController {
    public function actiontestAction($id,$idparent='',$event='') {
        echo "action test called";
    }
}

/**
 * Class RouterOneTest
 * @package eftec\tests
 * @copyright Jorge Castro Castillo
 * 2019-feb-24 5:06 PM
 */
class routerOneTest extends TestCase
{
    /** @var RouteOne */
    public $ro;

    public function setUp()
    {
        
    }

    /**
     * @throws \Exception
     */
    public function testNewVar()
    {
        $this->ro=new RouteOne('http://www.example.dom');
        $_GET['req']='MyController/Action2/id/parentid';
        $url=$this->ro->getCurrentUrl();
        $this->assertNotEmpty($url);
        $this->ro->fetch();
        $this->assertEquals("Action2", $this->ro->getAction());
        $this->assertEquals(null, $this->ro->getCategory());
        $this->assertEquals("id", $this->ro->getId());
        $this->assertEquals("parentid", $this->ro->getIdparent());
        $this->assertEquals("MyController", $this->ro->getController());
        $this->ro->callFile(dirname(__FILE__).'/%s.php');
        
        $this->assertEquals('http://www.example.dom/dummy.php',$this->ro->getNonRouteUrl('dummy.php'));
        
        //$this->ro->callObject();
    }

    /**
     * @throws \Exception
     */
    public function testCall() {
        $this->ro=new RouteOne('http://www.example.dom');
        $_GET['req']='category/actiontest/id/parentid';
        $this->ro->fetch();
        $r=$this->ro->callObject('eftec\tests\%sController');
        $this->assertEquals(null,$r,'no error'); 
        $r=$this->ro->callObjectEx('eftec\tests\{controller}Controller',true,'{action}Action',null,null
            ,['id']);
        $this->assertEquals(null,$r,'no error');
        $r=$this->ro->callObjectEx('eftec\tests\{controller}ControllerX',false,'{action}Action',null,null
            ,['id']);
        $this->assertEquals('Class eftec\tests\categoryControllerX doesn\'t exist',$r,'error');
        $r=$this->ro->callObjectEx('eftec\tests\{controller}Controller',false,'{action}ActionX',null,null
            ,['id']);
        $this->assertEquals('Action ex [actiontestActionX or ] (GET) not found for class [eftec\tests\categoryController]'
            ,$r,'error');
        $this->ro->setAction("");
        $this->ro->setController("");

    }
    public function testNewVar2()
    {
        $_GET['req']='Module/MyController/Action2/id/parentid';
        $_GET['_event']='Event';
        $_GET['_extra']='Extra';
        $this->ro=new RouteOne('http://www.example.dom','controller',true);
        $url=$this->ro->getCurrentUrl();
        $this->assertNotEmpty($url);
        $this->ro->fetch();
        $this->assertEquals("Action2", $this->ro->getAction());
        $this->assertEquals(null, $this->ro->getCategory());
        $this->assertEquals("id", $this->ro->getId());
        $this->assertEquals("parentid", $this->ro->getIdparent());
        $this->assertEquals("MyController", $this->ro->getController());
        $this->assertEquals("Module", $this->ro->getModule());
        $this->assertEquals("Event", $this->ro->getEvent());
        $this->assertEquals("Extra", $this->ro->getExtra());
        $this->assertEquals(false, $this->ro->isPostBack());
        $this->ro->setController("");
        $this->ro->setAction("");
        $this->ro->setId("");
        $this->ro->setEvent("");
        $this->ro->setIdParent("");
        $this->ro->setExtra("");
        $this->ro->setIsPostBack(false);
        
        $this->assertEquals('http://www.example.dom/Module////',$this->ro->getUrl('',false));
 
        //$this->ro->callObject();
    }
    public function testNewVar3()
    {

        $_GET['req']='category/subc/subsubc/id';
        $_GET['_event']='Event';
        $_GET['_extra']='Extra';
        $this->ro=new RouteOne('http://www.example.dom','front');
        $url=$this->ro->getCurrentUrl();
        $this->assertNotEmpty($url);
        $this->ro->fetch();
        $this->assertEquals("category", $this->ro->getCategory());
        $this->assertEquals('subc', $this->ro->getSubcategory());
        $this->assertEquals("subsubc", $this->ro->getSubsubcategory());
        $this->assertEquals("id", $this->ro->getId());
        $this->ro->setController("");
        $this->ro->setAction("");
        $this->ro->setId("");
        $this->ro->setEvent("");
        $this->ro->setIdParent("");

        $this->assertEquals("front",$this->ro->getType());
        $this->assertEquals("def",$this->ro->getQuery('id','def'));
        $this->ro->setQuery('id','123');
        $this->assertEquals("123",$this->ro->getQuery('id','def'));

        //$this->ro->callObject();
    }

    public function testNewVar4()
    {

        $_GET['req']='module/category/subc/subsubc/id';
        $_GET['_event']='Event';
        $_GET['_extra']='Extra';
        $this->ro=new RouteOne('http://www.example.dom','front',true);
        $url=$this->ro->getCurrentUrl();
        $this->assertNotEmpty($url);
        $this->ro->fetch();
        $this->assertEquals("category", $this->ro->getCategory());
        $this->assertEquals('subc', $this->ro->getSubcategory());
        $this->assertEquals("subsubc", $this->ro->getSubsubcategory());
        $this->assertEquals("id", $this->ro->getId());
        $this->ro->setController("");
        $this->ro->setAction("");
        $this->ro->setId("");
        $this->ro->setEvent("");
        $this->ro->setIdParent("");

        $this->assertEquals("front",$this->ro->getType());
        $this->assertEquals("def",$this->ro->getQuery('id','def'));
        $this->ro->setQuery('id','123');
        $this->assertEquals("123",$this->ro->getQuery('id','def'));
    }

    public function testNewVar5()
    {
        $this->ro=new RouteOne('http://www.example.dom','front');
        $this->ro->urlFront('mod','cat','subc','subsubc',20);
        $this->assertEquals('http://www.example.dom/cat/subc/subsubc/20/',$this->ro->getUrl());
        $this->ro=new RouteOne('http://www.example.dom','front',true);
        $this->ro->urlFront('mod','cat','subc','subsubc',20);
        $this->assertEquals('http://www.example.dom/mod/cat/subc/subsubc/20/',$this->ro->getUrl());
        $this->ro=new RouteOne('http://www.example.dom','controller');
        $this->ro->url('mod','controller','action',20);
        $this->assertEquals('http://www.example.dom/controller/action/20/',$this->ro->getUrl());
        $this->ro=new RouteOne('http://www.example.dom','controller',true);
        $this->ro->url('mod','controller','action',20);
        $this->assertEquals('http://www.example.dom/mod/controller/action/20/',$this->ro->getUrl());
        //$this->ro->callObject();
    }
}
