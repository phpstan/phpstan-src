<?php declare(strict_types = 1);

namespace Bug15292Generic;

class BigContainer
{

}

/**
 * @template T
 * @param T $v
 * @return T
 */
function identity($v)
{
	return $v;
}

final class Foo
{

	private const ALLOWED = [
		'Bug15292Generic\BigContainer',
		'generic_ident',
	];

	public function doFoo(): void
	{
		identity(self::ALLOWED);
		identity('Bug15292Generic\BigContainer::generic_string_ident');
	}

}
