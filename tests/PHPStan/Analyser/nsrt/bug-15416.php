<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15416;

use function PHPStan\Testing\assertType;

class Repro
{
	/** @return Cache<string> */
	public static function getCache(): Cache
	{
		static $cache = new Cache(Cache::strings());
		assertType('Bug15416\Cache<string>', $cache);
		return $cache;
	}

	/** @return Cache<string> */
	public static function getLazyCache(): Cache
	{
		static $cache = null;
		if ($cache === null) {
			$cache = new Cache(Cache::strings());
		}
		assertType('Bug15416\Cache<string>', $cache);
		return $cache;
	}

	/** @return Cache<string> */
	public static function getWrappedCache(): Cache
	{
		static $cache = wrap(Cache::strings());
		assertType('Bug15416\Cache<string>', $cache);
		return $cache;
	}

	/** @return array{Cache<string>, Cache<int>} */
	public static function getCaches(): array
	{
		static $strings = new Cache(Cache::strings());
		static $ints = new Cache([1, 2]);
		assertType('Bug15416\Cache<string>', $strings);
		assertType('Bug15416\Cache<int>', $ints);
		return [$strings, $ints];
	}

	public static function sendCache(): void
	{
		static $cache = new Cache(Cache::strings());
		assertType('Bug15416\Cache<string>', $cache);
		self::takeCache($cache);
	}

	/** @param Cache<string> $cache */
	public static function takeCache(Cache $cache): void
	{
	}

	public static function closure(): void
	{
		$f = /** @return Cache<string> */ function (): Cache {
			static $cache = new Cache(Cache::strings());
			assertType('Bug15416\Cache<string>', $cache);
			return $cache;
		};
	}
}

/**
 * @template T
 */
class Cache
{
	/** @param list<T> $x */
	public function __construct(public array $x) { }

	/** @return list<string> */
	public static function strings(): array { return ['a']; }
}

/**
 * @template T
 * @param list<T> $x
 * @return Cache<T>
 */
function wrap(array $x): Cache
{
	return new Cache($x);
}

/** @return Cache<string> */
function getCache(): Cache
{
	static $cache = new Cache(Cache::strings());
	assertType('Bug15416\Cache<string>', $cache);
	return $cache;
}
