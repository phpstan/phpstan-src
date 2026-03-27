<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug7599;

trait TraitForEnum
{
	/**
	 * @return array<int, string>
	 */
	public static function fooMethod(): array
	{
		return array_map(
			fn(self $enum): string => method_exists($enum, 'barMethod')
				? $enum->barMethod()
				: $enum->name,
			static::cases()
		);
	}
}

enum TestEnum: string
{
	use TraitForEnum;

	case Foo = 'foo';
	case Bar = 'bar';
}

enum SecondEnum: string
{
	use TraitForEnum;

	case Baz = 'baz';

	public function barMethod(): string
	{
		return 'blah';
	}
}
