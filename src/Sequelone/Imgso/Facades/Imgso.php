<?php namespace Sequelone\Imgso\Facades;

use Illuminate\Support\Facades\Facade;

class Imgso extends Facade
{

	protected static function getFacadeAccessor()
	{
		return 'imgso';
	}

}