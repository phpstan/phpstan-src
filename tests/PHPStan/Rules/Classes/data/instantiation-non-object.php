<?php declare(strict_types = 1);

namespace InstantiationNonObject;

function get_class_name(): int
{
	return 123;
}

class Foo
{
}

/**
 * @param int|string $intOrString
 * @param int|float $intOrFloat
 * @param class-string $classString
 * @param class-string<Foo> $classStringOfFoo
 */
function doFoo(
	string $string,
	object $object,
	int $int,
	float $float,
	bool $bool,
	$intOrString,
	$intOrFloat,
	?string $nullableString,
	string $classString,
	string $classStringOfFoo,
	Foo $foo
): void
{
	$class = get_class_name();
	new $class;

	new $string;
	new $object;
	new $int;
	new $float;
	new $bool;
	new $intOrString;
	new $intOrFloat;
	new $nullableString;
	new $classString;
	new $classStringOfFoo;
	new $foo;

	$array = ['a'];
	new $array;
}
