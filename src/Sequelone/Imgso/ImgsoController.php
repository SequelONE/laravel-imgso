<?php namespace Sequelone\Imgso;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;

use Sequelone\Imgso\Exception\Exception;
use Sequelone\Imgso\Exception\FileMissingException;
use Sequelone\Imgso\Exception\ParseException;

use App;
use Imgso;

class ImgsoController extends BaseController
{

    use DispatchesJobs, ValidatesRequests;
    
    public function serve($path)
    {
        // Serve the imgso response. If there is a file missing
        // exception or parse exception, throw a 404.
        try {
            return app('imgso')->serve($path);
        } catch (ParseException $e) {
            return abort(404);
        } catch (FileMissingException $e) {
            return abort(404);
        } catch (Exception $e) {
            return abort(500);
        }
    }
    
    public function proxy($path)
    {
        // Serve the imgso response from proxy. If there is a file missing
        // exception or parse exception, throw a 404.
        try {
            return app('imgso')->proxy($path);
        } catch (ParseException $e) {
            return abort(404);
        } catch (FileMissingException $e) {
            return abort(404);
        } catch (Exception $e) {
            return abort(500);
        }
    }
}
