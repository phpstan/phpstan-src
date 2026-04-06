<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug11430;

/**
 * @template T
 */
interface Option {}

/**
 * @template T
 * @implements Option<T>
 */
class Some implements Option
{
	/**
	 * @param T $value
	 */
	public function __construct(public mixed $value) {}
}

/**
 * @implements Option<never>
 */
class None implements Option {}

/**
 * @internal
 */
final class Choice
{
	/**
	 * @template T
	 * @template S
	 *
	 * @param T $value
	 * @param S $none
	 *
	 * @return (T is S ? None : Some<T>)
	 */
	public static function from(mixed $value, mixed $none = null): Option
	{
		if ($value === $none) {
			return new None();
		}

		return new Some($value);
	}
}
