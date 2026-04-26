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
// Two template params
// -------------------------------------------------------

/**
 * @phpstan-type Pair<TFirst, TSecond> array{first: TFirst, second: TSecond}
 */
class PairHolder
{
	/**
	 * @param Pair<string, int> $pair
	 */
	public function check(array $pair): void
	{
		assertType('string', $pair['first']);
		assertType('int', $pair['second']);
	}
}

// -------------------------------------------------------
// @return of generic alias with bound constraint
// -------------------------------------------------------

/**
 * @phpstan-type Range<T of int|float> array{min: T, max: T}
 */
class RangeHolder
{
	/**
	 * @param  Range<int>   $r
	 * @return Range<float>
	 */
	public function convert(array $r): array
	{
		assertType('int', $r['min']);
		assertType('int', $r['max']);
		$result = ['min' => (float) $r['min'], 'max' => (float) $r['max']];
		assertType('array{min: float, max: float}', $result);
		return $result;
	}
}

// -------------------------------------------------------
// @var property annotation
// -------------------------------------------------------

/**
 * @phpstan-type Config<TValue> array{key: string, value: TValue}
 */
class Settings
{
	/** @var Config<int> */
	public array $timeout = ['key' => 'timeout', 'value' => 30];

	/** @var Config<string> */
	public array $name = ['key' => 'name', 'value' => 'default'];

	public function check(): void
	{
		assertType('int', $this->timeout['value']);
		assertType('string', $this->name['value']);
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
// Nested generic alias (alias referencing another generic alias)
// -------------------------------------------------------

/**
 * @phpstan-type Item<T> array{id: int, data: T}
 * @phpstan-type ItemList<T> list<Item<T>>
 */
class ItemRepo
{
	/**
	 * @param ItemList<string> $items
	 */
	public function process(array $items): void
	{
		assertType('list<array{id: int, data: string}>', $items);
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
// Default param: explicit arg vs bare usage (default applied)
// -------------------------------------------------------

/**
 * @phpstan-type WithDefault<T = string> array{value: T}
 */
class DefaultHolder
{
	/**
	 * @param WithDefault<int> $explicit   explicit arg overrides default
	 * @param WithDefault      $implicit   bare usage – T defaults to string
	 */
	public function check(array $explicit, array $implicit): void
	{
		assertType('int', $explicit['value']);
		assertType('string', $implicit['value']);
	}
}

// -------------------------------------------------------
// @phpstan-import-type of a generic alias
// -------------------------------------------------------

/**
 * @phpstan-import-type Map from MapHolder
 * @phpstan-import-type Paged from Repo
 * @phpstan-import-type Pair from PairHolder
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

	/**
	 * @param Pair<int, bool> $p
	 */
	public function pairCheck(array $p): void
	{
		assertType('int', $p['first']);
		assertType('bool', $p['second']);
	}
}
