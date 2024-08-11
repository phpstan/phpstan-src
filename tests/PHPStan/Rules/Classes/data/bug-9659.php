<?php // lint >=8.0

namespace Bug9659;

class HelloWorld
{
	/**
	 * @param float|null $timeout
	 */
	public function __construct($timeout = null)
	{
		var_dump($timeout);
	}
}

new HelloWorld(20); // working
new HelloWorld(random_int(20, 80)); // broken
