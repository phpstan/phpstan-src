<?php declare(strict_types=1);

/**
 * @phpstan-type Filter array{skuId?: int, condition?: string}
 * @phpstan-type Request<TFilter of array<string, mixed>> array{filters?: TFilter, limit?: int}
 */
final class ProviderTypeError
{
    /**
     * @param Request<Filter> $req
     */
    public function find(array $req): void
    {
        $filters = $req['filters'] ?? [];

        // PHPStan now knows $filters is array{skuId?: int, condition?: string}
        // so this arithmetic on int + string IS caught:
        $bad = ($filters['skuId'] ?? 0) + 'hello';  // Error: binary +  with string

        // And this wrong-type pass is also caught:
        $this->takeString($filters['skuId'] ?? 0);  // Error: passing int where string expected
    }

    public function takeString(string $s): void {}
}

