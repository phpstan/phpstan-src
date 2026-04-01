<?php declare(strict_types=1);

// ---------------------------------------------------------------------------
// Generic @phpstan-type demo
// ---------------------------------------------------------------------------
use function PHPStan\dumpType;

/**
 * @template ProviderFilter of array<string, mixed>
 * @phpstan-type ProviderRequest<TFilter of ProviderFilter> array{
 *     filters?: TFilter,
 *     limit?: int,
 *     offset?: int,
 * }
 */
abstract class Provider
{
    /**
     * @param ProviderRequest<ProviderFilter> $request
     * @return array<mixed>
     */
    public function find(array $request): array {
		return [];
	}
}

/**
 * @phpstan-type AppraisalFilter array{skuId?: int, condition?: string}
 * @extends Provider<AppraisalFilter>
 */
final class SkuProvider extends Provider
{
    #[\Override]
    public function find(array $request): array
    {
//		dumpType($request);
        // PHPStan now knows $request is array{filters?: array{skuId?: int, condition?: string}, ...}
        $filters = $request['filters'] ?? [];

        // This is int|null, not mixed!
        $skuId = $filters['skuId'] ?? null;

        return [$skuId];
    }
}

// ---------------------------------------------------------------------------
// Two-param alias
// ---------------------------------------------------------------------------

/**
 * @phpstan-type Pair<TFirst, TSecond> array{first: TFirst, second: TSecond}
 */
final class PairHolder
{
    /**
     * @param Pair<string, int> $pair
     */
    public function use(array $pair): void
    {
        echo $pair['first'];  // string
        echo $pair['second']; // int
    }
}

// ---------------------------------------------------------------------------
// With default
// ---------------------------------------------------------------------------

/**
 * @phpstan-type Response<TData = array<mixed>> array{data: TData, status: int}
 */
final class ApiClient
{
    /**
     * @return Response<array{id: int, name: string}>
     */
    public function getUser(): array
    {
        return ['data' => ['id' => 1, 'name' => 'Alice'], 'status' => 200];
    }
}

// ---------------------------------------------------------------------------
// @return of generic alias
// ---------------------------------------------------------------------------

/**
 * @phpstan-type Page<TItem of object> array{items: list<TItem>, total: int, page: int}
 */
final class PagedRepo
{
    /**
     * @return Page<\stdClass>
     */
    public function getPage(): array
    {
        dumpType($this->getPage());  // should show array{items: list<stdClass>, total: int, page: int}
        return ['items' => [], 'total' => 0, 'page' => 1];
    }
}

// ---------------------------------------------------------------------------
// @var property annotation
// ---------------------------------------------------------------------------

/**
 * @phpstan-type Config<TValue> array{key: string, value: TValue}
 */
final class Settings
{
    /** @var Config<int> */
    public array $timeout = ['key' => 'timeout', 'value' => 30];

    /** @var Config<string> */
    public array $name = ['key' => 'name', 'value' => 'default'];

    public function check(): void
    {
        dumpType($this->timeout['value']); // int
        dumpType($this->name['value']);    // string
    }
}

// ---------------------------------------------------------------------------
// Nested generic alias (alias referencing another generic alias with type args)
// ---------------------------------------------------------------------------

/**
 * @phpstan-type Item<T> array{id: int, data: T}
 * @phpstan-type ItemList<T> list<Item<T>>
 */
final class ItemRepo
{
    /**
     * @param ItemList<string> $items
     */
    public function process(array $items): void
    {
        dumpType($items);          // list<array{id: int, data: string}>
        dumpType($items[0]['data']); // string
    }
}

// ---------------------------------------------------------------------------
// @phpstan-import-type of a generic alias, then used with type args
// ---------------------------------------------------------------------------

/**
 * @phpstan-import-type Pair from PairHolder
 */
final class PairConsumer
{
    /**
     * @param Pair<int, bool> $p
     */
    public function check(array $p): void
    {
        dumpType($p['first']);  // int
        dumpType($p['second']); // bool
    }
}

// ---------------------------------------------------------------------------
// Default type arg — using alias WITHOUT args should be OK (default kicks in)
// ---------------------------------------------------------------------------

/**
 * @phpstan-type WithDefault<T = string> array{value: T}
 */
final class DefaultConsumer
{
    /**
     * @param WithDefault<int> $explicit   no error: type arg provided
     * @param WithDefault      $implicit   no error: T has a default
     */
    public function check(array $explicit, array $implicit): void
    {
        dumpType($explicit['value']); // int
        dumpType($implicit['value']); // BUG: shows raw TemplateType instead of string — default not applied when alias used without args
    }
}

// ---------------------------------------------------------------------------
// Generic alias in a standalone function (not a class method)
// ---------------------------------------------------------------------------

/**
 * @phpstan-type Range<T of int|float> array{min: T, max: T}
 */
final class RangeHolder
{
    /**
     * @param Range<int> $r
     * @return Range<float>
     */
    public function convert(array $r): array
    {
        dumpType($r['min']); // int
        return ['min' => (float) $r['min'], 'max' => (float) $r['max']];
    }
}

// ---------------------------------------------------------------------------
// Too many type args — should error
// ---------------------------------------------------------------------------

/**
 * @phpstan-type Single<T> array{value: T}
 */
final class TooManyArgs
{
    /**
     * @param Single<int, string> $x   TODO: should error — Single takes 1 type arg, 2 given (not yet detected)
     */
    public function check(array $x): void {}
}

// ---------------------------------------------------------------------------
// Too few required type args (partial application of multi-param alias) — should error
// ---------------------------------------------------------------------------

/**
 * @phpstan-type KeyValue<TKey of array-key, TValue> array{key: TKey, value: TValue}
 */
final class TooFewArgs
{
    /**
     * @param KeyValue<string> $x   TODO: should error — KeyValue requires 2 type args (not yet detected)
     */
    public function check(array $x): void {}
}
