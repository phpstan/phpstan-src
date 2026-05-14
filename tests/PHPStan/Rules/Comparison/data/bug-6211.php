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
