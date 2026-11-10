<?php declare(strict_types = 1);

namespace Bug3889;

class HelloWorld
{
	public function sayHello(): void
	{
		$str = 'Hello';
		$search = ['a', 'b', 'c'];
		$replace = ['x', 'y'];
		echo str_replace($search, $replace, $str);
	}

	public function replaceIsString(): void
	{
		$str = 'Hello';
		$search = ['a', 'b', 'c'];
		echo str_replace($search, 'foo', $str);
	}
}

class Variants
{
	/**
	 * @param list<string> $list
	 * @param non-empty-list<string> $nonEmptyList
	 * @param array{string, string} $two
	 * @param array{string, string, string}|array{string} $oneOrThree
	 */
	public function doFoo(string $s, array $list, array $nonEmptyList, array $two, array $oneOrThree): void
	{
		// see https://3v4l.org/Ct0Ts#v
		str_replace(['a', 'b'], ['x', 'y'], $s);
		str_replace(['a', 'b'], ['x', 'y', 'z'], $s);
		str_replace(['a', 'b'], [], $s);
		str_ireplace(['a', 'b', 'c'], ['x'], $s);
		str_replace('a', 'x', $s);
		str_replace($list, ['x'], $s);
		str_replace($nonEmptyList, ['x'], $s);
		str_replace($two, ['x'], $s);
		str_replace($two, $nonEmptyList, $s);
		str_replace($oneOrThree, ['x'], $s);
		str_replace($oneOrThree, [], $s);
		str_replace(['a', 'b', 'c'], $oneOrThree, $s);

		preg_replace(['/a/', '/b/'], ['x'], $s);
		preg_replace(['/a/', '/b/'], ['x', 'y'], $s);
		preg_replace(['/a/', '/b/'], 'x', $s);
		preg_filter(['/a/', '/b/'], ['x'], $s);
	}
}
