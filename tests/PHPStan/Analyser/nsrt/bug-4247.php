<?php

namespace Bug4247;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @return static
	 */
	public function singleton() {
		assertType('static(Bug4247\HelloWorld)', SingletonLib::init(static::class));
		assertType('Bug4247\HelloWorld', SingletonLib::init(self::class));
	}
}

final class SingletonLib
{

	/**
	 * @template       TInit
	 * @param  class-string<TInit> $classname
	 * @return TInit
	 */
	public static function init($classname) {

	}

}
