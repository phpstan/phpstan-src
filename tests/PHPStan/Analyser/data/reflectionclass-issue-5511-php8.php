<?php declare(strict_types=1); // onlyif PHP_VERSION_ID >= 80000

namespace Issue5511;

use function PHPStan\Testing\assertType;

#[\Attribute]
class Abc
{
}

/**
 * @param string $str
 * @param class-string $className
 * @param class-string<Abc> $genericClassName
 */
function testGetAttributes(
	\ReflectionClass $reflectionClass,
	\ReflectionMethod $reflectionMethod,
	\ReflectionParameter $reflectionParameter,
	\ReflectionProperty $reflectionProperty,
	\ReflectionClassConstant $reflectionClassConstant,
	\ReflectionFunction $reflectionFunction,
	string $str,
	string $className,
	string $genericClassName
): void
{
	$classAll = $reflectionClass->getAttributes();
	$classAbc1 = $reflectionClass->getAttributes(Abc::class);
	$classAbc2 = $reflectionClass->getAttributes(Abc::class, \ReflectionAttribute::IS_INSTANCEOF);
	$classGCN = $reflectionClass->getAttributes($genericClassName);
	$classCN = $reflectionClass->getAttributes($className);
	$classStr = $reflectionClass->getAttributes($str);
	$classNonsense = $reflectionClass->getAttributes("some random string");

	assertType('array<ReflectionAttribute<object>>', $classAll);
	assertType('array<ReflectionAttribute<Issue5511\Abc>>', $classAbc1);
	assertType('array<ReflectionAttribute<Issue5511\Abc>>', $classAbc2);
	assertType('array<ReflectionAttribute<Issue5511\Abc>>', $classGCN);
	assertType('array<ReflectionAttribute<object>>', $classCN);
	assertType('array<ReflectionAttribute<object>>', $classStr);
	assertType('array<ReflectionAttribute<*ERROR*>>', $classNonsense);

	$methodAll = $reflectionMethod->getAttributes();
	$methodAbc = $reflectionMethod->getAttributes(Abc::class);
	assertType('array<ReflectionAttribute<object>>', $methodAll);
	assertType('array<ReflectionAttribute<Issue5511\Abc>>', $methodAbc);

	$paramAll = $reflectionParameter->getAttributes();
	$paramAbc = $reflectionParameter->getAttributes(Abc::class);
	assertType('array<ReflectionAttribute<object>>', $paramAll);
	assertType('array<ReflectionAttribute<Issue5511\Abc>>', $paramAbc);

	$propAll = $reflectionProperty->getAttributes();
	$propAbc = $reflectionProperty->getAttributes(Abc::class);
	assertType('array<ReflectionAttribute<object>>', $propAll);
	assertType('array<ReflectionAttribute<Issue5511\Abc>>', $propAbc);

	$constAll = $reflectionClassConstant->getAttributes();
	$constAbc = $reflectionClassConstant->getAttributes(Abc::class);
	assertType('array<ReflectionAttribute<object>>', $constAll);
	assertType('array<ReflectionAttribute<Issue5511\Abc>>', $constAbc);

	$funcAll = $reflectionFunction->getAttributes();
	$funcAbc = $reflectionFunction->getAttributes(Abc::class);
	assertType('array<ReflectionAttribute<object>>', $funcAll);
	assertType('array<ReflectionAttribute<Issue5511\Abc>>', $funcAbc);
}

/**
 * @param \ReflectionAttribute<Abc> $ra
 */
function testNewInstance(\ReflectionAttribute $ra): void
{
	assertType('ReflectionAttribute<Issue5511\\Abc>', $ra);
	$abc = $ra->newInstance();
	assertType(Abc::class, $abc);
}
