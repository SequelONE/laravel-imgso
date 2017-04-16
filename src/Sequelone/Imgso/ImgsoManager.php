<?php namespace Sequelone\Imgso;

use Sequelone\Imgso\Exception\Exception;
use Sequelone\Imgso\Exception\FileMissingException;
use Sequelone\Imgso\Exception\ParseException;
use Sequelone\Imgso\Exception\FormatException;

use Illuminate\Support\Manager;

use Imagine\Image\ImageInterface;
use Imagine\Image\Box;
use Imagine\Image\Point;

class ImgsoManager extends Manager
{

    /**
     * Default options
     *
     * @var array
     */
    protected $defaultOptions = array(
        'width' => null,
        'height' => null,
        'quality' => 80,
        'filters' => array()
    );

    /**
     * All of the custom filters.
     *
     * @var array
     */
    protected $filters = array();

    /**
     * Return an URL to process the imgso
     *
     * @param  string  $src
     * @param  int     $width
     * @param  int     $height
     * @param  array   $options
     * @return string
     */
    public function url($src, $width = null, $height = null, $options = array())
    {

        // Don't allow empty strings
        if (empty($src)) {
            return;
        }

        // Extract the path from a URL if a URL was provided instead of a path
        $src = parse_url($src, PHP_URL_PATH);

        //If width parameter is an array, use it as options
        if (is_array($width)) {
            $options = $width;
            $width = null;
            $height = null;
        }
        
        $config = $this->app['config'];
        $url_parameter = isset($options['url_parameter']) ? $options['url_parameter']:$config['imgso.url_parameter'];
        $url_parameter_separator = isset($options['url_parameter_separator']) ? $options['url_parameter_separator']:$config['imgso.url_parameter_separator'];
        unset($options['url_parameter'],$options['url_parameter_separator']);

        //Get size
        if (isset($options['width'])) {
            $width = $options['width'];
        }
        if (isset($options['height'])) {
            $height = $options['height'];
        }
        if (empty($width)) {
            $width = '_';
        }
        if (empty($height)) {
            $height = '_';
        }

        // Produce the parameter parts
        $params = array();

        //Add size only if present
        if ($width != '_' || $height != '_') {
            $params[] = $width.'x'.$height;
        }

        // Build options. If the key as no value or is equal to
        // true, only the key is added.
        if ($options && is_array($options)) {
            foreach ($options as $key => $val) {
                if (is_numeric($key)) {
                    $params[] = $val;
                } elseif ($val === true || $val === null) {
                    $params[] = $key;
                } elseif (is_array($val)) {
                    $params[] = $key.'('.implode(',', $val).')';
                } else {
                    $params[] = $key.'('.$val.')';
                }
            }
        }

        //Create the url parameter
        $params = implode($url_parameter_separator, $params);
        $parameter = str_replace('{options}', $params, $url_parameter);

        // Break the path apart and put back together again
        $parts = pathinfo($src);
        $host = isset($options['host']) ? $options['host']:$this->app['config']['imgso.host'];
        $dir = trim($parts['dirname'], '/');

        $path = array();
        $path[] = rtrim($host, '/');

        if ($prefix = $this->app['config']->get('imgso.write_path')) {
            $path[] = trim($prefix, '/');
        }

        if (!empty($dir)) {
            $path[] = $dir;
        }

        $filename = array();
        $filename[] = $parts['filename'].$parameter;
        if (!empty($parts['extension'])) {
            $filename[] = $parts['extension'];
        }
        $path[] = implode('.', $filename);

        return implode('/', $path);

    }

