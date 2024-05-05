<?php declare(strict_types=1); // lint >= 8.0

namespace OffsetAccessMixed;

/**
 * @template T
 * @param T $a
 */
function foo(mixed $a): void
{
	var_dump($a[5]);
	isset($a[5]); // error, because including object type

	if (is_object($a)) {
		throw new \Error();
	}

	var_dump($a[5]); // error
	isset($a[5]); // ok, because object type, which throws an error do not implement ArrayAccess, is subtracted
}

function foo2(mixed $a): void
{
	var_dump($a[5]);
	isset($a[5]);

	if (is_object($a)) {
		throw new \Error();
	}

	var_dump($a[5]); // error
	isset($a[5]); // ok, because object type, which throws an error do not implement ArrayAccess, is subtracted
}

function foo3($a): void
{
	var_dump($a[5]);
	isset($a[5]);

	if (is_object($a)) {
		throw new \Error();
	}

	var_dump($a[5]); // error
	isset($a[5]); // ok, because object type, which throws an error do not implement ArrayAccess, is subtracted
}
