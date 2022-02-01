<?php

namespace ArrayColumn;

use DOMElement;
use function PHPStan\Testing\assertType;

function testArrays(array $array): void
{
	/** @var array<int, array<string, string>> $array */
	assertType('array<int, string>', array_column($array, 'column'));
	assertType('array<int|string, string>', array_column($array, 'column', 'key'));
	assertType('array<int|string, array<string, string>>', array_column($array, null, 'key'));

	/** @var non-empty-array<int, array<string, string>> $array */
	// Note: Array may still be empty!
	assertType('array<int, string>', array_column($array, 'column'));

	/** @var array{} $array */
	assertType('array{}', array_column($array, 'column'));
	assertType('array{}', array_column($array, 'column', 'key'));
	assertType('array{}', array_column($array, null, 'key'));
}

function testConstantArrays(array $array): void
{
	/** @var array<int, array{column: string, key: string}> $array */
	assertType('array<int, string>', array_column($array, 'column'));
	assertType('array<string, string>', array_column($array, 'column', 'key'));
	assertType('array<string, array{column: string, key: string}>', array_column($array, null, 'key'));

	/** @var array<int, array{column: string, key: string}> $array */
	assertType('array{}', array_column($array, 'foo'));
	assertType('array{}', array_column($array, 'foo', 'key'));

	/** @var array{array{column: string, key: 'bar'}} $array */
	assertType("array{string}", array_column($array, 'column'));
	assertType("array{bar: string}", array_column($array, 'column', 'key'));
	assertType("array{bar: array{column: string, key: 'bar'}}", array_column($array, null, 'key'));

	/** @var array{array{column: string, key: string}} $array */
	assertType("non-empty-array<string, string>", array_column($array, 'column', 'key'));
	assertType("non-empty-array<string, array{column: string, key: string}>", array_column($array, null, 'key'));

	/** @var array<int, array{column?: 'foo', key?: 'bar'}> $array */
	assertType("array<int, 'foo'>", array_column($array, 'column'));
	assertType("array<'bar'|int, 'foo'>", array_column($array, 'column', 'key'));
	assertType("array<'bar'|int, array{column?: 'foo', key?: 'bar'}>", array_column($array, null, 'key'));

	/** @var array<int, array{column1: string, column2: bool}> $array */
	assertType('array<int, bool|string>', array_column($array, mt_rand(0, 1) === 0 ? 'column1' : 'column2'));

	/** @var non-empty-array<int, array{column: string, key: string}> $array */
	assertType('non-empty-array<int, string>', array_column($array, 'column'));
	assertType('non-empty-array<string, string>', array_column($array, 'column', 'key'));
	assertType('non-empty-array<string, array{column: string, key: string}>', array_column($array, null, 'key'));
}

function testImprecise(array $array): void {
	// These cases aren't handled precisely and will return non-constant arrays.

	/** @var array{array{column?: 'foo', key: 'bar'}} $array */
	assertType("array<int, 'foo'>", array_column($array, 'column'));
	assertType("array<'bar', 'foo'>", array_column($array, 'column', 'key'));
	assertType("array{bar: array{column?: 'foo', key: 'bar'}}", array_column($array, null, 'key'));

	/** @var array{array{column: 'foo', key?: 'bar'}} $array */
	assertType("non-empty-array<'bar'|int, 'foo'>", array_column($array, 'column', 'key'));
	assertType("non-empty-array<'bar'|int, array{column: 'foo', key?: 'bar'}>", array_column($array, null, 'key'));

	/** @var array{array{column: 'foo', key: 'bar'}}|array<int, array<string, string>> $array */
	assertType('array<int, string>', array_column($array, 'column'));
	assertType('array<int|string, string>', array_column($array, 'column', 'key'));

	/** @var array{0?: array{column: 'foo', key: 'bar'}} $array */
	assertType("array<int, 'foo'>", array_column($array, 'column'));
	assertType("array<'bar', 'foo'>", array_column($array, 'column', 'key'));
}

function testObjects(array $array): void {
	/** @var array<int, DOMElement> $array */
	assertType('array<int, string>', array_column($array, 'nodeName'));
	assertType('array<string, string>', array_column($array, 'nodeName', 'tagName'));
	assertType('array<string, DOMElement>', array_column($array, null, 'tagName'));
	assertType('array<int, mixed>', array_column($array, 'foo'));
	assertType('array<string, mixed>', array_column($array, 'foo', 'tagName'));
	assertType('array<string>', array_column($array, 'nodeName', 'foo'));
	assertType('array<DOMElement>', array_column($array, null, 'foo'));

	/** @var non-empty-array<int, DOMElement> $array */
	assertType('non-empty-array<int, string>', array_column($array, 'nodeName'));
	assertType('non-empty-array<string, string>', array_column($array, 'nodeName', 'tagName'));
	assertType('non-empty-array<string, DOMElement>', array_column($array, null, 'tagName'));
	assertType('array<int, mixed>', array_column($array, 'foo'));
	assertType('array<string, mixed>', array_column($array, 'foo', 'tagName'));
	assertType('non-empty-array<string>', array_column($array, 'nodeName', 'foo'));
	assertType('non-empty-array<DOMElement>', array_column($array, null, 'foo'));

	/** @var array{DOMElement} $array */
	assertType('array{string}', array_column($array, 'nodeName'));
	assertType('non-empty-array<string, string>', array_column($array, 'nodeName', 'tagName'));
	assertType('non-empty-array<string, DOMElement>', array_column($array, null, 'tagName'));
	assertType('array<int, mixed>', array_column($array, 'foo'));
	assertType('array<string, mixed>', array_column($array, 'foo', 'tagName'));
	assertType('non-empty-array<int|string, string>', array_column($array, 'nodeName', 'foo'));
	assertType('non-empty-array<int|string, DOMElement>', array_column($array, null, 'foo'));
}
