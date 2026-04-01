<?php declare(strict_types = 1);

namespace GenericTypeAliases;

use function PHPStan\Testing\assertType;

/**
 * @phpstan-type Filter array{skuId?: int, condition?: string}
 *
 * @phpstan-type Request<TFilter of array<string, mixed> = array<string, mixed>> array{
 *     filters?: TFilter,
 *     limit?: int,
 *     offset?: int,
 * }
 */
abstract class Provider
{
	/**
	 * @param Request<Filter> $request
	 */
	abstract public function find(array $request): void;
}

class ConcreteProvider extends Provider
{
	public function find(array $request): void
	{
		// Access an optional key – PHPStan represents the array{filters?:Filter,...} type
		// as a union of the possible ConstantArrayType shapes (with/without the optional key).
		// The important thing is that Filter IS substituted: `filters` carries array{skuId?: int, condition?: string}.
		assertType('array{filters?: array{skuId?: int, condition?: string}, limit?: int, offset?: int}', $request);
	}
}

// -------------------------------------------------------
// Direct usage in the same class – simpler and more reliable test
// -------------------------------------------------------

/**
 * @phpstan-type AppraisalFilter array{skuId?: int, condition?: string}
 *
 * @phpstan-type ProviderRequest<TFilter of array<string, mixed>> array{
 *     filters?: TFilter,
 *     limit?: int,
 *     offset?: int,
 * }
 */
class DirectUsage
{
	/**
	 * @param ProviderRequest<AppraisalFilter> $request
	 */
	public function find(array $request): void
	{
		assertType('array{filters?: array{skuId?: int, condition?: string}, limit?: int, offset?: int}', $request);
	}
}

// -------------------------------------------------------
// Test with list<T>
// -------------------------------------------------------

/**
 * @phpstan-type Paged<TItem of object> array{items: list<TItem>, total: int}
 */
class Repo
{
	/**
	 * @param Paged<\stdClass> $result
	 */
	public function check(array $result): void
	{
		assertType('list<stdClass>', $result['items']);
		assertType('int', $result['total']);
	}
}

// -------------------------------------------------------
// Test with two template params
// -------------------------------------------------------

/**
 * @phpstan-type Map<TKey of array-key, TValue> array<TKey, TValue>
 */
class MapHolder
{
	/**
	 * @param Map<string, int> $m
	 */
	public function check(array $m): void
	{
		assertType('array<string, int>', $m);
	}
}

// -------------------------------------------------------
// Test with default template param value
// -------------------------------------------------------

/**
 * @phpstan-type WithDefault<T = string> array{value: T}
 */
class DefaultHolder
{
	/**
	 * @param WithDefault<int> $withInt
	 */
	public function check(array $withInt): void
	{
		assertType('int', $withInt['value']);
	}
}

// -------------------------------------------------------
// Test @phpstan-import-type of a generic alias
// -------------------------------------------------------

/**
 * @phpstan-import-type Map from MapHolder
 * @phpstan-import-type Paged from Repo
 */
class ImportConsumer
{
	/**
	 * @param Map<string, bool> $m
	 */
	public function mapCheck(array $m): void
	{
		assertType('array<string, bool>', $m);
	}

	/**
	 * @param Paged<\DateTime> $p
	 */
	public function pagedCheck(array $p): void
	{
		assertType('list<DateTime>', $p['items']);
		assertType('int', $p['total']);
	}
}


