<?php

namespace TentativeReturnTypes;

class Foo implements \IteratorAggregate
{

	public function getIterator()
	{

	}

}

class Bar implements \IteratorAggregate
{

	#[\ReturnTypeWillChange]
	public function getIterator()
	{

	}

}

class Baz implements \IteratorAggregate
{

	#[\ReturnTypeWillChange]
	public function getIterator(): string
	{

	}

}

class Lorem implements \IteratorAggregate
{

	public function getIterator(): string
	{

	}

}
