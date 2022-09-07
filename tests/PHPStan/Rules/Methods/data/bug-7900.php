<?php declare(strict_types = 1);

namespace Bug7900;

abstract class A
{
	/**
	 * @param numeric-string $requireInt
	 */
	public static function fromNumString(string $requireInt): void
	{
	}
}

final class B extends A {}

/** @param mixed[] $vars */
function x(array $vars): void {
	$vars['b'] ?? '';

	B::fromNumString($vars['a']);
}
