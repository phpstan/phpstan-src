<?php declare(strict_types = 1);

namespace CyclicPhpDocs;

interface Foo extends \IteratorAggregate
{
	/** @return iterable<Foo> | Foo */
	#[\ReturnTypeWillChange]
	public function getIterator();
}
