<?php // lint < 8.2

namespace ArrayColumn;

use DOMElement;
use function PHPStan\Testing\assertType;


class ArrayColumnTest
{

	/** @param array<int, array<string, string>> $array */
	public function testArray1(array $array): void
	{
		assertType('list<string>', array_column($array, 'column'));
		assertType('array<int|string, string>', array_column($array, 'column', 'key'));
		assertType('array<int|string, array<string, string>>', array_column($array, null, 'key'));
	}

	/** @param non-empty-array<int, array<string, string>> $array */
	public function testArray2(array $array): void
	{
		// Note: Array may still be empty!
		assertType('list<string>', array_column($array, 'column'));
	}

	/** @param array{} $array */
	public function testArray3(array $array): void
	{
		assertType('array{}', array_column($array, 'column'));
		assertType('array{}', array_column($array, 'column', 'key'));
		assertType('array{}', array_column($array, null, 'key'));
	}

	/** @param array<int, array<string, float>> $array */
	public function testArray4(array $array): void
	{
		assertType('array<int, float>', array_column($array, 'column', 'key'));
	}

	/** @param array<int, array<string, bool>> $array */
	public function testArray5(array $array): void
	{
		assertType('array<int, bool>', array_column($array, 'column', 'key'));
	}

	/** @param array<int, array<string, true>> $array */
	public function testArray6(array $array): void
	{
		assertType('array<int, true>', array_column($array, 'column', 'key'));
	}

	/** @param array<int, array<string, null>> $array */
	public function testArray7(array $array): void
	{
		assertType('array<\'\'|int, null>', array_column($array, 'column', 'key'));
	}

	/** @param array<int, array<string, array>> $array */
	public function testArray8(array $array): void
	{
		assertType('array<int, array>', array_column($array, 'column', 'key'));
	}

	/** @param array<int, array{column: string, key: string}> $array */
	public function testConstantArray1(array $array): void
	{
		assertType('list<string>', array_column($array, 'column'));
		assertType('array<string, string>', array_column($array, 'column', 'key'));
		assertType('array<string, array{column: string, key: string}>', array_column($array, null, 'key'));
	}

	/** @param array<int, array{column: string, key: string}> $array */
	public function testConstantArray2(array $array): void
	{
		assertType('array{}', array_column($array, 'foo'));
		assertType('array{}', array_column($array, 'foo', 'key'));
	}

	/** @param array{array{column: string, key: 'bar'}} $array */
	public function testConstantArray3(array $array): void
	{
		assertType("array{string}", array_column($array, 'column'));
		assertType("array{bar: string}", array_column($array, 'column', 'key'));
		assertType("array{bar: array{column: string, key: 'bar'}}", array_column($array, null, 'key'));
	}

	/** @param array{array{column: string, key: string}} $array */
	public function testConstantArray4(array $array): void
	{
		assertType("non-empty-array<string, string>", array_column($array, 'column', 'key'));
		assertType("non-empty-array<string, array{column: string, key: string}>", array_column($array, null, 'key'));
	}

	/** @param array<int, array{column?: 'foo', key?: 'bar'}> $array */
	public function testConstantArray5(array $array): void
	{
		assertType("list<'foo'>", array_column($array, 'column'));
		assertType("array<'bar'|int, 'foo'>", array_column($array, 'column', 'key'));
		assertType("array<'bar'|int, array{column?: 'foo', key?: 'bar'}>", array_column($array, null, 'key'));
	}

	/** @param array<int, array{column1: string, column2: bool}> $array */
	public function testConstantArray6(array $array): void
	{
		assertType('list<bool|string>', array_column($array, mt_rand(0, 1) === 0 ? 'column1' : 'column2'));
	}

	/** @param non-empty-array<int, array{column: string, key: string}> $array */
	public function testConstantArray7(array $array): void
	{
		assertType('non-empty-list<string>', array_column($array, 'column'));
		assertType('non-empty-array<string, string>', array_column($array, 'column', 'key'));
		assertType('non-empty-array<string, array{column: string, key: string}>', array_column($array, null, 'key'));
	}

	/** @param array<int, array{column: string, key: float}> $array */
	public function testConstantArray8(array $array): void
	{
		assertType('array<int, string>', array_column($array, 'column', 'key'));
	}

	/** @param array<int, array{column: string, key: bool}> $array */
	public function testConstantArray9(array $array): void
	{
		assertType('array<0|1, string>', array_column($array, 'column', 'key'));
	}

	/** @param array<int, array{column: string, key: true}> $array */
	public function testConstantArray10(array $array): void
	{
		assertType('array<1, string>', array_column($array, 'column', 'key'));
	}

