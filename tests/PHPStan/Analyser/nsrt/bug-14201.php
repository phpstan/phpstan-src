<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14201;

use function PHPStan\Testing\assertType;

class Foo { public function __construct(public string $fooName) {}}
class Bar { public function __construct(public string $barName) {}}

class HelloWorld
{
	/**
	 * @param Foo[]|Bar[] $items
	 */
	public function doitMatch(array $items): void
	{
		if ([] === $items) {return; }

		$first = reset($items);
		match (true) {
			$first instanceOf Foo => array_map(function ($i) {
				assertType('Bug14201\Foo', $i);
				return $i->fooName;
			}, $items),
			$first instanceOf Bar => array_map(function ($i) {
				assertType('Bug14201\Bar', $i);
				return $i->barName;
			}, $items),
			default => throw new \RuntimeException('None of Foo nor Bar')
		};
	}

	/**
	 * @param Foo[]|Bar[] $items
	 */
	public function doitIf(array $items): void
	{
		if ([] === $items) {return; }

		$first = reset($items);
		if ($first instanceof Foo) {
			assertType('non-empty-array<Bug14201\Foo>', $items);
			array_map(function ($i) {
				assertType('Bug14201\Foo', $i);
				return $i->fooName;
			}, $items);
		} elseif ($first instanceof Bar) {
			assertType('non-empty-array<Bug14201\Bar>', $items);
			array_map(function ($i) {
				assertType('Bug14201\Bar', $i);
				return $i->barName;
			}, $items);
		}
	}
}
