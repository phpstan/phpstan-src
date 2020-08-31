<?php declare(strict_types = 1);

namespace Bug3782;

use function PHPStan\Analyser\assertType;

class HelloWorld
{
	/** @param mixed[] $data */
	public function sayHello(array $data): void
	{
		foreach($data as $key => $value){
			$this[$key] = $value;
			assertType('$this(Bug3782\\HelloWorld)', $this);
		}
	}

	public static function sayHello2(array $data): void
	{
		$var = new HelloWorld();
		foreach ($data as $key => $value){
			$var[$key] = $value;
			assertType('Bug3782\\HelloWorld', $var);
		}
	}
}
