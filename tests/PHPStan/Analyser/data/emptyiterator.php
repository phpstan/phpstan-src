<?php

namespace EmptyIteratorTest;

use function PHPStan\Testing\assertType;

class Foo
{
	public function doFoo(\EmptyIterator $it): void
	{
		assertType('EmptyIterator', $it);
		assertType('*NEVER*', $it->key());
		assertType('*NEVER*', $it->current());
		assertType('void', $it->next());
		assertType('false', $it->valid());
	}

}
