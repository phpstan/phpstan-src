<?php

namespace Bug10498;

use const PHP_EOL;
use function printf;

/**
 * @template T of object
 */
abstract class A
{

	public static function foo(): void
	{
		if (static::class === B::class) {
			printf("I'm a %s" . PHP_EOL, B::class);
		}
	}

}

/**
 * @extends A<C>
 */
class B extends A
{

}

class C
{

}
