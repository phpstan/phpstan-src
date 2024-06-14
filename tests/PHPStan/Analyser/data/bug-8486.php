<?php // onlyif PHP_VERSION_ID >= 80100

namespace Bug8486;

use function PHPStan\Testing\assertType;

enum Operator: string
{
	case Foo = 'foo';
	case Bar = 'bar';
	case None = '';

	public function explode(): void
	{
		$character = match ($this) {
			self::None => 'baz',
			default => $this->value,
		};

		assertType("'bar'|'baz'|'foo'", $character);
	}

	public function typeInference(): void
	{
		match ($this) {
			self::None => 'baz',
			default => assertType('$this(Bug8486\Operator~Bug8486\Operator::None)', $this),
		};
	}

	public function typeInference2(): void
	{
		if ($this === self::None) {
			return;
		}

		assertType("'Bar'|'Foo'", $this->name);
		assertType("'bar'|'foo'", $this->value);
	}
}

class Foo
{

	public function doFoo(Operator $operator)
	{
		$character = match ($operator) {
			Operator::None => 'baz',
			default => $operator->value,
		};

		assertType("'bar'|'baz'|'foo'", $character);
	}

	public function typeInference(Operator $operator): void
	{
		match ($operator) {
			Operator::None => 'baz',
			default => assertType('Bug8486\Operator~Bug8486\Operator::None', $operator),
		};
	}

	public function typeInference2(Operator $operator): void
	{
		if ($operator === Operator::None) {
			return;
		}

		assertType("'Bar'|'Foo'", $operator->name);
		assertType("'bar'|'foo'", $operator->value);
	}

	public function typeInference3(Operator $operator): void
	{
		if ($operator === Operator::None) {
			return;
		}

		if ($operator === Operator::Foo) {
			return;
		}

		assertType("Bug8486\Operator::Bar", $operator);
		assertType("'Bar'", $operator->name);
		assertType("'bar'", $operator->value);
	}

}
