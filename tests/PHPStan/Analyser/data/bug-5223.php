<?php declare(strict_types = 1);

namespace Bug5223;

use function PHPStan\Testing\assertType;

class HelloWorld
{

	/**
	 * @param array{
	 *     categoryKeys: string[],
	 *     tagNames: string[],
	 * } $filters
	 */
	public function withUnset(array $filters): void
	{
		assertType("array{categoryKeys: array<string>, tagNames: array<string>}", $filters);

		unset($filters['page']); // @phpstan-ignore-line
		assertType("array{categoryKeys: array<string>, tagNames: array<string>}", $filters);

		unset($filters['limit']); // @phpstan-ignore-line // this line should also throw a phpstan error
		assertType("array{categoryKeys: array<string>, tagNames: array<string>}", $filters);

		assertType('*ERROR*', $filters['something']);
		var_dump($filters['something']); // phpstan should error here?

		$this->test($filters);
	}

	/**
	 * @param array{
	 *     categoryKeys: string[],
	 *     tagNames: string[],
	 * } $filters
	 */
	public function withoutUnset(array $filters): void
	{
		assertType("array{categoryKeys: array<string>, tagNames: array<string>}", $filters);
		assertType('*ERROR*', $filters['something']);
		var_dump($filters['something']); // @phpstan-ignore-line

		$this->test($filters);
	}

	/**
	 * @param array{
	 *     categoryKeys: string[],
	 *     tagNames: string[],
	 * } $filters
	 */
	private function test(array $filters): void
	{
	}
}
