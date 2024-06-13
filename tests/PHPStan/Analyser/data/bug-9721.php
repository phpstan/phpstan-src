<?php // onlyif PHP_VERSION_ID >= 80000

namespace Bug9721;

use function PHPStan\Testing\assertType;

class Example {
	public function mergeWith(): self
	{
		return $this;
	}
}

function () {
	$mergedExample = null;
	$loop = 2;

	do {

		$example = new Example();
		$mergedExample = $mergedExample?->mergeWith() ?? $example;

		assertType(Example::class, $mergedExample);

		$loop--;
	} while ($loop);

};
