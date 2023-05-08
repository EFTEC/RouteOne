<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnusedLocalVariableInspection */

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
    public function actiontest2Action($id, $idparent='', $event='')
    {
        echo 'action test called';
        return $id;
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
    public function testAddPath():void
    {
        $_SERVER['HTTP_HOST']='www.example.dom';
        $this->ro=new RouteOne('http://www.example.dom');

        $this->ro->addPath('{controller:base}/{id}/{idparent}','normal');
        $this->assertEquals([['controller','base'],['id',null],['idparent',null]],$this->ro->path['normal']);


        $this->ro->addPath('base/base2/{controller:base}/{id}/{idparent}','base');
        $this->assertEquals([['controller','base'],['id',null],['idparent',null]],$this->ro->path['base']);
        $this->assertEquals('base/base2/',$this->ro->pathName['base']);
            }
    public function testComplete():void
    {
        $_SERVER['HTTP_HOST']='www.example.dom';
        $_GET['req']='module1/controller1/id123/';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->addPath('{module:module1}/{controller:base}/{id}/{idparent}','normal',
            function(callable $next,$id=null,$idparent=null,$event=null) {
                echo "middleware\n";
                $result=$next($id,$idparent,$event); // calling the controller
                echo "endmiddleware\n";
                return $result;
            }
        );
        $this->ro->addPath('base/base2/{controller:base}/{id}/{idparent}','base');

        $this->assertEquals('normal',$this->ro->fetchPath());
        $this->assertEquals('id123',$this->ro->callObjectEx(function(...$args) { return $args[0];}));
    }
    public function testComplete2():void
    {
        $_SERVER['HTTP_HOST']='www.example.dom';
        $_GET['req']='module1/controller1/id123/';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->addPath('{module:module1}/{controller:base}/{id}/{idparent}','normal',
            function(callable $next,$id=null,$idparent=null,$event=null) {
                echo "middleware\n";
                $result=$next($id,$idparent,$event); // calling the controller
                echo "endmiddleware\n";
                return $result;
            }
        );
        $this->ro->addPath('base/base2/{controller:base}/{id}/{idparent}','base');

        $this->assertEquals('normal',$this->ro->fetchPath());
        $this->assertEquals('id123',$this->ro->callObjectEx(categoRYController2::class,true,'actiontest2Action'));
    }
    public function testRoot(): void
    {
        $_SERVER['HTTP_HOST']='www.example.dom';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->addPath('{controller:base}/{id}/{idparent}','normal');
        $this->ro->fetchPath();
        self::assertEquals('base',$this->ro->controller);
    }
    public function testMisc1(): void
    {
        $_SERVER['HTTP_HOST']='www.example.dom';
        $_SERVER['REQUEST_METHOD']='POST';
        $_GET['req']='module1/controller1/action1';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->addPath('{module}/{controller}/{action}','path1');
        self::assertEquals('path1', $this->ro->fetchPath());
        self::assertEquals(true,$this->ro->isPostBack());
        self::assertEquals('module1',$this->ro->module);
        self::assertEquals('controller1',$this->ro->controller);
        self::assertEquals('action1',$this->ro->action);
        self::assertEquals('POST',$this->ro->verb);
        self::assertEquals('http://www.example.dom/module1/controller1/action1',$this->ro->getUrlPath());
        unset($_SERVER['REQUEST_METHOD']);
    }
    public function testMisc1b(): void
    {
        $_SERVER['HTTP_HOST']='www.example.dom';
        $_SERVER['REQUEST_METHOD']='POST';
        $_GET['req']='module1/controller1/action1/';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->addPath('{module}/{controller}/{action}','path1');
        self::assertEquals('path1', $this->ro->fetchPath());
        self::assertEquals(true,$this->ro->isPostBack());
        self::assertEquals('module1',$this->ro->module);
        self::assertEquals('controller1',$this->ro->controller);
        self::assertEquals('action1',$this->ro->action);
        self::assertEquals('POST',$this->ro->verb);
        self::assertEquals('http://www.example.dom/module1/controller1/action1',$this->ro->getUrlPath());
        unset($_SERVER['REQUEST_METHOD']);
    }
    public function testMisc1NotFound(): void
    {
        $_SERVER['HTTP_HOST'] = 'www.example.dom';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['req'] = 'module1/controller1/action1';
        $this->ro = new RouteOne('http://www.example.dom');
        $this->ro->addPath('xx/{module}/{controller}/{action}/{xx}', 'path1');
        self::assertEquals(null, $this->ro->fetchPath());
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
        self::assertEquals('http://www.example.dom/MyController/Action2/id/parentid',$this->ro->getUrlPath());
        $this->ro->callFile(__DIR__ .'/%s.php');

        self::assertEquals('http://www.example.dom/dummy.php',$this->ro->getNonRouteUrl('dummy.php'));

        //$this->ro->callObject();
    }

    /** @noinspection UnnecessaryAssertionInspection */
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
        self::assertEquals('http://www.example.dom/Module/MyController/Action2/id/parentid',$this->ro->getUrlPath());
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
        self::assertEquals('http://www.example.dom/Module',$this->ro->getUrlPath());




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
        self::assertInstanceOf(RouteOne::class,$this->ro->setId('xxx'));
        self::assertInstanceOf(RouteOne::class,$this->ro->setEvent(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setIdParent(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setExtra(''));
        self::assertInstanceOf(RouteOne::class,$this->ro->setIsPostBack(false));
        self::assertEquals('http://www.example.dom/Module/MyController/Action2/id/xxx',$this->ro->getUrlPath());

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
        self::assertEquals('http://www.example.dom/aaaaaaAA999/bbb_/aaaàa aáa../2',$this->ro->getUrlPath());


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
        self::assertEquals('http://www.example.dom/cat/subc/subcc',$this->ro->getUrlPath());
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
        self::assertEquals('http://www.example.dom/cat2/subc/subcc',$this->ro->getUrlPath());
        $this->ro->reset();
    }
    public function testGen(): void
    {
        $_SERVER['HTTP_HOST']='www.example.dom';
        $_GET['req']='';
        $this->ro=new RouteOne('http://www.example.dom');
        $this->ro->addPath('mybase/{controller:defcontroller}/{action:defaction}/{id:1}/{idparent:2}');
        $this->ro->addPath('mybase/{action:defaction}/{id:1}/{idparent:2}/{controller:defcontroller}');
        //$this->ro->fetchPath();
        self::assertEquals('http://www.example.dom/mybase/cont/act/1/2',
            $this->ro->url(null,'cont','act')->getUrlPath(0));
        self::assertEquals('http://www.example.dom/mybase/act/id/idparent/cont',
            $this->ro->url(null,'cont','act','id','idparent')->getUrlPath(1));
    }

}
