<?php declare(strict_types = 1);

namespace ClosureBindScopeNestedClass;

use Closure;

class Foo
{

	protected const A = 'Foo';

}

// a class declared inside a bound closure has its own self/parent/static
$o = Closure::bind(static function () {
	return new class {

		public const Z = 'z';

		public function f(): string
		{
			return self::Z;
		}

		public static function g(): string
		{
			return static::Z;
		}

		public function h(): self
		{
			return new self();
		}

		public function i(): static
		{
			return new static();
		}

	};
}, null, Foo::class)();

// and so does a function declared inside one
Closure::bind(static function (): void {
	if (!function_exists('ClosureBindScopeNestedClass\\inner')) {
		function inner(): string
		{
			return self::A;
		}
	}
}, null, Foo::class);

class Container
{

	public function run(): void
	{
		Closure::bind(static function () {
			return new class extends Foo {

				public function k(): string
				{
					return self::A . parent::A;
				}

				public function l(): parent
				{
					return new parent();
				}

			};
		}, null, Container::class)();
	}

}
