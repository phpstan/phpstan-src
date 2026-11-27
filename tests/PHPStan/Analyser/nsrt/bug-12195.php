<?php declare(strict_types = 1);

namespace Bug12195;

use function PHPStan\Testing\assertType;

/**
 * @param list<string>|array{0: null} $list
 */
function test(array $list): void
{
	assertType('array{null}|list<string>', $list);
}
