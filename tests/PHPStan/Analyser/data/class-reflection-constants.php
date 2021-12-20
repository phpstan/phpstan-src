<?php

namespace ClassReflectionConstants;

use function PHPStan\Testing\assertType;

class HelloWorld {
	const A_CONSTANT = 'Constant';
	const A_ARRAY = ['Constant', 987];

	public const HELLO = 'Hello';
	protected const WORLD = 'World';
	private const SECRET = 123;

	public function constantReflection() {
		$cr = new \ReflectionClass(HelloWorld::class);
		assertType("'Constant'", $cr->getConstant('A_CONSTANT'));
		assertType("array{'Constant', 987}", $cr->getConstant('A_ARRAY'));
		assertType("'Hello'", $cr->getConstant('HELLO'));
		assertType("'World'", $cr->getConstant('WORLD'));
		assertType('123', $cr->getConstant('SECRET'));
	}
}
