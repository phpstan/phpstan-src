<?php

namespace Bug8980;

function doFoo(): void {
	$undefined_curl_functions = array_filter(
		[
			'curl_multi_add_handle',
			'curl_multi_exec',
			'curl_multi_init',
		],
		static function ($function_name) {
			return !function_exists($function_name);
		}
	);
}


class HelloWorld
{
	/**
	 * @var HelloWorld
	 */
	private static $instance = null;

	public static function getInstance(): HelloWorld
	{
		if (self::$instance === null) {
			self::$instance = new HelloWorld();
		}

		return self::$instance;
	}

	public function sayHello(): void
	{
		echo 'Hello World!';
	}

	function doFoo(): void {
		$helloWorld = method_exists(HelloWorld::class, 'getInstance') ? HelloWorld::getInstance() : new HelloWorld();
		$helloWorld->sayHello();
	}
}

