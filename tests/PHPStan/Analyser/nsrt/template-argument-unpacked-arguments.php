<?php // lint >= 8.0

namespace TemplateArgumentUnpackedArguments;

use function PHPStan\Testing\assertType;

/** @template T */
class Collection
{

	/** @param T $value */
	public function add($value): void
	{
	}

}

class Foo
{

	/** @param list<int> $ints */
	public function unpackedList(array $ints): void
	{
		$c = new Collection();
		$c->add(...$ints);
		assertType('TemplateArgumentUnpackedArguments\Collection<int>', $c);
	}

	public function unpackedConstantArray(): void
	{
		$c = new Collection();
		$c->add(1);
		$d = new Collection();
		$d->add(...[1]);
		assertType('TemplateArgumentUnpackedArguments\Collection<1>', $c);
		assertType('TemplateArgumentUnpackedArguments\Collection<1>', $d);
	}

	public function unpackedByName(): void
	{
		$c = new Collection();
		$c->add(...['value' => 'x']);
		assertType("TemplateArgumentUnpackedArguments\\Collection<'x'>", $c);
	}

}
