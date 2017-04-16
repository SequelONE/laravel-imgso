<?php namespace Sequelone\Imgso\Tests;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sequelone\Imgso\Exception\FormatException;
use Orchestra\Testbench\TestCase;

class ImgsoServeTestCase extends TestCase
{
    protected $imgsoPath = '/imgso.jpg';
    protected $imgsoSmallPath = '/imgso_small.jpg';
    protected $imgsoSize;
    protected $imgsoSmallSize;

    public function setUp()
    {
        parent::setUp();
        
        $this->imgso = $this->app['imgso'];
        $this->imgsoSize = getimgsosize(public_path().$this->imgsoPath);
        $this->imgsoSmallSize = getimgsosize(public_path().$this->imgsoSmallPath);
    }

    public function tearDown()
    {
        $customPath = $this->app['path.public'].'/custom';
        $this->app['config']->set('imgso.write_path', $customPath);
        
        $this->imgso->deleteManipulated($this->imgsoPath);
        
        parent::tearDown();
    }

    public function testServeWriteImgso()
    {
        $this->app['config']->set('imgso.write_imgso', true);

        $url = $this->imgso->url($this->imgsoPath, 300, 300, [
            'crop' => true
        ]);

        $response = $this->call('GET', $url);

        $this->assertTrue($response->isOk());

        $imgsoPath = $this->app['path.public'].'/'.basename($url);
        $this->assertFileExists($imgsoPath);

        $sizeManipulated = getimgsosize($imgsoPath);
        $this->assertEquals($sizeManipulated[0], 300);
        $this->assertEquals($sizeManipulated[1], 300);

        $this->app['config']->set('imgso.write_imgso', false);
    }

    public function testServeWriteImgsoPath()
    {
        $customPath = 'custom';

        $this->app['config']->set('imgso.write_imgso', true);
        $this->app['config']->set('imgso.write_path', $customPath);

        $url = $this->imgso->url($this->imgsoPath, 300, 300, [
            'crop' => true
        ]);

        $response = $this->call('GET', $url);

        $this->assertTrue($response->isOk());

        $imgsoPath = public_path($customPath.'/'.basename($url));
        $this->assertFileExists($imgsoPath);

        $sizeManipulated = getimgsosize($imgsoPath);
        $this->assertEquals($sizeManipulated[0], 300);
        $this->assertEquals($sizeManipulated[1], 300);

        $this->app['config']->set('imgso.write_imgso', false);
        $this->app['config']->set('imgso.write_path', null);
    }

    public function testServeNoResize()
    {

        $url = $this->imgso->url($this->imgsoPath, null, null, array(
            'grayscale'
        ));
        $response = $this->call('GET', $url);

        $this->assertTrue($response->isOk());

        $sizeManipulated = getimgsosizefromstring($response->getContent());
        $this->assertEquals($sizeManipulated[0], $this->imgsoSize[0]);
        $this->assertEquals($sizeManipulated[1], $this->imgsoSize[1]);
    }

    public function testServeResizeWidth()
    {
        $url = $this->imgso->url($this->imgsoPath, 300);
        $response = $this->call('GET', $url);

        $this->assertTrue($response->isOk());

        $sizeManipulated = getimgsosizefromstring($response->getContent());
        $this->assertEquals($sizeManipulated[0], 300);
    }

    public function testServeResizeHeight()
    {
        $url = $this->imgso->url($this->imgsoPath, null, 300);
        $response = $this->call('GET', $url);

        $this->assertTrue($response->isOk());

        $sizeManipulated = getimgsosizefromstring($response->getContent());
        $this->assertEquals($sizeManipulated[1], 300);
    }

    public function testServeResizeCrop()
    {
        //Both height and width with crop
        $url = $this->imgso->url($this->imgsoPath, 300, 300, array(
            'crop' => true
        ));
        $response = $this->call('GET', $url);

        $this->assertTrue($response->isOk());

        $sizeManipulated = getimgsosizefromstring($response->getContent());
        $this->assertEquals($sizeManipulated[0], 300);
        $this->assertEquals($sizeManipulated[1], 300);
    }
    
    public function testServeResizeCropSmall()
    {
        //Both height and width with crop
        $url = $this->imgso->url($this->imgsoSmallPath, 300, 300, array(
            'crop' => true
        ));
        $response = $this->call('GET', $url);

        $this->assertTrue($response->isOk());

        $sizeManipulated = getimgsosizefromstring($response->getContent());
        $this->assertEquals($sizeManipulated[0], 300);
        $this->assertEquals($sizeManipulated[1], 300);
    }

    public function testServeWrongParameter()
    {
        $url = $this->imgso->url($this->imgsoPath, 300, 300, array(
            'crop' => true,
            'wrong' => true
        ));
        
        try {
            $response = $this->call('GET', $url);
            $this->assertSame(404, $response->getStatusCode());
        }
        catch(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e)
        {
            $this->assertInstanceOf('\Symfony\Component\HttpKernel\Exception\NotFoundHttpException', $e);
        }
    }

    public function testServeWrongFile()
    {
        $url = $this->imgso->url('/wrong123.jpg', 300, 300, array(
            'crop' => true,
            'wrong' => true
        ));
        
        try {
            $response = $this->call('GET', $url);
            $this->assertSame(404, $response->getStatusCode());
        }
        catch(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e)
        {
            $this->assertInstanceOf('\Symfony\Component\HttpKernel\Exception\NotFoundHttpException', $e);
        }
    }

    public function testServeWrongFormat()
    {
        $url = $this->imgso->url('/wrong.jpg', 300, 300, array(
            'crop' => true
        ));
        
        try {
            $response = $this->call('GET', $url);
            $this->assertSame(500, $response->getStatusCode());
        }
        catch(\Symfony\Component\HttpKernel\Exception\HttpException $e)
        {
            $this->assertInstanceOf('\Symfony\Component\HttpKernel\Exception\HttpException', $e);
        }
    }
    
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app->instance('path.public', __DIR__.'/fixture');
    }

    protected function getPackageProviders($app)
    {
        return array('Sequelone\Imgso\ImgsoServiceProvider');
    }

    protected function getPackageAliases($app)
    {
        return array(
            'Imgso' => 'Sequelone\Imgso\Facades\Imgso'
        );
    }
}
