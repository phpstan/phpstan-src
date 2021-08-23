<?php declare(strict_types=1);

namespace Issue5511;

use function PHPStan\Testing\assertType;

#[\Attribute]
class Abc
{
}

#[Abc]
class X
{
}

/**
 * @param string $str
 * @param class-string $className
 * @param class-string<Abc> $genericClassName
 */
function testGetAttributes(string $str, string $className, string $genericClassName): void
{
	$class = new \ReflectionClass(X::class);

	$attrsAll = $class->getAttributes();
	$attrsAbc1 = $class->getAttributes(Abc::class);
	$attrsAbc2 = $class->getAttributes(Abc::class, \ReflectionAttribute::IS_INSTANCEOF);
	$attrsGCN = $class->getAttributes($genericClassName);
	$attrsCN = $class->getAttributes($className);
	$attrsStr = $class->getAttributes($str);
	$attrsNonsense = $class->getAttributes("some random string");

	assertType('array<ReflectionAttribute<object>>', $attrsAll);
	assertType('array<ReflectionAttribute<Issue5511\Abc>>', $attrsAbc1);
	assertType('array<ReflectionAttribute<Issue5511\Abc>>', $attrsAbc2);
	assertType('array<ReflectionAttribute<Issue5511\Abc>>', $attrsGCN);
	assertType('array<ReflectionAttribute<object>>', $attrsCN);
	assertType('array<ReflectionAttribute<object>>', $attrsStr);
	assertType('array<ReflectionAttribute<some random string>>', $attrsNonsense);
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
