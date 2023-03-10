<?php

namespace Bug8408;

/**
 * @template TTemplate
 */
class HelloWorld
{
	/**
	 * @return (TTemplate is string ? never : int)
	 */
	public function test()
	{
		return 1;
	}
}