    /**
     * Make an imgso and apply options
     *
     * @param  string    $path The path of the imgso
     * @param  array    $options The manipulations to apply on the imgso
     * @return ImgsoInterface
     */
    public function make($path, $options = array())
    {
        //Get app config
        $config = $this->app['config'];

        // See if the referenced file exists and is an imgso
        if (!($path = $this->getRealPath($path))) {
            throw new FileMissingException('Imgso file missing');
        }

        // Get imgso format
        $format = $this->format($path);
        if (!$format) {
            throw new FormatException('Imgso format is not supported');
        }

        // Check if all filters exists
        if (isset($options['filters']) && sizeof($options['filters'])) {
            foreach ($options['filters'] as $filter) {
                $filter = (array)$filter;
                $key = $filter[0];
                if (!$this->filters[$key]) {
                    throw new Exception('Custom filter "'.$key.'" doesn\'t exists.');
                }
            }
        }

        // Increase memory limit, cause some imgsos require a lot to resize
        if ($config->get('imgso.memory_limit')) {
            ini_set('memory_limit', $config->get('imgso.memory_limit'));
        }

        //Open the imgso
        $imgso = $this->open($path);

        //Merge options with the default
        $options = array_merge($this->defaultOptions, $options);

        // Apply the custom filter on the imgso. Replace the
        // current imgso with the return value.
        if (isset($options['filters']) && sizeof($options['filters'])) {
            foreach ($options['filters'] as $filter) {
                $arguments = (array)$filter;
                array_unshift($arguments, $imgso);

                $imgso = call_user_func_array(array($this,'applyCustomFilter'), $arguments);
            }
        }

        // Resize only if one or both width and height values are set.
        if ($options['width'] !== null || $options['height'] !== null) {
            $crop = isset($options['crop']) ? $options['crop']:false;

            $imgso = $this->thumbnail($imgso, $options['width'], $options['height'], $crop);
        }

        // Apply built-in filters by checking fi a method $this->filterName
        // exists. Also if the value of the option is false, the filter
        // is ignored.
        foreach ($options as $key => $arguments) {
            $method = 'filter'.ucfirst($key);

            if ($arguments !== false && method_exists($this, $method)) {
                $arguments = (array)$arguments;
                array_unshift($arguments, $imgso);

                $imgso = call_user_func_array(array($this, $method), $arguments);
            }
        }



        return $imgso;
    }

    /**
     * Serve an imgso from an url
     *
     * @param  string    $path
     * @param  array    $config
     * @return Illuminate\Support\Facades\Response
     */
    public function serve($path, $config = array())
    {
        //Use user supplied quality or the config value
        $quality = array_get($config, 'quality', $this->app['config']['imgso.quality']);
        //if nothing works fallback to the hardcoded value
        $quality = $quality ?: $this->defaultOptions['quality'];

        //Merge config with defaults
        $config = array_merge(array(
            'quality' => $quality,
            'custom_filters_only' => $this->app['config']['imgso.serve_custom_filters_only'],
            'write_imgso' => $this->app['config']['imgso.write_imgso'],
            'write_path' => $this->app['config']['imgso.write_path']
        ), $config);

        $serve = new ImgsoServe($this, $config);
        
        return $serve->response($path);
    }
    
    /**
     * Proxy an imgso
     *
     * @param  string    $path
     * @param  array    $config
     * @return Illuminate\Support\Facades\Response
     */
    public function proxy($path, $config = array())
    {
        //Merge config with defaults
        $config = array_merge(array(
            'tmp_path' => $this->app['config']['imgso.proxy_tmp_path'],
            'filesystem' => $this->app['config']['imgso.proxy_filesystem'],
            'cache' => $this->app['config']['imgso.proxy_cache'],
            'cache_expiration' => $this->app['config']['imgso.proxy_cache_expiration'],
            'write_imgso' => $this->app['config']['imgso.proxy_write_imgso'],
            'cache_filesystem' => $this->app['config']['imgso.proxy_cache_filesystem']
        ), $config);
        
        $serve = new ImgsoProxy($this, $config);
        return $serve->response($path);
    }

    /**
     * Register a custom filter.
     *
     * @param  string            $name The name of the filter
     * @param  Closure|string    $filter
     * @return void
     */
    public function filter($name, $filter)
    {
        $this->filters[$name] = $filter;
    }

    /**
     * Create a thumbnail from an imgso
     *
     * @param  ImgsoInterface|string    $imgso An imgso instance or the path to an imgso
     * @param  int                        $width
     * @return ImgsoInterface
     */
    public function thumbnail($imgso, $width = null, $height = null, $crop = true)
    {
        //If $imgso is a path, open it
        if (is_string($imgso)) {
            $imgso = $this->open($imgso);
        }

        //Get new size
        $imgsoSize = $imgso->getSize();
        $newWidth = $width === null ? $imgsoSize->getWidth():$width;
        $newHeight = $height === null ? $imgsoSize->getHeight():$height;
        $size = new Box($newWidth, $newHeight);
        
        $ratios = array(
            $size->getWidth() / $imgsoSize->getWidth(),
            $size->getHeight() / $imgsoSize->getHeight()
        );

        $thumbnail = $imgso->copy();

        $thumbnail->usePalette($imgso->palette());
        $thumbnail->strip();

        if (!$crop) {
            $ratio = min($ratios);
        } else {
            $ratio = max($ratios);
        }

        if ($crop) {
            
            $imgsoSize = $thumbnail->getSize()->scale($ratio);
            $thumbnail->resize($imgsoSize);
            
            $x = max(0, round(($imgsoSize->getWidth() - $size->getWidth()) / 2));
            $y = max(0, round(($imgsoSize->getHeight() - $size->getHeight()) / 2));
            
            $cropPositions = $this->getCropPositions($crop);
            
            if ($cropPositions[0] === 'top') {
                $y = 0;
            } elseif ($cropPositions[0] === 'bottom') {
                $y = $imgsoSize->getHeight() - $size->getHeight();
            }
            
            if ($cropPositions[1] === 'left') {
                $x = 0;
            } elseif ($cropPositions[1] === 'right') {
                $x = $imgsoSize->getWidth() - $size->getWidth();
            }
            
            $point = new Point($x, $y);
            
            $thumbnail->crop($point, $size);
        } else {
            if (!$imgsoSize->contains($size)) {
                $imgsoSize = $imgsoSize->scale($ratio);
                $thumbnail->resize($imgsoSize);
            } else {
                $imgsoSize = $thumbnail->getSize()->scale($ratio);
                $thumbnail->resize($imgsoSize);
            }
        }

        //Create the thumbnail
        return $thumbnail;
    }

