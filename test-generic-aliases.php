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
