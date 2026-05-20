<?php // lint >= 8.0

namespace Bug9392;

use function PHPStan\Testing\assertType;

class Range
{
	public function __construct(
		public ?string $notInRangeMessage = null,
		public mixed $min = null,
		public mixed $max = null,
	) {
	}
}

function () {
	new Range(
		min: $min = 20 * 100,
		max: $max = 5_000 * 100,
		notInRangeMessage: sprintf('The price must be between %s and %s.', round($min / 100, 2), round($max / 100, 2)),
	);

	assertType('2000', $min);
	assertType('500000', $max);
};