    /**
     * Get the format of an imgso
     *
     * @param  string    $path The path to an imgso
     * @return ImgsoInterface
     */
    public function format($path)
    {

        $format = @exif_imgsotype($path);
        switch ($format) {
            case IMAGETYPE_GIF:
                return 'gif';
            break;
            case IMAGETYPE_JPEG:
                return 'jpeg';
            break;
            case IMAGETYPE_PNG:
                return 'png';
            break;
        }

        return null;
    }

    /**
     * Delete a file and all manipulated files
     *
     * @param  string    $path The path to an imgso
     * @return void
     */
    public function delete($path)
    {
        $files = $this->getFiles($path);

        foreach ($files as $file) {
            if (!unlink($file)) {
                throw new Exception('Unlink failed: '.$file);
            }
        }
    }

    /**
     * Delete all manipulated files
     *
     * @param  string    $path The path to an imgso
     * @return void
     */
    public function deleteManipulated($path)
    {
        $files = $this->getFiles($path, false);

        foreach ($files as $file) {
            if (!unlink($file)) {
                throw new Exception('Unlink failed: '.$file);
            }
        }
    }

    /**
     * Get the URL pattern
     *
     * @return string
     */
    public function pattern($parameter = null, $pattern = null)
    {
        //Replace the {options} with the options regular expression
        $config = $this->app['config'];
        $parameter = !isset($parameter) ? $config['imgso.url_parameter']:$parameter;
        $parameter = preg_replace('/\\\{\s*options\s*\\\}/', '([0-9a-zA-Z\(\),\-/._]+?)?', preg_quote($parameter));
        
        if(!$pattern)
        {
            $pattern = $config->get('imgso.pattern', '^(.*){parameters}\.(jpg|jpeg|png|gif|JPG|JPEG|PNG|GIF)$');
        }
        $pattern = preg_replace('/\{\s*parameters\s*\}/', $parameter, $pattern);

        return $pattern;
    }

    /**
     * Parse the path for the original path of the imgso and options
     *
     * @param  string    $path A path to parse
     * @param  array    $config Configuration options for the parsing
     * @return array
     */
    public function parse($path, $config = array())
    {
        //Default config
        $config = array_merge(array(
            'custom_filters_only' => false,
            'url_parameter' => null,
            'url_parameter_separator' => $this->app['config']['imgso.url_parameter_separator']
        ), $config);

        $parsedOptions = array();
        if (preg_match('#'.$this->pattern($config['url_parameter']).'#i', $path, $matches)) {
            //Get path and options
            $path = $matches[1].'.'.$matches[3];
            $pathOptions = $matches[2];

            // Parse options from path
            $parsedOptions = $this->parseOptions($pathOptions, $config);
        }

        return array(
            'path' => $path,
            'options' => $parsedOptions
        );
    }

