<?php declare(strict_types = 1);

namespace Bug12786;

use function PHPStan\Testing\assertType;

class A
{
	/** @param ?string[] $should_be_array */
	public function __construct(
		public ?array $should_be_array,
	)
	{
	}

	/** @param array{y?: string|string[]} $x */
	public static function fromRequest(array $x): self
	{
		if (isset($x['y']) && is_string($x['y'])) {
			$x['y'] = explode(',', $x['y']);
		} // $x['y'] can't be string anymore

		assertType('array<string>|null', $x['y'] ?? null);

		return new self(
			should_be_array: $x['y'] ?? null,
		);
	}
}
