<?php declare(strict_types = 1);

namespace MethodCallBreakIterator;

function doFoo(\IntlBreakIterator $it): void
{
	// move the iterator to a boundary
	$it->isBoundary(1);
	$it->first();

	// only reads the iterator
	$it->current();
}
