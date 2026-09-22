<?php declare(strict_types = 1);

namespace IntersectionTemplateMixedProperty;

class Foo
{

	public int $prop = 1;

	public static int $staticProp = 1;

}

/**
 * @template T
 * @param T&Foo $x
 */
function test($x): void
{
	$x->prop = 'string';
	$x::$staticProp = 'string';
}
