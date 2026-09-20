<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14980;

use function PHPStan\Testing\assertType;

/**
 * @param list<int> $ints
 */
function decimalIntStringKeys(array $ints): void
{
	$keys = [];
	foreach ($ints as $int) {
		$keys[] = $int;
		$keys[] = (string) $int;
	}

	assertType('list<int|decimal-int-string>', $keys);
	assertType('array<int, true>', array_fill_keys($keys, true));
}

/**
 * @param list<numeric-string> $keys
 */
function numericStringKeys(array $keys): void
{
	assertType('array<int|numeric-string, true>', array_fill_keys($keys, true));
}

function fillKeysMixedArray(mixed $m): void
{
	if (is_array($m)) {
		assertType('array<true>', array_fill_keys($m, true));
	}
}

/**
 * @param list<int> $ints
 */
function combineDecimalIntStringKeys(array $ints): void
{
	$keys = [];
	foreach ($ints as $int) {
		$keys[] = $int;
		$keys[] = (string) $int;
	}

	assertType('array<int, int|decimal-int-string>', array_combine($keys, $keys));
}

/**
 * @param list<numeric-string> $keys
 * @param list<string> $values
 */
function combineNumericStringKeys(array $keys, array $values): void
{
	assertType('array<int|numeric-string, string>', array_combine($keys, $values));
}

class DecimalIntBug
{

	/** @var array<int, true> */
	private array $data = [];

	/**
	 * @param list<int> $ints
	 */
	public function set(array $ints): void
	{
		$keys = [];
		foreach ($ints as $int) {
			$keys[] = $int;
			$keys[] = (string) $int;
		}

		$this->data = array_fill_keys($keys, true);
	}

}
