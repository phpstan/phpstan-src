<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug13421;

use function PHPStan\Testing\assertType;

enum Bar
{
	case Bar1;
	case Bar2;
	case Bar3;
}

/**
 * @param non-empty-array<Bar> $nonEmptyFilterArray
 */
function test(array $nonEmptyFilterArray): void
{
	$bars = [Bar::Bar1, Bar::Bar2, Bar::Bar3];

	$filteredBars = array_filter($bars, fn (Bar $bar) => in_array($bar, $nonEmptyFilterArray));

	assertType("array{0?: Bug13421\Bar::Bar1, 1?: Bug13421\Bar::Bar2, 2?: Bug13421\Bar::Bar3}", $filteredBars);
}
