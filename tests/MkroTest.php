<?php

require_once '/var/www/mkro/Mkro.php';

/**
 * Test class for Mkro.
 * Generated by PHPUnit on 2010-11-18 at 15:59:14.
 */
class MkroTest extends PHPUnit_Extensions_OutputTestCase
{
    /**
     * @var Mkro
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $_GET['getvar'] = 'getvalue';
        $_POST['postvar'] = 'postvalue';
        
        $_SERVER['SCRIPT_NAME'] = '/mkro/index.php';
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @todo Implement testConfig().
     */
    public function testReadConfigValue()
    {
        $this->assertEquals('', Mkro::config('views_dir'));
    }
    
    /**
     * @depends testReadConfigValue
     */    
    public function testChangeConfigValue()
    {
        $this->assertEquals(TRUE, Mkro::config('views_dir', '/views'));
    }
    
    /**
     * @depends testChangeConfigValue
     */
    public function testReadChangedConfigValue()
    {
        $this->assertEquals('/views', Mkro::config('views_dir'));
    }
    
    public function testReadInvalidConfigValue()
    {
        $this->assertEquals(FALSE, Mkro::config('foobar'));
    }

    /**
     * @todo Implement testRoute().
     */
    public function testGetRoute()
    {    
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/mkro/test1';
        
        $this->assertEquals(TRUE, Mkro::route('GET /test1', function() {}));
        
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/mkro/test2';
        
        $this->assertEquals(FALSE, Mkro::route('GET /test2', function() {}));
    }
    
    public function testPostRoute()
    {
        // Invoke private reset method.
        $method = new ReflectionMethod('Mkro', 'reset');
        $method->setAccessible(TRUE);
        $method->invoke(NULL);
    
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/mkro/test1';
        
        $this->assertEquals(TRUE, Mkro::route('POST /test1', function() {}));
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/mkro/test2';
        
        $this->assertEquals(FALSE, Mkro::route('POST /test2', function() {}));
    }

    /**
     * @todo Implement testGet().
     */
    public function testGet()
    {
        $this->assertEquals('getvalue', Mkro::get('getvar'));
    }

    /**
     * @todo Implement testPost().
     */
    public function testPost()
    {
        $this->assertEquals('postvalue', Mkro::post('postvar'));
    }

    /**
     * @todo Implement testRender().
     */
    public function testRender()
    {
        Mkro::config('views_dir', '');
        $this->expectOutputString('RenderViewTest');
        Mkro::render('mkro_test_view');
    }
    
    public function testRenderWithData()
    {
        $this->expectOutputString('RenderViewTestFoobar');
        Mkro::render('mkro_test_view', array('data' => 'Foobar'));
    }
}
?>
