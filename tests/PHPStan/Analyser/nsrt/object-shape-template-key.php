<?php declare(strict_types = 1);

namespace ObjectShapeTemplateKey;

use Exception;
use stdClass;
use function PHPStan\Testing\assertType;

class Shapes
{

	/**
	 * @template TKey of string
	 * @param TKey $key
	 * @return object{TKey: int}
	 */
	public function sealed(string $key): object
	{
		throw new Exception();
	}

	/**
	 * @template TKey of string
	 * @param TKey $key
	 * @return object{TKey?: int}
	 */
	public function optionalKey(string $key): object
	{
		throw new Exception();
	}

	/**
	 * @template TKey of string
	 * @template TOther of string
	 * @param TKey $key
	 * @param TOther $other
	 * @return object{TKey: int, TOther: bool}
	 */
	public function twoKeys(string $key, string $other): object
	{
		throw new Exception();
	}

	/**
	 * @template TKey of string
	 * @param TKey $key
	 * @return object{TKey: int, foo: string, 'TKey': bool}
	 */
	public function mixedKeys(string $key): object
	{
		throw new Exception();
	}

	/**
	 * @template TKey of string
	 * @param TKey $key
	 * @return object{outer: object{TKey: int}}
	 */
	public function nested(string $key): object
	{
		throw new Exception();
	}

	/**
	 * @template TKey of string
	 * @param TKey $key
	 * @return array{list: list<object{TKey: int}>}
	 */
	public function insideArray(string $key): array
	{
		throw new Exception();
	}

	/**
	 * @template TKey of 'a'|'b'
	 * @param TKey $key
	 * @return object{TKey: int}
	 */
	public function unionBound(string $key): object
	{
		throw new Exception();
	}

	/**
	 * @template TKey
	 * @param TKey $key
	 * @return object{TKey: int}
	 */
	public function noBound($key): object
	{
		throw new Exception();
	}

	/**
	 * @template TKey of int
	 * @param TKey $key
	 * @return object{TKey: string}
	 */
	public function intKey(int $key)
	{
		throw new Exception();
	}

	/**
	 * @template TKey of stdClass
	 * @param TKey $key
	 * @return object{TKey: int}
	 */
	public function objectKey(object $key)
	{
		throw new Exception();
	}

}

/**
 * @template TKey of string
 */
class GenericHolder
{

	/** @var object{TKey: int} */
	public object $shape;

	/**
	 * @return stdClass&object{TKey: int}
	 */
	public function get(): object
	{
		throw new Exception();
	}

	/**
	 * @param TKey $key
	 * @return object{TKey: int, foo: string}
	 */
	public function insideBody(string $key): object
	{
		// the template type is not resolved inside the method body, so the
		// property name is unknown and only the known properties are left
		assertType('object{foo: string}', $this->insideBody($key));

		throw new Exception();
	}

}

/**
 * @param 'a'|'b' $union
 * @param 'a'|1 $stringOrInt
 */
function testKeys(Shapes $s, string $str, int $i, stdClass $o, string $union, $stringOrInt): void
{
	assertType('object{a: int}', $s->sealed('a'));
	assertType('object{foo bar: int}', $s->sealed('foo bar'));
	assertType('object', $s->sealed($str));

	assertType('object{a?: int}', $s->optionalKey('a'));
	assertType('object', $s->optionalKey($str));

	assertType('object{a: int, b: bool}', $s->twoKeys('a', 'b'));
	// the later key wins, like in a written shape
	assertType('object{a: bool}', $s->twoKeys('a', 'a'));
	assertType('object{a: int}', $s->twoKeys('a', $str));

	assertType('object{x: int, foo: string, TKey: bool}', $s->mixedKeys('x'));
	assertType('object{foo: string, TKey: bool}', $s->mixedKeys('foo'));

	assertType('object{outer: object{a: int}}', $s->nested('a'));
	assertType('array{list: list<object{a: int}>}', $s->insideArray('a'));

	assertType('object{a: int}', $s->unionBound('a'));
	assertType('object{a?: int, b?: int}', $s->unionBound($union));

	assertType('object{a: int}', $s->noBound('a'));
	assertType('object', $s->noBound($str));
	// only the string part of the key can name a property
	assertType('object{a?: int}', $s->noBound($stringOrInt));

	// property names are strings, so neither of these can be one
	assertType('*ERROR*', $s->noBound($i));
	assertType('*ERROR*', $s->intKey(5));
	assertType('*ERROR*', $s->objectKey($o));
}

/**
 * @param GenericHolder<'a'> $constant
 * @param GenericHolder<string> $generic
 */
function testGenericClass(GenericHolder $constant, GenericHolder $generic): void
{
	assertType('object{a: int}', $constant->shape);
	assertType('int', $constant->shape->a);
	assertType('object', $generic->shape);

	assertType('object{a: int}&stdClass', $constant->get());
	assertType('stdClass', $generic->get());
}
