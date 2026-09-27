<?php // lint >= 8.0

namespace TemplateArgumentYieldSend;

use function PHPStan\Testing\assertType;

/** @template T */
class Collection
{

	/** @param array<T> $items */
	public function __construct(array $items = [])
	{
	}

}

class Foo
{

	/** @return \Generator<int, Collection<int>> */
	public function yieldValue(): \Generator
	{
		$c = new Collection([1]);
		assertType('TemplateArgumentYieldSend\Collection<int>', $c);

		yield $c;
	}

	/** @return \Generator<Collection<string>, int> */
	public function yieldKey(): \Generator
	{
		$c = new Collection(['a']);
		assertType('TemplateArgumentYieldSend\Collection<string>', $c);

		yield $c => 1;
	}

	/** @return \Generator<int, Collection<int>> */
	public function yieldFrom(): \Generator
	{
		$c = new Collection([1]);
		assertType('TemplateArgumentYieldSend\Collection<int>', $c);

		yield from [$c];
	}

	/** @return iterable<int, Collection<int>> */
	public function yieldIterable(): iterable
	{
		$c = new Collection([1]);
		assertType('TemplateArgumentYieldSend\Collection<int>', $c);

		yield $c;
	}

	public function yieldUntyped(): \Generator
	{
		$c = new Collection([1]);
		assertType('TemplateArgumentYieldSend\Collection<1>', $c);

		yield $c;
	}

}
