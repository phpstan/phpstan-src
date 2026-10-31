<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15617Types;

use function PHPStan\Testing\assertType;

class Foo {}

interface Bar {}

class Sx extends \SimpleXMLElement {}

/**
 * @template T of object
 * @template U of \Countable
 * @template V of Foo
 * @param T $t
 * @param U $u
 * @param V $v
 * @param object{} $shape
 * @param object{foo: int} $shapeWithProperty
 */
function doFoo(
	object $object,
	?object $nullableObject,
	\Countable $countable,
	\Stringable $stringable,
	\Traversable $traversable,
	\ArrayAccess $arrayAccess,
	\Iterator $iterator,
	$t,
	$u,
	$v,
	$shape,
	$shapeWithProperty,
	Foo $foo,
	Bar $bar,
	Sx $sx,
	\SimpleXMLElement $simpleXml,
	\GMP $gmp,
	\BcMath\Number $number,
	Foo|\GMP $union
): void {
	assertType('bool', (bool) $object);
	assertType('bool', (bool) $nullableObject);
	assertType('bool', (bool) $countable);
	assertType('bool', (bool) $stringable);
	assertType('bool', (bool) $traversable);
	assertType('bool', (bool) $arrayAccess);
	assertType('bool', (bool) $iterator);
	assertType('bool', (bool) $t);
	assertType('bool', (bool) $u);
	assertType('true', (bool) $v);
	assertType('bool', (bool) $shape);
	assertType('true', (bool) $shapeWithProperty);
	assertType('true', (bool) $foo);
	assertType('true', (bool) $bar);
	assertType('bool', (bool) $sx);
	assertType('bool', (bool) $simpleXml);
	assertType('bool', (bool) $gmp);
	assertType('bool', (bool) $number);
	assertType('bool', (bool) $union);
	assertType('bool', !$object);
	assertType('0|1', $object ? 1 : 0);

	if ($object instanceof \SimpleXMLElement || $object instanceof \GMP || $object instanceof \BcMath\Number) {
		return;
	}

	assertType('true', (bool) $object);
}