    /**
     * Parse options from url string
     *
     * @param  string    $option_path The path contaning all the options
     * @param  array    $config Configuration options for the parsing
     * @return array
     */
    protected function parseOptions($option_path, $config = array())
    {

        //Default config
        $config = array_merge(array(
            'custom_filters_only' => false,
            'url_parameter_separator' => $this->app['config']['imgso.url_parameter_separator']
        ), $config);

        $options = array();

        // These will look like (depends on the url_parameter_separator): "-colorize(CC0000)-greyscale"
        $option_path_parts = explode($config['url_parameter_separator'], $option_path);

        // Loop through the params and make the options key value pairs
        foreach ($option_path_parts as $option) {
            //Check if the option is a size or is properly formatted
            if (!$config['custom_filters_only'] && preg_match('#([0-9]+|_)x([0-9]+|_)#i', $option, $matches)) {
                $options['width'] = $matches[1] === '_' ? null:(int)$matches[1];
                $options['height'] = $matches[2] === '_' ? null:(int)$matches[2];
                continue;
            } elseif (!preg_match('#(\w+)(?:\(([\w,.]+)\))?#i', $option, $matches)) {
                continue;
            }

            //Check if the key is valid
            $key = $matches[1];
            if (!$this->isValidOption($key)) {
                throw new ParseException('The option key "'.$key.'" does not exists.');
            }

            // If the option is a custom filter, check if it's a closure or an array.
            // If it's an array, merge it with options
            if (isset($this->filters[$key])) {
                if (is_object($this->filters[$key]) && is_callable($this->filters[$key])) {
                    $arguments = isset($matches[2]) ? explode(',', $matches[2]):array();
                    array_unshift($arguments, $key);
                    $options['filters'][] = $arguments;
                } elseif (is_array($this->filters[$key])) {
                    $options = array_merge($options, $this->filters[$key]);
                }
            } elseif (!$config['custom_filters_only']) {
                if (isset($matches[2])) {
                    $options[$key] = strpos($matches[2], ',') === true ? explode(',', $matches[2]):$matches[2];
                } else {
                    $options[$key] = true;
                }
            } else {
                throw new ParseException('The option key "'.$key.'" does not exists.');
            }
        }

        // Merge the options with defaults
        return $options;
    }

    /**
     * Check if an option key is valid by checking if a
     * $this->filterName() method is present or if a custom filter
     * is registered.
     *
     * @param  string  $key Option key to check
     * @return boolean
     */
    protected function isValidOption($key)
    {
        if (in_array($key, array('crop','width','height'))) {
            return true;
        }

        $method = 'filter'.ucfirst($key);
        if (method_exists($this, $method) || isset($this->filters[$key])) {
            return true;
        }
        return false;
    }

    /**
     * Get real path
     *
     * @param  string    $path Path to an original imgso
     * @return string
     */
    public function getRealPath($path)
    {
        if (is_file(realpath($path))) {
            return realpath($path);
        }
        
        //Get directories
        $dirs = $this->app['config']['imgso.src_dirs'];
        if ($this->app['config']['imgso.write_path']) {
            $dirs[] = $this->app['config']['imgso.write_path'];
        }

        // Loop through all the directories files may be uploaded to
        foreach ($dirs as $dir) {
            $dir = rtrim($dir, '/');

            // Check that directory exists
            if (!is_dir($dir)) {
                continue;
            }

            // Look for the imgso in the directory
            $src = realpath($dir.'/'.ltrim($path, '/'));
            if (is_file($src)) {
                return $src;
            }
        }

        // None found
        return false;
    }

    /**
     * Get all files (including manipulated imgsos)
     *
     * @param  string    $path Path to an original imgso
     * @return array
     */
    protected function getFiles($path, $withOriginal = true)
    {

        $imgsos = array();

        //Check path
        $path = urldecode($path);
        if (!($path = $this->getRealPath($path))) {
            return $imgsos;
        }

        // Add the source imgso to the list
        if ($withOriginal) {
            $imgsos[] = $path;
        }

        // Loop through the contents of the source and write directory and get
        // all files that match the pattern
        $parts = pathinfo($path);
        $dirs = [$parts['dirname']];
        $dirs = [$parts['dirname']];
        if ($this->app['config']['imgso.write_path']) {
            $dirs[] = $this->app['config']['imgso.write_path'];
        }
        foreach ($dirs as $directory) {
            $files = scandir($directory);
            foreach ($files as $file) {
                if (strpos($file, $parts['filename']) === false || !preg_match('#'.$this->pattern().'#', $file)) {
                    continue;
                }
                $imgsos[] = $directory.'/'.$file;
            }
        }
        
        // Return the list
        return $imgsos;
    }

    /**
     * Apply a custom filter or an imgso
     *
     * @param  ImgsoInterface    $imgso An imgso instance
     * @param  string            $name The filter name
     * @return ImgsoInterface|array
     */
    protected function applyCustomFilter(ImgsoInterface $imgso, $name)
    {
        //Get all arguments following $name and add $imgso as the first
        //arguments then call the filter closure
        $arguments = array_slice(func_get_args(), 2);
        array_unshift($arguments, $imgso);
        $return = call_user_func_array($this->filters[$name], $arguments);

        // If the return value is an instance of ImgsoInterface,
        // replace the current imgso with it.
        if ($return instanceof ImgsoInterface) {
            $imgso = $return;
        }

        return $imgso;
    }

