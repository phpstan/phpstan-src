<?php declare(strict_types = 1);

namespace Bug6211;

/**
 * @method bool isTrue()
 */
interface Foo
{
	public function test(): bool;
}

class Hell implements Foo
{
	public function test(): bool
	{
		return true;
	}
}

$hell = new Hell();

// @method should not make method_exists always true
if (\method_exists($hell, 'isTrue')) {

}

// @method with class string should not make method_exists always true
if (\method_exists(Hell::class, 'isTrue')) {

}

// native method should still be always true
if (\method_exists($hell, 'test')) {

}

// native method via class string should still be always true
if (\method_exists(Hell::class, 'test')) {

}

/**
 * @method bool magicMethod()
 */
class Bar
{
	public function realMethod(): bool
	{
		return true;
	}
}

$bar = new Bar();

// @method on the class itself (not interface) should not make method_exists always true
if (\method_exists($bar, 'magicMethod')) {

}

// native method should still be always true
if (\method_exists($bar, 'realMethod')) {

}

/**
 * @property int $magicProp
 */
class Baz
{
	public int $realProp = 1;

	public function __get(string $name): mixed
	{
		return null;
	}
}

$baz = new Baz();

// @property should not make property_exists always true
if (\property_exists($baz, 'magicProp')) {

}

// native property should still be always true
if (\property_exists($baz, 'realProp')) {

}

// Nested method_exists with @method should report the inner as always-true
if (\method_exists($hell, 'isTrue')) {
	if (\method_exists($hell, 'isTrue')) { // if condition always true

	}
}

// Nested method_exists with @method via class-string
if (\method_exists(Hell::class, 'isTrue')) {
	if (\method_exists(Hell::class, 'isTrue')) { // if condition always true

	}
}

// Nested method_exists with native method (already always-true, inner is also)
if (\method_exists($hell, 'test')) {
	if (\method_exists($hell, 'test')) {

	}
}

// Nested property_exists with @property should report the inner as always-true
if (\property_exists($baz, 'magicProp')) {
	if (\property_exists($baz, 'magicProp')) { // if condition always true

	}
}

// Nested property_exists with native property (already always-true, inner is also)
if (\property_exists($baz, 'realProp')) {
	if (\property_exists($baz, 'realProp')) {

	}
}

/**
 * @param class-string<Foo> $classString
 */
function testGenericClassString(string $classString): void {
	// @method via generic class-string should not make method_exists always true
	if (\method_exists($classString, 'isTrue')) {

	}

	// native method via generic class-string should still be always true
	if (\method_exists($classString, 'test')) {

	}
}
