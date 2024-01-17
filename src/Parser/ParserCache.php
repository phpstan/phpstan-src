<?php declare(strict_types = 1);

namespace PHPStan\Parser;

/**
 * @template TValue
 */
interface ParserCache
{

	/**
	 * @return TValue
	 */
	public function get(string $key);

	/**
	 * @param TValue $value
	 */
	public function put(string $key, $value): void;

	public function has(string $key): bool;

	public function getCount(): int;

}
