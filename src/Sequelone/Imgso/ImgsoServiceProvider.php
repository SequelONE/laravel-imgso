<?php namespace Sequelone\Imgso;

use Illuminate\Support\ServiceProvider;

class ImgsoServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Config file path
        $configFile = __DIR__ . '/../../resources/config/imgso.php';
        $publicFile = __DIR__ . '/../../resources/assets/';

        // Merge files
        $this->mergeConfigFrom($configFile, 'imgso');

        // Publish
        $this->publishes([
            $configFile => config_path('imgso.php')
        ], 'config');
        
        $this->publishes([
            $publicFile => public_path('vendor/sequelone/imgso')
        ], 'public');

        $app = $this->app;
        $router = $app['router'];
        $config = $app['config'];
        
        $pattern = $app['imgso']->pattern();
        $proxyPattern = $config->get('imgso.proxy_route_pattern');
        $router->pattern('imgso_pattern', $pattern);
        $router->pattern('imgso_proxy_pattern', $proxyPattern ? $proxyPattern:$pattern);

        //Serve imgso
        $serve = config('imgso.serve');
        if ($serve) {
            // Create a route that match pattern
            $serveRoute = $config->get('imgso.serve_route', '{imgso_pattern}');
            $router->get($serveRoute, array(
                'as' => 'imgso.serve',
                'domain' => $config->get('imgso.domain', null),
                'uses' => 'Sequelone\Imgso\ImgsoController@serve'
            ));
        }
        
        //Proxy
        $proxy = $this->app['config']['imgso.proxy'];
        if ($proxy) {
            $serveRoute = $config->get('imgso.proxy_route', '{imgso_proxy_pattern}');
            $router->get($serveRoute, array(
                'as' => 'imgso.proxy',
                'domain' => $config->get('imgso.proxy_domain'),
                'uses' => 'Sequelone\Imgso\ImgsoController@proxy'
            ));
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('imgso', function ($app) {
            return new ImgsoManager($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('imgso');
    }
}
