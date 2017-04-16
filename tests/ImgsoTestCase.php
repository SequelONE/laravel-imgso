<?php namespace Sequelone\Imgso\Tests;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sequelone\Imgso\Exception\FormatException;
use Orchestra\Testbench\TestCase;

class ImgsoTestCase extends TestCase
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

    public function testURLisValid()
    {

        $patterns = array(
            array(
                'url_parameter' => null
            ),
            array(
                'url_parameter' => '-imgso({options})',
                'url_parameter_separator' => '-'
            ),
            array(
                'url_parameter' => '-i-{options}',
                'url_parameter_separator' => '-'
            ),
            array(
                'url_parameter' => '/i/{options}',
                'url_parameter_separator' => '/'
            )
        );

        foreach ($patterns as $pattern) {
            $options = array(
                'grayscale',
                'crop' => true,
                'colorize' => 'FFCC00'
            );
            $url = $this->imgso->url($this->imgsoPath, 300, 300, array_merge($pattern, $options));

            //Check against pattern
            $urlMatch = preg_match('#'.$this->imgso->pattern($pattern['url_parameter']).'#', $url, $matches);
            $this->assertEquals($urlMatch, 1);

            //Check path
            $parsedPath = $this->imgso->parse($url, $pattern);
            $this->assertEquals($parsedPath['path'], $this->imgsoPath);

            //Check options
            foreach ($options as $key => $value) {
                if (is_numeric($key)) {
                    $this->assertTrue($parsedPath['options'][$value]);
                } else {
                    $this->assertEquals($parsedPath['options'][$key], $value);
                }
            }

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