    /**
     * Apply rotate filter
     *
     * @param  ImgsoInterface    $imgso An imgso instance
     * @param  float            $degree The rotation degree
     * @return void
     */
    protected function filterRotate(ImgsoInterface $imgso, $degree)
    {
        return $imgso->rotate($degree);
    }

    /**
     * Apply grayscale filter
     *
     * @param  ImgsoInterface    $imgso An imgso instance
     * @return void
     */
    protected function filterGrayscale(ImgsoInterface $imgso)
    {
        $imgso->effects()->grayscale();
        return $imgso;
    }

    /**
     * Apply negative filter
     *
     * @param  ImgsoInterface    $imgso An imgso instance
     * @return void
     */
    protected function filterNegative(ImgsoInterface $imgso)
    {
        $imgso->effects()->negative();
        return $imgso;
    }

    /**
     * Apply gamma filter
     *
     * @param  ImgsoInterface    $imgso An imgso instance
     * @param  float            $gamma The gamma value
     * @return void
     */
    protected function filterGamma(ImgsoInterface $imgso, $gamma)
    {
        $imgso->effects()->gamma($gamma);
        return $imgso;
    }

    /**
     * Apply blur filter
     *
     * @param  ImgsoInterface    $imgso An imgso instance
     * @param  int            $blur The amount of blur
     * @return void
     */
    protected function filterBlur(ImgsoInterface $imgso, $blur)
    {
        $imgso->effects()->blur($blur);
        return $imgso;
    }

    /**
     * Apply colorize filter
     *
     * @param  ImgsoInterface    $imgso An imgso instance
     * @param  string            $color The hex value of the color
     * @return void
     */
    protected function filterColorize(ImgsoInterface $imgso, $color)
    {
        $palettes = ['RGB','CMYK'];
        $parts = explode(',', $color);
        $color = $parts[0];
        if(isset($parts[1]) && in_array(strtoupper($parts[1]), $palettes))
        {
            $className = '\\Imagine\\Imgso\\Palette\\'.strtoupper($parts[1]);
            $palette = new $className();
        }
        else
        {
            $palette = $imgso->palette();
        }
        $color = $palette->color($color);
        $imgso->effects()->colorize($color);
        return $imgso;
    }

    /**
     * Apply  interlace filter
     *
     * @param  ImgsoInterface    $imgso An imgso instance
     * @return void
     */
    protected function filterInterlace(ImgsoInterface $imgso)
    {
        $imgso->interlace(ImgsoInterface::INTERLACE_LINE);
        return $imgso;
    }

    /**
     * Get mime type from imgso format
     *
     * @return string
     */
    public function getMimeFromFormat($format)
    {

        switch ($format) {
            case 'gif':
                return 'imgso/gif';
            break;
            case 'jpg':
            case 'jpeg':
                return 'imgso/jpeg';
            break;
            case 'png':
                return 'imgso/png';
            break;
        }

        return null;
    }
    
    /**
     * Return crop positions from the crop parameter
     *
     * @return array
     */
    protected function getCropPositions($crop)
    {
        $crop = $crop === true ? 'center':$crop;
        
        $cropPositions = explode('_', $crop);
        if (sizeof($cropPositions) === 1) {
            if ($cropPositions[0] === 'top' || $cropPositions[0] === 'bottom' || $cropPositions[0] === 'center') {
                $cropPositions[] = 'center';
            } elseif ($cropPositions[0] === 'left' || $cropPositions[0] === 'right') {
                array_unshift($cropPositions, 'center');
            }
        }
        
        return $cropPositions;
    }

    /**
     * Create an instance of the Imagine Gd driver.
     *
     * @return \Imagine\Gd\Imagine
     */
    protected function createGdDriver()
    {
        return new \Imagine\Gd\Imagine();
    }

    /**
     * Create an instance of the Imagine Imagick driver.
     *
     * @return \Imagine\Imagick\Imagine
     */
    protected function createImagickDriver()
    {
        return new \Imagine\Imagick\Imagine();
    }

    /**
     * Create an instance of the Imagine Gmagick driver.
     *
     * @return \Imagine\Gmagick\Imagine
     */
    protected function createGmagickDriver()
    {
        return new \Imagine\Gmagick\Imagine();
    }

    /**
     * Get the default imgso driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['imgso.driver'];
    }

    /**
     * Set the default imgso driver name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['imgso.driver'] = $name;
    }
}
