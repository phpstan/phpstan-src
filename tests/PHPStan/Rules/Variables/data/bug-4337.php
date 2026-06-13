<?php

namespace Bug4337;

class Foo
{

	/** @var string|null */
	public $stringOrNull = null;

	/** @var string|null */
	public static $staticStringOrNull = null;

	public function method(): ?string
	{
		return null;
	}

	public static function staticMethod(): ?string
	{
		return null;
	}

}

function hello1(?string $name): ?string
{
	if ($name === null) {
		return null;
	}

	return 'Hello, ' . $name . '!';
}

function hello2(?string $name): ?string
{
	return hello1($name) ?? null;
}

function methodCall(Foo $foo): ?string
{
	return $foo->method() ?? null;
}

function staticCall(): ?string
{
	return Foo::staticMethod() ?? null;
}

function definedNullableVariable(?string $name): ?string
{
	$x = $name;
	return $x ?? null;
}

function alwaysSetNullableProperty(Foo $foo): ?string
{
	return $foo->stringOrNull ?? null;
}

function alwaysSetNullableStaticProperty(): ?string
{
	return Foo::$staticStringOrNull ?? null;
}

/** @param array{a: string|null} $arr */
function alwaysSetNullableOffset(array $arr): ?string
{
	return $arr['a'] ?? null;
}

function assignCoalesceAlwaysSet(?string $name): void
{
	$x = $name;
	$x ??= null;
}

// these are NOT unnecessary and must not be reported

function maybeUndefinedVariable(): ?string
{
	if (rand() > 0.5) {
		$x = 'foo';
	}

	return $x ?? null;
}

/** @param array<string, string|null> $arr */
function maybeUndefinedOffset(array $arr): ?string
{
	return $arr['a'] ?? null;
}

function nonNullRightSide(?string $name): string
{
	return hello1($name) ?? 'default';
}
