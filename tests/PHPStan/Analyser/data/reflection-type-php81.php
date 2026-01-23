<?php

namespace ReflectionTypeTestPhp81;

use function PHPStan\Testing\assertType;

function test(
	\ReflectionProperty $reflectionProperty,
	\ReflectionFunctionAbstract $reflectionFunctionAbstract,
	\ReflectionParameter $reflectionParameter
){
	assertType('ReflectionType|null', $reflectionProperty->getType());
	assertType('ReflectionType|null', $reflectionFunctionAbstract->getReturnType());
	assertType('ReflectionType|null', $reflectionFunctionAbstract->getTentativeReturnType());
	assertType('ReflectionType|null', $reflectionParameter->getType());

	if ($reflectionProperty->hasType()) {
		assertType('ReflectionType', $reflectionProperty->getType());
	} else {
		assertType('null', $reflectionProperty->getType());
	}

	if ($reflectionFunctionAbstract->hasReturnType()) {
		assertType('ReflectionType', $reflectionFunctionAbstract->getReturnType());
	} else {
		assertType('null', $reflectionFunctionAbstract->getReturnType());
	}

	if ($reflectionFunctionAbstract->hasTentativeReturnType()) {
		assertType('ReflectionType', $reflectionFunctionAbstract->getTentativeReturnType());
	} else {
		assertType('null', $reflectionFunctionAbstract->getTentativeReturnType());
	}

	if ($reflectionParameter->hasType()) {
		assertType('ReflectionType', $reflectionParameter->getType());
	} else {
		assertType('null', $reflectionParameter->getType());
	}
}
