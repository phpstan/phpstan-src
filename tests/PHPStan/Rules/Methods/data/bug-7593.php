<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug7593;

/** @template T */
class Collection {
	/** @param T $item */
	public function add(mixed $item): void {}
}

class Foo {}
class Bar {}

class CollectionManager
{
	/**
	* @param Collection<Foo> $fooCollection
	* @param Collection<Bar> $barCollection
	*/
	public function __construct(
		private Collection $fooCollection,
		private Collection $barCollection,
	) {}

	public function updateCollection(Foo|Bar $foobar): void
	{
		(match(get_class($foobar)) {
			Foo::class => $this->fooCollection,
			Bar::class => $this->barCollection,
			default => throw new LogicException(),
		})->add($foobar);
	}
}
