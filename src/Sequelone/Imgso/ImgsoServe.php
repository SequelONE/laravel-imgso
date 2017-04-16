<?php namespace Sequelone\Imgso;

use Sequelone\Imgso\Exception\FileMissingException;
use Sequelone\Imgso\Exception\Exception;

class ImgsoServe
{
    protected $imgso;

    protected $config = [];

    public function __construct($imgso, $config = [])
    {
        $this->imgso = $imgso;

        $this->config = array_merge([
            'custom_filters_only' => false,
            'write_imgso' => false,
            'write_path' => null,
            'quality' => 80,
            'options' => []
        ], $config);
    }

    public function response($path)
    {
        // Parse the current path
        $parsedPath = $this->imgso->parse($path, array(
            'custom_filters_only' => $this->config['custom_filters_only']
        ));
        $writePath = isset($this->config['write_path']) ? trim($this->config['write_path'], '/') : null;
        $parsedOptions = $parsedPath['options'];
        $imgsoPath = $parsedPath['path'];

        if ($writePath && strpos($imgsoPath, $writePath) === 0) {
            $imgsoPath = substr($imgsoPath, strlen($writePath)+1);
        }

        // See if the referenced file exists and is an imgso
        if (!($realPath = $this->imgso->getRealPath($imgsoPath))) {
            throw new FileMissingException('Imgso file missing');
        }

        // create the destination if it does not exist
        if ($this->config['write_imgso']) {
            // make sure the path is relative to the document root
            if (strpos($realPath, public_path()) === 0) {
                $imgsoPath = substr($realPath, strlen(public_path()));
            }
            $destinationFolder = $writePath ?: dirname($imgsoPath);
            $destinationFolder = public_path(trim($writePath, '/') . '/' . ltrim(dirname($imgsoPath), '/'));
            
            if (isset($writePath)) {
                \File::makeDirectory($destinationFolder, 0770, true, true);
            }

            // Make sure destination is writeable
            if (!is_writable(dirname($destinationFolder))) {
                throw new Exception('Destination is not writeable');
            }
        }


        // Merge all options with the following priority:
        // Options passed as an argument to the serve method
        // Options parsed from the URL
        // Default options
        $options = array_merge($parsedOptions, $this->config['options']);

        //Make the imgso
        $imgso = $this->imgso->make($imgsoPath, $options);

        //Write the imgso
        if ($this->config['write_imgso']) {
            $destinationPath = rtrim($destinationFolder, '/') . '/' . basename($path);
            $imgso->save($destinationPath);
        }

        //Get the imgso format
        $format = $this->imgso->format($realPath);

        //Get the imgso content
        $saveOptions = array();
        $quality = array_get($options, 'quality', $this->config['quality']);
        if ($format === 'jpeg') {
            $saveOptions['jpeg_quality'] = $quality;
        } elseif ($format === 'png') {
            $saveOptions['png_compression_level'] = round($quality / 100 * 9);
        }
        $content = $imgso->get($format, $saveOptions);

        //Create the response
        $mime = $this->imgso->getMimeFromFormat($format);
        $response = $this->createResponseFromContent($content);
        $response->header('Content-type', $mime);

        return $response;
    }
    
    protected function getResponseExpires()
    {
        return config('imgso.serve_expires', 3600*24*31);
    }
    
    protected function createResponseFromContent($content)
    {
        $expires = $this->getResponseExpires();
        $response = response()->make($content, 200);
        $response->header('Cache-control', 'max-age='.$expires.', public');
        $response->header('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + $expires));
        return $response;
    }
}
