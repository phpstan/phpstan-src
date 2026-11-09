<?php // lint >= 8.1

namespace Bug15681RestrictedUsage;

class Request
{

	public string $foo = '';

	public function doFoo(): void
	{
	}

	public function __toString(): string
	{
		return '';
	}

}

class RequestStack
{

	public function getCurrentRequest(): ?Request
	{
		return null;
	}

}

class Other
{

}

class StaticRequest
{

	public static string $foo = '';

	public const FOO = 1;

	public static function doFoo(): void
	{
	}

}

function test(RequestStack $stack, Request $request, ?Request $nullable, Request|false $falsy, Request|Other|null $multi, Request|int $int, ?StaticRequest $static): void
{
	$request->doFoo();
	$nullable?->doFoo();
	$nullable->doFoo();
	$stack->getCurrentRequest()->doFoo();
	$falsy->doFoo();
	$multi->doFoo();
	$int->doFoo();

	echo $request->foo;
	echo $nullable->foo;
	echo $stack->getCurrentRequest()->foo;
	echo $falsy->foo;

	$nullable->doFoo(...);
	$falsy->doFoo(...);

	(string) $nullable;
	(string) $falsy;

	$static::doFoo();
	$static::doFoo(...);
	echo $static::$foo;
	echo $static::FOO;
}

/**
 * @param class-string<StaticRequest> $className
 * @param class-string<StaticRequest>|null $nullableClassName
 */
function testClassString(string $className, ?string $nullableClassName): void
{
	$className::doFoo();
	$className::doFoo(...);
	echo $className::$foo;
	echo $className::FOO;

	$nullableClassName::doFoo();
	$nullableClassName::doFoo(...);
	echo $nullableClassName::$foo;
	echo $nullableClassName::FOO;
}

/**
 * @template T
 */
class GenericStaticRequest
{

	public static string $foo = '';

	public const FOO = 1;

	public static function doFoo(): void
	{
	}

}

/**
 * @param GenericStaticRequest<StaticRequest>|null $generic
 * @param class-string<GenericStaticRequest<StaticRequest>> $genericClassName
 */
function testGeneric(?GenericStaticRequest $generic, string $genericClassName): void
{
	$generic::doFoo();
	$generic::doFoo(...);
	echo $generic::$foo;
	echo $generic::FOO;

	$genericClassName::doFoo();
	$genericClassName::doFoo(...);
	echo $genericClassName::$foo;
	echo $genericClassName::FOO;
}

/**
 * @param (callable(): void)|Request $callable
 * @param iterable<int>|Request $iterable
 * @param object{a: int}|Request $objectShape
 */
function testUnionWithClasslessObject($callable, $iterable, $objectShape): void
{
	$callable->doFoo();
	$iterable->doFoo();
	$objectShape->doFoo();

	echo $callable->foo;
	echo $iterable->foo;
	echo $objectShape->foo;

	$callable->doFoo(...);
	$iterable->doFoo(...);
	$objectShape->doFoo(...);

	(string) $callable;
	(string) $iterable;
	(string) $objectShape;
}

/**
 * @template TObject of object
 * @param TObject|Request $templated
 */
function testUnionWithTemplate(object $templated): void
{
	$templated->doFoo();
	echo $templated->foo;
	$templated->doFoo(...);
	(string) $templated;
}

/**
 * @param StaticRequest|false $orFalse
 * @param StaticRequest|string $orString
 * @param StaticRequest|int|null $orIntOrNull
 */
function testStaticOnMixedUnion(StaticRequest|false $orFalse, StaticRequest|string $orString, StaticRequest|int|null $orIntOrNull): void
{
	$orFalse::doFoo();
	$orFalse::doFoo(...);
	echo $orFalse::$foo;
	echo $orFalse::FOO;

	$orString::doFoo();
	$orString::doFoo(...);
	echo $orString::$foo;
	echo $orString::FOO;

	$orIntOrNull::doFoo();
	$orIntOrNull::doFoo(...);
	echo $orIntOrNull::$foo;
	echo $orIntOrNull::FOO;
}

/**
 * @param class-string<StaticRequest>|false $classNameOrFalse
 */
function testClassStringOrFalse(string|false $classNameOrFalse): void
{
	$classNameOrFalse::doFoo();
	$classNameOrFalse::doFoo(...);
	echo $classNameOrFalse::$foo;
	echo $classNameOrFalse::FOO;
}
