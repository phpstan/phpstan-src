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
