<?php // lint >= 8.0

declare(strict_types = 1);

namespace NamedArgumentsPhpversion;

use Exception;

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

class HelloWorld3
{
	/** @return mixed[] */
	public function sayHello(): array|null
	{
		return [
			PHP_VERSION_ID >= 70400 ? 1 : 0,
			new Exception(previous: new Exception()),
		];
	}
}

class HelloWorld4
{
	/** @return mixed[] */
	public function sayHello(): array|null
	{
		return [
			PHP_VERSION_ID < 80000 ? 1 : 0,
			new Exception(previous: new Exception()),
		];
	}
}
