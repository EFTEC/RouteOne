<?php

namespace eftec\tests;

use eftec\routeone\RouteOne;
use PHPUnit\Framework\TestCase;

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


    public function testNewVar()
    {
        $this->ro=new RouteOne('http://www.example.dom/');
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
        //$this->ro->callObject();
    }
    public function testNewVar2()
    {
        
        $_GET['req']='Module/MyController/Action2/id/parentid';
        $_GET['_event']='Event';
        $_GET['_extra']='Extra';
        $this->ro=new RouteOne('http://www.example.dom/','controller',true);
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
        $this->ro->setController("");
        $this->ro->setAction("");
        $this->ro->setId("");
        $this->ro->setEvent("");
        $this->ro->setIdParent("");
 
        //$this->ro->callObject();
    }
    public function testNewVar3()
    {

        $_GET['req']='category/subc/subsubc/id';
        $_GET['_event']='Event';
        $_GET['_extra']='Extra';
        $this->ro=new RouteOne('http://www.example.dom/','front');
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

        //$this->ro->callObject();
    }
}