	/** @param array<int, array{column: string, key: null}> $array */
	public function testConstantArray11(array $array): void
	{
		assertType('array<\'\', string>', array_column($array, 'column', 'key'));
	}

	/** @param array{0?: array{column: 'foo', key: 'bar'}} $array */
	public function testConstantArray12(array $array): void
	{
		assertType("array{0?: 'foo'}", array_column($array, 'column'));
		assertType("array{bar?: 'foo'}", array_column($array, 'column', 'key'));
	}

	/** @param array{0?: array{column: 'foo1', key: 'bar1'}, 1?: array{column: 'foo2', key: 'bar2'}} $array */
	public function testConstantArray13(array $array): void
	{
		assertType("array{0?: 'foo1'|'foo2', 1?: 'foo2'}", array_column($array, 'column'));
		assertType("array{bar1?: 'foo1', bar2?: 'foo2'}", array_column($array, 'column', 'key'));
	}

	/** @param array{0?: array{column: 'foo1', key: 'bar1'}, 1: array{column: 'foo2', key: 'bar2'}} $array */
	public function testConstantArray14(array $array): void
	{
		assertType("array{0: 'foo1'|'foo2', 1?: 'foo2'}", array_column($array, 'column'));
		assertType("array{bar1?: 'foo1', bar2: 'foo2'}", array_column($array, 'column', 'key'));
	}

	// These cases aren't handled precisely and will return non-constant arrays.

	/** @param array{array{column?: 'foo', key: 'bar'}} $array */
	public function testImprecise1(array $array): void
	{
		assertType("list<'foo'>", array_column($array, 'column'));
		assertType("array<'bar', 'foo'>", array_column($array, 'column', 'key'));
		assertType("array{bar: array{column?: 'foo', key: 'bar'}}", array_column($array, null, 'key'));
	}

	/** @param array{array{column: 'foo', key?: 'bar'}} $array */
	public function testImprecise2(array $array): void
	{
		assertType("non-empty-array<'bar'|int, 'foo'>", array_column($array, 'column', 'key'));
		assertType("non-empty-array<'bar'|int, array{column: 'foo', key?: 'bar'}>", array_column($array, null, 'key'));
	}

	/** @param array{array{column: 'foo', key: 'bar'}}|array<int, array<string, string>> $array */
	public function testImprecise3(array $array): void
	{
		assertType('list<string>', array_column($array, 'column'));
		assertType('array<int|string, string>', array_column($array, 'column', 'key'));
	}

	/** @param array<int, DOMElement> $array */
	public function testImprecise5(array $array): void
	{
		assertType('list<string>', array_column($array, 'nodeName'));
		assertType('array<string, string>', array_column($array, 'nodeName', 'tagName'));
		assertType('array<string, DOMElement>', array_column($array, null, 'tagName'));
		assertType('list<mixed>', array_column($array, 'foo'));
		assertType('array<string, mixed>', array_column($array, 'foo', 'tagName'));
		assertType('array<string>', array_column($array, 'nodeName', 'foo'));
		assertType('array<DOMElement>', array_column($array, null, 'foo'));
	}

	/** @param non-empty-array<int, DOMElement> $array */
	public function testObjects1(array $array): void
	{
		assertType('non-empty-list<string>', array_column($array, 'nodeName'));
		assertType('non-empty-array<string, string>', array_column($array, 'nodeName', 'tagName'));
		assertType('non-empty-array<string, DOMElement>', array_column($array, null, 'tagName'));
		assertType('list<mixed>', array_column($array, 'foo'));
		assertType('array<string, mixed>', array_column($array, 'foo', 'tagName'));
		assertType('non-empty-array<string>', array_column($array, 'nodeName', 'foo'));
		assertType('non-empty-array<DOMElement>', array_column($array, null, 'foo'));
	}

	/** @param array{DOMElement} $array */
	public function testObjects2(array $array): void
	{
		assertType('array{string}', array_column($array, 'nodeName'));
		assertType('non-empty-array<string, string>', array_column($array, 'nodeName', 'tagName'));
		assertType('non-empty-array<string, DOMElement>', array_column($array, null, 'tagName'));
		assertType('list<mixed>', array_column($array, 'foo'));
		assertType('array<string, mixed>', array_column($array, 'foo', 'tagName'));
		assertType('non-empty-array<string>', array_column($array, 'nodeName', 'foo'));
		assertType('non-empty-array<DOMElement>', array_column($array, null, 'foo'));
	}

}

final class Foo
{

	/** @param array<int, self> $a */
	public function doFoo(array $a): void
	{
		assertType('list<mixed>', array_column($a, 'nodeName'));
		assertType('array', array_column($a, 'nodeName', 'tagName'));
	}

}
