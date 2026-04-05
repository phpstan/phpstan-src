<?php

declare(strict_types=1);

namespace Bug13325;

use FilesystemIterator;
use Iterator;
use RegexIterator;

final class Foo
{
	/** @return RegexIterator<mixed, mixed, Iterator> */
	public function __invoke(Iterator $iterator): RegexIterator
	{
		return new RegexIterator($iterator, 'string');
	}

	/** @return RegexIterator<mixed, mixed, FilesystemIterator> */
	public function createRegionIterator(): RegexIterator
	{
		return new RegexIterator(
			new FilesystemIterator('foo'),
			'/bar/',
		);
	}
}
