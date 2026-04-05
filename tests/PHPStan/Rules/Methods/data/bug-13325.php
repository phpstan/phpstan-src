<?php

declare(strict_types=1);

namespace Bug13325;

use Iterator;
use RegexIterator;
use Traversable;

final class Foo
{
	/** @return RegexIterator<mixed, mixed, Traversable<mixed, mixed>> */
	public function __invoke(Iterator $iterator): RegexIterator
	{
		return new RegexIterator($iterator, 'string');
	}
}
