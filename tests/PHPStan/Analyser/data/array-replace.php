<?php

namespace NonEmptyArray;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param int[] $array1
	 * @param string[] $array2
	 */
	public function arrayReplaceSimple($array1, $array2): void
	{
		assertType("array<int>", array_replace($array1, $array1));
		assertType("array<int|string>", array_replace($array1, $array2));
		assertType("array<int|string>", array_replace($array2, $array1));
	}

	/**
	 * @param array<int, int|string> $array1
	 * @param array<int, bool|float> $array2
	 */
	public function arrayReplaceUnionType($array1, $array2): void
	{
		assertType("array<int, int|string>", array_replace($array1, $array1));
		assertType("array<int, int|string>", array_replace($array1, $array1, $array1, $array1));
		assertType("array<int, bool|float|int|string>", array_replace($array1, $array2));
		assertType("array<int, bool|float|int|string>", array_replace($array2, $array1));
	}

	/**
	 * @param array<int, array{bar: '2'}|array{foo: '1'}> $array1
	 * @param array<int, array{bar: '3'}|array{foo: '2'}> $array2
	 */
	public function arrayReplaceUnionTypeArrayShapes($array1, $array2): void
	{
		assertType("array<int, array{bar: '2'}|array{foo: '1'}>", array_replace($array1, $array1));
		assertType("array<int, array{bar: '2'|'3'}|array{foo: '1'|'2'}>", array_replace($array1, $array2));
		assertType("array<int, array{bar: '2'|'3'}|array{foo: '1'|'2'}>", array_replace($array2, $array1));
	}

	/**
	 * @param array{a?: int, b?: string} $overrides
	 */
	public function arrayReplaceArrayShapes(array $overrides = []): void
	{
		$defaults = ['a' => 1, 'b' => 'bee'];
		$data = array_replace($defaults, $overrides);

		assertType("array{a: int, b: string}", $data);
	}

	/**
	 * @param array{foo: '1'} $array1
	 * @param array{foo: '2'} $array2
	 */
	public function arrayReplaceUnionTypeArrayShapesSimple($array1, $array2): void
	{
		assertType("array{foo: '1'}", array_replace($array1, $array1));
		assertType("array{foo: '2'}", array_replace($array1, $array2));
		assertType("array{foo: '1'}", array_replace($array2, $array1));
	}

	/**
	 * @param array{a: '1', b: '2', c: '3', d: '4', e: '5', f: '6', g: '7', h: '8', i: '9', j: '10', k: '11'} $array1
	 * @param array{foo: '2'} $array2
	 */
	public function arrayReplaceUnionTypeArrayShapesBig($array1, $array2): void
	{
		assertType("non-empty-array<'a'|'b'|'c'|'d'|'e'|'f'|'g'|'h'|'i'|'j'|'k', '1'|'10'|'11'|'2'|'3'|'4'|'5'|'6'|'7'|'8'|'9'>", array_replace($array1, $array1));
		assertType("non-empty-array<'a'|'b'|'c'|'d'|'e'|'f'|'foo'|'g'|'h'|'i'|'j'|'k', '1'|'10'|'11'|'2'|'3'|'4'|'5'|'6'|'7'|'8'|'9'>", array_replace($array1, $array2));
		assertType("non-empty-array<'a'|'b'|'c'|'d'|'e'|'f'|'foo'|'g'|'h'|'i'|'j'|'k', '1'|'10'|'11'|'2'|'3'|'4'|'5'|'6'|'7'|'8'|'9'>", array_replace($array2, $array1));
	}

	/**
	 * @param array{a: '1'} $array1
	 * @param array{b: '2'} $array2
	 * @param array{c: '3'} $array3
	 * @param array{d: '4'} $array4
	 * @param array{e: '5'} $array5
	 * @param array{f: '6'} $array6
	 */
	public function arrayReplaceUnionTypeArrayShapesArgumentsMany($array1, $array2, $array3, $array4, $array5, $array6): void
	{
		assertType("non-empty-array<literal-string&non-empty-string, '1'|'2'|'3'|'4'|'5'|'6'>", array_replace($array1, $array2, $array3, $array4, $array5, $array6));
	}

	/**
	 * @param array{a: '1'}|array{b: '2'}|array{c: '3'}|array{d: '4'}|array{e: '5'}|array{f: '6'}|array{g: '7'}|array{h: '8'}|array{i: '9'}|array{j: '10'}|array{k: '11'} $array1
	 * @param array{foo: '2'} $array2
	 */
	public function arrayReplaceUnionTypeArrayShapesMany($array1, $array2): void
	{
		assertType("non-empty-array<literal-string&non-empty-string, '1'|'10'|'11'|'2'|'3'|'4'|'5'|'6'|'7'|'8'|'9'>", array_replace($array1, $array1));
		assertType("non-empty-array<literal-string&non-empty-string, '1'|'10'|'11'|'2'|'3'|'4'|'5'|'6'|'7'|'8'|'9'>", array_replace($array1, $array2));
		assertType("non-empty-array<literal-string&non-empty-string, '1'|'10'|'11'|'2'|'3'|'4'|'5'|'6'|'7'|'8'|'9'>", array_replace($array2, $array1));
	}

	public function arrayReplaceLogic(): void
	{
		$base = ["orange", "banana", "apple", 'foo' => "raspberry"];
		$replacements = [0 => "pineapple", 4 => "cherry", 'foo' => "lall"];
		$replacements2 = [0 => "grape"];

		assertType("array{0: 'grape', 1: 'banana', 2: 'apple', foo: 'lall', 4: 'cherry'}", array_replace($base, $replacements, $replacements2));
	}
}