<?php declare(strict_types = 1);

namespace Bug14324;

use function PHPStan\Testing\assertType;

final class Test
{
	private const ADDITIONAL_MAPS = [
		'foo-',
		'bar-',
		'baz-',
	];

	/** @var array<string, callable(): string> */
	private static array $map = [];

	public function createMap(): void
	{
		if ([] === self::$map) {
			// 29 entries
			self::$map = [
				'foo' => static fn() => 'foo',
				'bar' => static fn() => 'bar',
				'baz' => static fn() => 'baz',
				'qux' => static fn() => 'qux',
				'quux' => static fn() => 'quux',
				'corge' => static fn() => 'corge',
				'grault' => static fn() => 'grault',
				'garply' => static fn() => 'garply',
				'waldo' => static fn() => 'waldo',
				'fred' => static fn() => 'fred',
				'plugh' => static fn() => 'plugh',
				'xyzzy' => static fn() => 'xyzzy',
				'thud' => static fn() => 'thud',
				'foo1' => static fn() => 'foo1',
				'bar1' => static fn() => 'bar1',
				'baz1' => static fn() => 'baz1',
				'qux1' => static fn() => 'qux1',
				'quux1' => static fn() => 'quux1',
				'corge1' => static fn() => 'corge1',
				'grault1' => static fn() => 'grault1',
				'garply1' => static fn() => 'garply1',
				'waldo1' => static fn() => 'waldo1',
				'fred1' => static fn() => 'fred1',
				'plugh1' => static fn() => 'plugh1',
				'xyzzy1' => static fn() => 'xyzzy1',
				'thud1' => static fn() => 'thud1',
				'foo2' => static fn() => 'foo2',
				'bar2' => static fn() => 'bar2',
				'baz2' => static fn() => 'baz2',
			];
			assertType("array{foo: Closure(): 'foo', bar: Closure(): 'bar', baz: Closure(): 'baz', qux: Closure(): 'qux', quux: Closure(): 'quux', corge: Closure(): 'corge', grault: Closure(): 'grault', garply: Closure(): 'garply', waldo: Closure(): 'waldo', fred: Closure(): 'fred', plugh: Closure(): 'plugh', xyzzy: Closure(): 'xyzzy', thud: Closure(): 'thud', foo1: Closure(): 'foo1', bar1: Closure(): 'bar1', baz1: Closure(): 'baz1', qux1: Closure(): 'qux1', quux1: Closure(): 'quux1', corge1: Closure(): 'corge1', grault1: Closure(): 'grault1', garply1: Closure(): 'garply1', waldo1: Closure(): 'waldo1', fred1: Closure(): 'fred1', plugh1: Closure(): 'plugh1', xyzzy1: Closure(): 'xyzzy1', thud1: Closure(): 'thud1', foo2: Closure(): 'foo2', bar2: Closure(): 'bar2', baz2: Closure(): 'baz2'}", self::$map);

			foreach (self::ADDITIONAL_MAPS as $map) {
				// added with 3 entries, breaching the closure limit of 32 entries
				self::$map[$map] = fn () => self::$map['foo']();
			}

			assertType("non-empty-array<'bar'|'bar-'|'bar1'|'bar2'|'baz'|'baz-'|'baz1'|'baz2'|'corge'|'corge1'|'foo'|'foo-'|'foo1'|'foo2'|'fred'|'fred1'|'garply'|'garply1'|'grault'|'grault1'|'plugh'|'plugh1'|'quux'|'quux1'|'qux'|'qux1'|'thud'|'thud1'|'waldo'|'waldo1'|'xyzzy'|'xyzzy1', callable(): mixed>&oversized-array", self::$map);
		}
	}
}
