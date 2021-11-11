<?php

namespace Bug5782;

class HelloWorld
{
	public static function sayHello(): void
	{

	}

	public function sayGoodbye(): void
	{

	}

}

function baz() {
	$closure1 = \Closure::fromCallable([\Bug5782\HelloWorld::class, 'sayHello']);
	$closure2 = \Closure::fromCallable('\Bug5782\HelloWorld::sayHello');
	$closure3 = \Closure::fromCallable([\Bug5782\HelloWorld::class, 'sayGoodbye']);
	// @todo this should also error on mixed type, but doesn't?
	// ConstantStringType::getCallableParametersAcceptors returns a MixedType
	// but that doesn't cause an error like ConstantArrayType.
	$closure4 = \Closure::fromCallable('Bug5782\HelloWorld::sayGoodbye');
}
