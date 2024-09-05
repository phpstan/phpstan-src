<?php // lint >= 8.1
declare(strict_types = 1);

namespace Bug11337;

use function array_filter;

class Foo
{

	/**
	 * @return array<\stdClass>
	 */
	public function testFunction(): array
	{
		$objects = [
			new \stdClass(),
			null,
			new \stdClass(),
			null,
		];

		return array_filter($objects, is_object(...));
	}

	/**
	 * @return array<1|2>
	 */
	public function testMethod(): array
	{
		$objects = [
			1,
			2,
			-4,
			0,
			-1,
		];

		return array_filter($objects, $this->isPositive(...));
	}

	/**
	 * @return array<'foo'|'bar'>
	 */
	public function testStaticMethod(): array
	{
		$objects = [
			'',
			'foo',
			'',
			'bar',
		];

		return array_filter($objects, self::isNonEmptyString(...));
	}

	/**
	 * @phpstan-assert-if-true int<1, max> $n
	 */
	private function isPositive(int $n): bool
	{
		return $n > 0;
	}

	/**
	 * @phpstan-assert-if-true non-empty-string $str
	 */
	private static function isNonEmptyString(string $str): bool
	{
		return \strlen($str) > 0;
	}
}
