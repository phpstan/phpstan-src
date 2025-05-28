<?php declare(strict_types=1);

namespace NamedAttributesPhpversion;

use Exception;
use function PHPStan\debugScope;
use function PHPStan\dumpType;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	/** @return mixed[] */
	public function sayHello(): array|null
	{
		if(PHP_VERSION_ID >= 80400) {
		} else {
		}
		return [
			new Exception(previous: new Exception()),
		];
	}
}

class HelloWorld2
{
	/** @return mixed[] */
	public function sayHello(): array|null
	{
		return [
			PHP_VERSION_ID >= 80400 ? 1 : 0,
			new Exception(previous: new Exception()),
		];
	}
}
