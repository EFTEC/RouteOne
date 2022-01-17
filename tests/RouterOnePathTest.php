<?php /** @noinspection PhpUnusedLocalVariableInspection */

/** @noinspection HttpUrlsUsage */

namespace eftec\tests;

use eftec\routeone\RouteOne;
use Exception;
use PHPUnit\Framework\TestCase;
class categoRYController2 {
    /** @noinspection PhpUnused */
    public function actiontestAction($id, $idparent='', $event=''): void
    {
        echo 'action test called';
    }
}

/**
 * Class RouterOneTest
 * @package eftec\tests
 * @copyright Jorge Castro Castillo
 * 2019-feb-24 5:06 PM
 */
class RouterOneTestPath extends TestCase
{
    /** @var RouteOne */
    public $ro;

    public function setUp()
    {

    }
    public function testNaked(): void
    {
        $_SERVER['HTTP_HOST']='www.example.dom';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->addPath('{controller}/{id}/{idparent}');
        $this->ro->fetchPath();

        $url=$this->ro->alwaysNakedDomain(false,false);
        self::assertEquals('http://example.dom',$url);

        $_SERVER['HTTP_HOST']='www.example.dom';
        $_SERVER['HTTPS']='';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->fetchPath();
        $url=$this->ro->alwaysNakedDomain(true,false);
        self::assertEquals('https://example.dom',$url);
    }
    public function testMisc1(): void
    {
        $_SERVER['HTTP_HOST']='www.example.dom';
        $_SERVER['REQUEST_METHOD']='POST';
        $_GET['req']='module1/controller1/action1';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->addPath('{module}/{controller}/{action}');
        $this->ro->fetchPath();
        self::assertEquals(true,$this->ro->isPostBack());
        self::assertEquals('module1',$this->ro->module);
        self::assertEquals('controller1',$this->ro->controller);
        self::assertEquals('action1',$this->ro->action);
        self::assertEquals('POST',$this->ro->verb);
        unset($_SERVER['REQUEST_METHOD']);
    }
    /**
     * @throws Exception
     */
    public function testNewVar(): void
    {
        $_SERVER['HTTP_HOST']='www.example.dom';
        $_SERVER['REQUEST_METHOD']='POST';
        $this->ro=new RouteOne('http://www.example.dom');
        $_GET['req']='MyController/Action2/id/parentid';
        $url=$this->ro->getCurrentUrl();
        self::assertNotEmpty($url);
        $this->ro->addPath('{controller}/{action}/{id}/{idparent}');
        $this->ro->fetchPath();
        //$this->ro->fetch();
        self::assertEquals('Action2', $this->ro->getAction());
        self::assertEquals(null, $this->ro->getCategory());
        self::assertEquals('id', $this->ro->getId());
        self::assertEquals('parentid', $this->ro->getIdparent());
        self::assertEquals('MyController', $this->ro->getController());
        $this->ro->callFile(__DIR__ .'/%s.php');

        self::assertEquals('http://www.example.dom/dummy.php',$this->ro->getNonRouteUrl('dummy.php'));

        //$this->ro->callObject();
    }

    public function testNoFront(): void
    {
        $_GET['req']='Module/MyController/Action2/id/parentid';
        $_GET['_event']='Event';
        $_GET['_extra']='Extra';
        $_SERVER['REQUEST_METHOD']='GET';
        $this->ro=new RouteOne('http://www.example.dom',null,['Module'],false,'nomodulefront');
        $url=$this->ro->getCurrentUrl();
        self::assertNotEmpty($url);
        $this->ro->addPath('{module}/{controller}/{action}/{id}/{idparent}');
        $this->ro->fetchPath();
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



        //$this->ro->callObject();
        $_GET['req']='Module/MyController/Action2/id/parentid';
        $_GET['_event']='Event';
        $_GET['_extra']='Extra';
        $this->ro=new RouteOne('http://www.example.dom',null,['Module'],false,'modulefront');
        $url=$this->ro->getCurrentUrl();
        self::assertNotEmpty($url);
        $this->ro->addPath('{module}/{category}/{subcategory}/{subsubcategory}/{id}/{idparent}');
        $this->ro->fetchPath();
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

    }
    public function testNoChar(): void
    {
        $_SERVER['HTTP_HOST']='www.example.dom';
        $_GET['req']='aaaaaáa AA999/\'"bb \\b*+-._/aaaàa aáa..';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->addPath('{controller:defcontroller}/{action:defaction}/{id:1}/{idparent:2}');
        $this->ro->fetchPath();
        self::assertEquals('aaaaaaAA999',$this->ro->controller);
        self::assertEquals('bbb_',$this->ro->action);
        self::assertEquals('aaaàa aáa..',$this->ro->id);

        $_SERVER['HTTP_HOST']='www.example.dom';
        $_GET['req']='controller2';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->setDefaultValues('controller','action');
        $this->ro->addPath('{controller:defcontroller}/{action:defaction}/{id:1}/{idparent:2}');
        $this->ro->fetchPath();
        self::assertEquals('controller2',$this->ro->controller);
        self::assertEquals('defaction',$this->ro->action);

        $_SERVER['HTTP_HOST']='www.example.dom';
        $_GET['req']='';
        $this->ro=new RouteOne('http://www.example.dom','front');
        $this->ro->setDefaultValues('controller','index','cat','subc','subcc');
        $this->ro->addPath('{category:cat}/{subcategory:subc}/{subsubcategory:subcc}');
        $this->ro->fetchPath();
        self::assertEquals('cat',$this->ro->category);
        self::assertEquals('subc',$this->ro->subcategory);
        self::assertEquals('subcc',$this->ro->subsubcategory);
        $this->ro->reset();

    }

    public function testDefault(): void
    {
        $_SERVER['HTTP_HOST']='www.example.dom';
        $_GET['req']='';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->addPath('{controller:defcontroller}/{action:defaction}/{id:1}/{idparent:2}');
        $this->ro->fetchPath();
        self::assertEquals('defcontroller',$this->ro->controller);
        self::assertEquals('defaction',$this->ro->action);

        $_SERVER['HTTP_HOST']='www.example.dom';
        $_GET['req']='controller2';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->setDefaultValues('controller','action');
        $this->ro->addPath('{controller:defcontroller}/{action:defaction}/{id:1}/{idparent:2}');
        $this->ro->fetchPath();
        self::assertEquals('controller2',$this->ro->controller);
        self::assertEquals('defaction',$this->ro->action);

        $_SERVER['HTTP_HOST']='www.example.dom';
        $_GET['req']='';
        $this->ro=new RouteOne('http://www.example.dom','front');
        $this->ro->setDefaultValues('controller','index','cat','subc','subcc');
        $this->ro->addPath('{category:cat}/{subcategory:subc}/{subsubcategory:subcc}');
        $this->ro->fetchPath();
        self::assertEquals('cat',$this->ro->category);
        self::assertEquals('subc',$this->ro->subcategory);
        self::assertEquals('subcc',$this->ro->subsubcategory);
        $this->ro->reset();

        // default values to front (category is set)
        $_SERVER['HTTP_HOST']='www.example.dom';
        $_GET['req']='cat2';
        $this->ro=new RouteOne('http://www.example.dom','front');
        $this->ro->addPath('{category:cat}/{subcategory:subc}/{subsubcategory:subcc}');
        $this->ro->fetchPath();

        self::assertEquals('cat2',$this->ro->category);
        self::assertEquals('subc',$this->ro->subcategory);
        self::assertEquals('subcc',$this->ro->subsubcategory);
        $this->ro->reset();
    }

}
