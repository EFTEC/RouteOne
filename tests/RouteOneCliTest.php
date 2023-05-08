<?php

namespace eftec\tests;
use eftec\CliOne\CliOne;
use eftec\routeone\RouteOneCli;
use PHPUnit\Framework\TestCase;

class RouteOneCliTest extends TestCase
{
    /** @var RouteOneCli */
    public $route;
    /** @var CliOne */
    public $cli;

    public function setUp(): void
    {

        parent::setUp();
        $this->cli=new CliOne();
        chdir(__DIR__);

    }

    public function test1(): void
    {
        CliOne::testUserInput(['router', 'configure','filetest','yes','',''
            ,'paths','add','path1','','','remove','path1'
            ,'add','path2','',''
            ,'edit','path2','a','b','','htaccess','router'
            ,'save','yes','configex','','']);

        $this->route=new RouteOneCli();
        $this->assertEquals([
            'routerfilename' => 'configex',
            'baseurldev' => 'http://localhost',
            'baseurlprod' => 'https://www.domain.dom',
            'dev' => gethostname(),
            'paths' =>
                [
                    'path2' => 'a, b',
                ],
        ],$this->route->getConfig());
    }
}
