<?php // lint >= 8.0
declare(strict_types = 1);

namespace NoNamedArgumentsNativeType;

final class QueryBuilder
{

	/**
	 * @no-named-arguments
	 */
	public function groupBy(string ...$groupBy): void
	{
		self::validateVariadicParameter($groupBy);
	}

	/**
	 * @template TItem
	 * @param TItem[] $parameter
	 * @phpstan-assert list<TItem> $parameter
	 */
	private static function validateVariadicParameter(array $parameter): void
	{
		if (array_is_list($parameter)) {
			return;
		}

		throw new \InvalidArgumentException();
	}

}
