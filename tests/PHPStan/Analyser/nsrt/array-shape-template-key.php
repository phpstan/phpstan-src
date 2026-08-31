<?php declare(strict_types = 1);

namespace ArrayShapeTemplateKey;

use Exception;
use stdClass;
use function PHPStan\Testing\assertType;

class Shapes
{

	/**
	 * @template TKey of string
	 * @param TKey $key
	 * @return array{TKey: int}
	 */
	public function sealed(string $key): array
	{
		throw new Exception();
	}

	/**
	 * @template TKey of string
	 * @param TKey $key
	 * @return array{TKey?: int}
	 */
	public function optionalKey(string $key): array
	{
		throw new Exception();
	}

	/**
	 * @template TKey of int
	 * @param TKey $key
	 * @return array{TKey: string}
	 */
	public function intKey(int $key): array
	{
		throw new Exception();
	}

	/**
	 * @template TKey of string
	 * @template TOther of string
	 * @param TKey $key
	 * @param TOther $other
	 * @return array{TKey: int, TOther: bool}
	 */
	public function twoKeys(string $key, string $other): array
	{
		throw new Exception();
	}

	/**
	 * @template TKey of string
	 * @param TKey $key
	 * @return array{int, TKey: string}
	 */
	public function autoIndex(string $key): array
	{
		throw new Exception();
	}

	/**
	 * @template TKey of string
	 * @param TKey $key
	 * @return array{outer: array{TKey: int}}
	 */
	public function nested(string $key): array
	{
		throw new Exception();
	}

	/**
	 * @template TKey of string
	 * @param TKey $key
	 * @return array{TKey: callable(): void}
	 */
	public function callableValue(string $key): array
	{
		throw new Exception();
	}

	/**
	 * @template TKey of string
	 * @param TKey $key
	 * @return non-empty-array{TKey: int}
	 */
	public function nonEmpty(string $key): array
	{
		throw new Exception();
	}

	/**
	 * @template TKey of 'a'|'b'
	 * @param TKey $key
	 * @return array{TKey: int}
	 */
	public function unionBound(string $key): array
	{
		throw new Exception();
	}

	/**
	 * @template TKey of stdClass
	 * @param TKey $key
	 * @return array{TKey: int}
	 */
	public function objectKey(object $key)
	{
		throw new Exception();
	}

}

class UnsealedShapes
{

	/**
	 * @template TKey of string
	 * @param TKey $key
	 * @return array{TKey: int, ...}
	 */
	public function unsealed(string $key): array
	{
		throw new Exception();
	}

	/**
	 * @template TKey of string
	 * @param TKey $key
	 * @return array{TKey: int, ...<bool>}
	 */
	public function unsealedValueOnly(string $key): array
	{
		throw new Exception();
	}

	/**
	 * @template TKey of string
	 * @param TKey $key
	 * @return array{TKey: int, ...<string, bool>}
	 */
	public function unsealedKeyAndValue(string $key): array
	{
		throw new Exception();
	}

	/**
	 * @template TKey of string
	 * @param TKey $key
	 * @return array{TKey: int, ...<'x'|'y', bool>}
	 */
	public function unsealedFiniteKeys(string $key): array
	{
		throw new Exception();
	}

	/**
	 * @template TKey of string
	 * @param TKey $key
	 * @return array{a: int, ...<TKey, bool>}
	 */
	public function templateUnsealedKey(string $key): array
	{
		throw new Exception();
	}

	/**
	 * @template TKey of string
	 * @param TKey $key
	 * @return array{TKey: int, ...<TKey, bool>}
	 */
	public function templateKeyAndUnsealedKey(string $key): array
	{
		throw new Exception();
	}

}

/**
 * @template TKey of string
 */
class GenericHolder
{

	/** @var array{TKey: int} */
	public array $shape;

	/**
	 * @return array{TKey: int, ...<TKey, bool>}
	 */
	public function get(): array
	{
		throw new Exception();
	}

	/**
	 * @param TKey $key
	 * @return array{TKey: int}
	 */
	public function insideBody(string $key): array
	{
		// the template type is not resolved inside the method body, so the
		// shape degrades to the bound of the template type
		assertType('non-empty-array<string, int>', $this->insideBody($key));

		throw new Exception();
	}

}

/**
 * @param 'a'|'b' $union
 */
function testKeys(Shapes $s, string $str, int $i, stdClass $o, string $union): void
{
	assertType('array{a: int}', $s->sealed('a'));
	assertType('array{5: int}', $s->sealed('5'));
	assertType('non-empty-array<string, int>', $s->sealed($str));

	assertType('array{a?: int}', $s->optionalKey('a'));
	assertType('array<string, int>', $s->optionalKey($str));

	assertType('array{5: string}', $s->intKey(5));
	assertType('non-empty-array<int, string>', $s->intKey($i));

	assertType('array{a: int, b: bool}', $s->twoKeys('a', 'b'));
	assertType('array{a: bool|int, ...<string, bool>}', $s->twoKeys('a', $str));

	assertType('array{0: int, a: string}', $s->autoIndex('a'));
	assertType('array{outer: array{a: int}}', $s->nested('a'));
	assertType('array{a: callable(): void}', $s->callableValue('a'));
	assertType('array{a: int}', $s->nonEmpty('a'));

	assertType('array{a: int}', $s->unionBound('a'));
	assertType('non-empty-array{a?: int, b?: int}', $s->unionBound($union));

	// stdClass cannot be an array key at all
	assertType('*ERROR*', $s->objectKey($o));
}

function testUnsealed(UnsealedShapes $s, string $str): void
{
	assertType('array{a: int, ...}', $s->unsealed('a'));
	assertType('non-empty-array', $s->unsealed($str));

	assertType('array{a: int, ...<bool>}', $s->unsealedValueOnly('a'));
	assertType('non-empty-array<bool|int>', $s->unsealedValueOnly($str));

	assertType('array{a: int, ...<string, bool>}', $s->unsealedKeyAndValue('a'));
	assertType('non-empty-array<string, bool|int>', $s->unsealedKeyAndValue($str));

	assertType('array{a: int, x?: bool, y?: bool}', $s->unsealedFiniteKeys('a'));
	// an explicit key owns its slot, the unsealed extras only describe the rest
	assertType('array{x: int, y?: bool}', $s->unsealedFiniteKeys('x'));

	assertType('array{a: int}', $s->templateUnsealedKey('a'));
	assertType('array{a: int, z?: bool}', $s->templateUnsealedKey('z'));
	assertType('array{a: int, ...<string, bool>}', $s->templateUnsealedKey($str));

	assertType('array{a: int}', $s->templateKeyAndUnsealedKey('a'));
	assertType('non-empty-array<string, bool|int>', $s->templateKeyAndUnsealedKey($str));
}

/**
 * @param GenericHolder<'a'> $constant
 * @param GenericHolder<string> $generic
 */
function testGenericClass(GenericHolder $constant, GenericHolder $generic): void
{
	assertType('array{a: int}', $constant->shape);
	assertType('non-empty-array<string, int>', $generic->shape);

	assertType('array{a: int}', $constant->get());
	assertType('non-empty-array<string, bool|int>', $generic->get());
}
