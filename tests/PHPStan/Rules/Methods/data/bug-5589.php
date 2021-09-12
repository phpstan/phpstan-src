<?php

namespace Bug5589;

/**
 * @template-covariant T of object
 * @extends \ReflectionClass<T>
 */
class HelloWorld extends \ReflectionClass
{
	public static function export($argument, $return = false)
	{
		return '';
	}
}
