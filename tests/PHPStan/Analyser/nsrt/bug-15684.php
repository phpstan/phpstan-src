<?php declare(strict_types = 1);

namespace Bug15684;

use function PHPStan\Testing\assertType;

function doFoo(): void
{
	assertType("' a'", chop(' a ', ' '));
	assertType("'yax'", chop('yax', 'y'));
	assertType("' a'", rtrim(' a ', ' '));
	assertType('true', chop(' a ', ' ') === ' a');
}
