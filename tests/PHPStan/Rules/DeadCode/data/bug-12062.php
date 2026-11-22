<?php // lint >= 8.0

declare(strict_types=1);

namespace Bug12062;

use InvalidArgumentException;

interface IKeyStore
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value, int $time = 10800): bool;

    public function delete(string $key): bool;

    public function increment(string $key, int $n = 1, int $initial = 1, int $expiry = 0): bool|int;

    public function touch(string $key, int $expiry = 10800): bool;
}

abstract class Base
{
    public static function get(IKeyStore $cache, mixed $param): mixed
    {
        static::validateParam($param);
        $value = $cache->get(static::getCacheKey($param));
        if ($value === false) {
            $value = static::fetchValue($param);
            static::set($cache, $param, $value);
        }
        return $value;
    }

    public static function set(IKeyStore $cache, mixed $param, mixed $value): void
    {
        static::validateParam($param);
        $cache->set(
            static::getCacheKey($param),
            $value,
            static::getCacheDuration($param)
        );
    }

    public static function clear(IKeyStore $cache, mixed $param): void
    {
        static::validateParam($param);
        $cache->delete(static::getCacheKey($param));
    }

    abstract protected static function getCacheKey(mixed $param): string;

    abstract protected static function getCacheDuration(mixed $param): int;

    protected static function validateParam(mixed $param): bool
    {
        return true;
    }

    abstract protected static function fetchValue(mixed $param): mixed;
}

abstract class IntParam extends Base
{
    protected static function validateParam(mixed $param): bool
    {
        if (!is_int($param) && !ctype_digit($param)) {
            throw new InvalidArgumentException(
                'Paremeter given to IntParam CachedValue must be either integer or string consisting of numeric values'
            );
        }
        return true;
    }
}

final class ExampleValue extends IntParam
{
    protected static function getCacheKey(mixed $param): string
    {
        return 'example_value';
    }

    protected static function getCacheDuration(mixed $param): int
    {
        return 3600;
    }

    protected static function fetchValue(mixed $param): int
    {
        return $param * 2;
    }
}

abstract class SelfCall
{
    public static function get(mixed $param): void
    {
        self::validateParam($param);
    }

    protected static function validateParam(mixed $param): bool
    {
        return true;
    }
}

final class FinalStaticCall
{
    public static function get(mixed $param): void
    {
        static::validateParam($param);
    }

    protected static function validateParam(mixed $param): bool
    {
        return true;
    }
}
