<?php

namespace ReflectionTypeTest;

use function PHPStan\Testing\assertType;

function test(
	\ReflectionProperty $reflectionProperty,
	\ReflectionFunctionAbstract $reflectionFunctionAbstract,
	\ReflectionParameter $reflectionParameter
){
	assertType('ReflectionType|nulll', $reflectionProperty->getType());
	assertType('ReflectionType|null', $reflectionFunctionAbstract->getReturnType());
	assertType('ReflectionType|null', $reflectionParameter->getType());
}
