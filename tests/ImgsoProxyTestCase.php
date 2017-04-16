<?php namespace Sequelone\Imgso\Tests;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sequelone\Imgso\Exception\FormatException;
use Orchestra\Testbench\TestCase;

class ImgsoProxyTestCase extends TestCase
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

    public function testProxy()
    {
        $url = $this->imgso->url($this->imgsoPath, 300, 300, [
            'crop' => true
        ]);
        $response = $this->call('GET', $url);
        $this->assertTrue($response->isOk());
        
        $imgso = imgsocreatefromstring($response->getContent());
        $this->assertTrue($imgso !== false);
        
        $this->assertEquals(imgsosx($imgso), 300);
        $this->assertEquals(imgsosy($imgso), 300);
        
        imgsodestroy($imgso);
    }

    public function testProxyURL()
    {
        $this->app['config']->set('imgso.host', '/proxy/http://placehold.it/');
        $this->app['config']->set('imgso.proxy_filesystem', null);
        $this->app['config']->set('imgso.proxy_route_pattern', '^(.*)$');
        
        $url = $this->imgso->url('/640x480.png', 300, 300, [
            'crop' => true
        ]);
        $response = $this->call('GET', $url);
        $this->assertTrue($response->isOk());
        
        $imgso = imgsocreatefromstring($response->getContent());
        $this->assertTrue($imgso !== false);
        
        $this->assertEquals(imgsosx($imgso), 300);
        $this->assertEquals(imgsosy($imgso), 300);
        
        imgsodestroy($imgso);
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
        
        $app['config']->set('imgso.host', '/proxy');
        $app['config']->set('imgso.serve', false);
        $app['config']->set('imgso.proxy', true);
        $app['config']->set('imgso.proxy_route', '/proxy/{imgso_proxy_pattern}');
        $app['config']->set('imgso.proxy_filesystem', 'imgso_testbench');
        $app['config']->set('imgso.proxy_cache_filesystem', null);
        
        $app['config']->set('filesystems.default', 'imgso_testbench');
        $app['config']->set('filesystems.cloud', 'imgso_testbench');
        
        $app['config']->set('filesystems.disks.imgso_testbench', [
            'driver' => 'local',
            'root' => __DIR__.'/fixture'
        ]);
        
        $app['config']->set('filesystems.disks.imgso_testbench_cache', [
            'driver' => 'local',
            'root' => __DIR__.'/fixture/cache'
        ]);
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
