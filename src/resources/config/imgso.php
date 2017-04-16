<?php



return array(

    /*
    |--------------------------------------------------------------------------
    | Default Imgso Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default imgso "driver" used by Imagine library
    | to manipulate imgsos.
    |
    | Supported: "gd", "imagick", "gmagick"
    |
    */
    'driver' => 'gd',
    
    /*
    |--------------------------------------------------------------------------
    | Memory limit
    |--------------------------------------------------------------------------
    |
    | When manipulating an imgso, the memory limit is increased to this value
    |
    */
    'memory_limit' => '128M',

    /*
    |--------------------------------------------------------------------------
    | Source directories
    |--------------------------------------------------------------------------
    |
    | A list a directories to look for imgsos
    |
    */
    'src_dirs' => array(
        public_path()
    ),

    /*
    |--------------------------------------------------------------------------
    | Host
    |--------------------------------------------------------------------------
    |
    | The http host where the imgso are served. Used by the Imgso::url() method
    | to generate the right URL.
    |
    */
    'host' => '',

    /*
    |--------------------------------------------------------------------------
    | Pattern
    |--------------------------------------------------------------------------
    |
    | The pattern that is used to match routes that will be handled by the
    | ImgsoController. The {parameters} will be remplaced by the url parameters
    | pattern.
    |
    */
    'pattern' => '^(.*){parameters}\.(jpg|jpeg|png|gif|JPG|JPEG|PNG|GIF)$',

    /*
    |--------------------------------------------------------------------------
    | URL parameter
    |--------------------------------------------------------------------------
    |
    | The URL parameter that will be appended to your imgso filename containing
    | all the options for imgso manipulation. You have to put {options} where
    | you want options to be placed. Keep in mind that this parameter is used
    | in an url so all characters should be URL safe.
    |
    | Default: -imgso({options})
    |
    | Example: /uploads/photo-imgso(300x300-grayscale).jpg
    |
    */
    'url_parameter' => '-imgso({options})',

    /*
    |--------------------------------------------------------------------------
    | URL parameter separator
    |--------------------------------------------------------------------------
    |
    | The URL parameter separator is used to build the parameters string
    | that will replace {options} in url_parameter
    |
    | Default: -
    |
    | Example: /uploads/photo-imgso(300x300-grayscale).jpg
    |
    */
    'url_parameter_separator' => '-',

    /*
    |--------------------------------------------------------------------------
    | Serve imgso
    |--------------------------------------------------------------------------
    |
    | If true, a route will be added to catch imgso containing the
    | URL parameter above.
    |
    */
    'serve' => true,

    /*
    |--------------------------------------------------------------------------
    | Serve route
    |--------------------------------------------------------------------------
    |
    | If you want to restrict the route to a specific domain.
    |
    */
    'serve_domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Serve route
    |--------------------------------------------------------------------------
    |
    | The route where imgso are served
    |
    */
    'serve_route' => '{imgso_pattern}',

    /*
    |--------------------------------------------------------------------------
    | Serve custom Filters only
    |--------------------------------------------------------------------------
    |
    | Restrict options in url to custom filters only. This prevent direct
    | manipulation of the imgso.
    |
    */
    'serve_custom_filters_only' => false,
    
    /*
    |--------------------------------------------------------------------------
    | Serve expires
    |--------------------------------------------------------------------------
    |
    | The expires headers that are sent when sending imgso.
    |
    */
    'serve_expires' => (3600*24*31),

    /*
    |--------------------------------------------------------------------------
    | Write imgso
    |--------------------------------------------------------------------------
    |
    | When serving an imgso, write the manipulated imgso in the same directory
    | as the original imgso so the next request will serve this static file
    |
    */
    'write_imgso' => false,

    /*
    |--------------------------------------------------------------------------
    | Write path
    |--------------------------------------------------------------------------
    |
    | By default, the manipulated imgsos are saved in the same path as the
    | as the original imgso, you can override this path here
    |
    */
    'write_path' => null,
    
    /*
    |--------------------------------------------------------------------------
    | Proxy
    |--------------------------------------------------------------------------
    |
    | This enable or disable the proxy route
    |
    */
    'proxy' => false,
    
    /*
    |--------------------------------------------------------------------------
    | Proxy expires
    |--------------------------------------------------------------------------
    |
    | The expires headers that are sent when proxying imgso. Defaults to 
    | serve_expires
    |
    */
    'proxy_expires' => null,

    /*
    |--------------------------------------------------------------------------
    | Proxy route
    |--------------------------------------------------------------------------
    |
    | The route that will be used to serve proxied imgso
    |
    */
    'proxy_route' => '{imgso_proxy_pattern}',
    
    

    /*
    |--------------------------------------------------------------------------
    | Proxy route pattern
    |--------------------------------------------------------------------------
    |
    | The proxy route pattern that will be available as `imgso_proxy_pattern`.
    | If the value is null, the default imgso pattern will be used.
    |
    */
    'proxy_route_pattern' => null,

    /*
    |--------------------------------------------------------------------------
    | Proxy route domain
    |--------------------------------------------------------------------------
    |
    | If you wind to bind your route to a specific domain.
    |
    */
    'proxy_route_domain' => null,
    
    /*
    |--------------------------------------------------------------------------
    | Proxy filesystem
    |--------------------------------------------------------------------------
    |
    | The filesystem from which the file will be proxied
    |
    */
    'proxy_filesystem' => 'cloud',
    
    /*
    |--------------------------------------------------------------------------
    | Proxy temporary directory
    |--------------------------------------------------------------------------
    |
    | Write the manipulated imgso back to the file system
    |
    */
    'proxy_write_imgso' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Proxy cache
    |--------------------------------------------------------------------------
    |
    | Cache the response of the proxy on the local filesystem. The proxy will be
    | cached using the laravel cache driver.
    |
    */
    'proxy_cache' => true,
    
    /*
    |--------------------------------------------------------------------------
    | Proxy cache filesystem
    |--------------------------------------------------------------------------
    |
    | If you want the proxy to cache files on a filesystem instead of using the
    | cache driver.
    |
    */
    'proxy_cache_filesystem' => null,
    
    /*
    |--------------------------------------------------------------------------
    | Proxy cache expiration
    |--------------------------------------------------------------------------
    |
    | The number of minuts that a proxied imgso can stay in cache. If the value
    | is -1, the imgso is cached forever.
    |
    */
    'proxy_cache_expiration' => 60*24,
    
    /*
    |--------------------------------------------------------------------------
    | Proxy temporary path
    |--------------------------------------------------------------------------
    |
    | The temporary path where the manipulated file are saved.
    |
    */
    'proxy_tmp_path' => sys_get_temp_dir(),

);
