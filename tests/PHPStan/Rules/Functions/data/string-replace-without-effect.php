<?php declare(strict_types = 1); // lint >= 8.0

namespace StringReplaceWithoutEffect;

class Foo
{

	public function addClass(string $className, string $path): void
	{
		// swapped arguments, real world bug from composer/class-map-generator
		echo strtr('\\', '/', $path);
		echo rtrim(strtr('\\', '/', $path), '/');
	}

	public function correctOrder(string $path): void
	{
		echo strtr($path, '\\', '/');
		echo strtr('a\\b', '\\', '/');
	}

	public function emptyFromTo(string $path): void
	{
		echo strtr($path, '', '/');
		echo strtr($path, '\\', '');
	}

	/**
	 * @param non-empty-string $nonEmpty
	 */
	public function unknownStrings(string $path, string $from, string $nonEmpty): void
	{
		echo strtr($path, $from, '/');
		echo strtr('abc', $from, '/');
		echo strtr($path, $nonEmpty, '/');
	}

	/**
	 * @param array<string, string> $pairs
	 */
	public function pairs(string $path, array $pairs): void
	{
		echo strtr($path, $pairs);
		echo strtr($path, []);
		echo strtr('abc', ['x' => 'y']);
		echo strtr('abc', ['b' => 'y']);
		echo strtr('a1c', [1 => 'y']);
		echo strtr('abc', ['xy' => 'z', 'qq' => 'w']);
	}

	public function unions(bool $b): void
	{
		$subject = $b ? 'abc' : 'def';
		echo strtr($subject, 'xy', 'zw');
		echo strtr($subject, 'xc', 'zw');
	}

}

class Bar
{

	public function strReplace(string $path): void
	{
		echo str_replace('\\', '/', 'a/b');
		echo str_replace('/', '\\', 'a/b');
		echo str_replace('\\', '/', $path);
		echo str_replace(['x', 'y'], '/', 'abc');
		echo str_replace(['x', 'b'], '/', 'abc');
		echo str_replace([], '/', 'abc');
		echo str_replace('', '/', 'abc');
		echo str_replace('', '/', $path);
	}

	public function strIreplace(string $path): void
	{
		echo str_ireplace('B', '/', 'abc');
		echo str_ireplace('X', '/', 'abc');
		echo str_ireplace('X', '/', $path);
	}

	public function withCount(string $path): void
	{
		$count = 0;
		echo str_replace('x', '/', 'abc', $count);
		echo $count;
	}

	public function arraySubject(): void
	{
		echo implode(str_replace('x', '/', ['abc', 'def']));
		echo implode(str_replace('a', '/', ['abc', 'def']));
	}

	/**
	 * @param 'abc' $subject
	 * @param 'x' $search
	 */
	public function phpDocTypes(string $subject, string $search): void
	{
		echo str_replace('x', '/', $subject);
		echo str_replace($search, '/', 'abc');
	}

	public function namedArguments(string $path): void
	{
		echo str_replace(subject: 'abc', search: 'x', replace: '/');
		echo strtr(to: '.', from: '/', string: '\\');
	}

}

class Identity
{

	public function strtrIdentity(string $path): void
	{
		echo strtr($path, 'ab', 'ab');
		echo strtr($path, 'abc', 'ab');
		echo strtr($path, 'ab', 'ba');
		echo strtr($path, ['a' => 'a', 'bb' => 'bb']);
		echo strtr($path, ['a' => 'b']);
	}

	public function strReplaceIdentity(string $path): void
	{
		echo str_replace('/', '/', $path);
		echo str_replace('/', '\\', $path);
		echo str_ireplace('A', 'A', $path);
	}

}

class Others
{

	public function substrReplace(string $path): void
	{
		echo substr_replace($path, '', 3, 0);
		echo substr_replace($path, '', 3, 1);
		echo substr_replace($path, 'x', 3, 0);
		echo substr_replace($path, '', 3);
	}

	/**
	 * @param array<string, callable(array<string>): string> $callbacks
	 */
	public function pregReplace(string $path, array $callbacks): void
	{
		echo preg_replace([], [], $path);
		echo preg_replace('/a/', 'b', $path);
		echo preg_replace_callback([], static fn (array $matches): string => '', $path);
		echo preg_replace_callback_array([], $path);
		echo preg_replace_callback_array($callbacks, $path);

		$count = 0;
		echo preg_replace([], [], $path, -1, $count);
		echo $count;
	}

}

class Unions
{

	public function searchAndReplaceFromSameUnion(string $path, bool $b): void
	{
		$search = $b ? 'a' : 'b';
		$replace = $b ? 'b' : 'a';
		echo str_replace($search, $replace, $path);
	}

	public function strtrFromSameUnion(string $path, bool $b): void
	{
		$from = $b ? 'a' : 'b';
		$to = $b ? 'b' : 'a';
		echo strtr($path, $from, $to);
	}

}
