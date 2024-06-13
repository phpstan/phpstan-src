<?php // onlyif PHP_VERSION_ID >= 70400

namespace Bug4188Types;

interface A {}
interface B {}

use function PHPStan\Testing\assertType;

class Test
{
	/** @param array<A|B> $data */
	public function set(array $data): void
	{
		$filtered = array_filter(
			$data,
			function ($param): bool {
				return $param instanceof B;
			},
		);
		assertType('array<Bug4188Types\B>', $filtered);

		$this->onlyB($filtered);
	}

	/** @param array<A|B> $data */
	public function setShort(array $data): void
	{
		$filtered = array_filter(
			$data,
			fn($param): bool => $param instanceof B,
		);
		assertType('array<Bug4188Types\B>', $filtered);

		$this->onlyB($filtered);
	}

	/** @param B[] $data */
	public function onlyB(array $data): void {}
}
