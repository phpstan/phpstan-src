<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11844;

use function PHPStan\Testing\assertType;

class StaticPropertyCase
{
	/**
	 * @var \WeakMap<object, string>|null
	 */
	private static ?\WeakMap $map = null;

	public static function init(): void
	{
		if (self::$map === null) {
			self::$map = new \WeakMap();
			assertType('WeakMap<object, string>', self::$map);
		}
	}
}

class InstancePropertyCase
{
	/**
	 * @var \WeakMap<object, string>|null
	 */
	private ?\WeakMap $map = null;

	public function init(): void
	{
		if ($this->map === null) {
			$this->map = new \WeakMap();
			assertType('WeakMap<object, string>', $this->map);
		}
	}
}

/** @template T */
class GenericContainer
{
	/** @var T */
	private $value;

	/** @param T $value */
	public function __construct($value) {
		$this->value = $value;
	}
}

class NullOrFalsePropertyCase
{
	/**
	 * @var \WeakMap<object, string>|null|false
	 */
	private \WeakMap|null|false $map = false;

	public function init(): void
	{
		if ($this->map !== false) {
			if ($this->map === null) {
				$this->map = new \WeakMap();
				assertType('WeakMap<object, string>', $this->map);
			}
		}
	}

	public function reset(): void
	{
		$this->map = null;
	}
}

class StaticNullOrFalsePropertyCase
{
	/**
	 * @var \WeakMap<object, string>|null|false
	 */
	private static \WeakMap|null|false $map = false;

	public static function init(): void
	{
		if (self::$map !== false) {
			if (self::$map === null) {
				self::$map = new \WeakMap();
				assertType('WeakMap<object, string>', self::$map);
			}
		}
	}

	public static function reset(): void
	{
		self::$map = null;
	}
}

class OtherGenericCase
{
	/**
	 * @var \SplObjectStorage<object, string>|null
	 */
	private static ?\SplObjectStorage $storage = null;

	public static function init(): void
	{
		if (self::$storage === null) {
			self::$storage = new \SplObjectStorage();
			assertType('SplObjectStorage<object, string>', self::$storage);
		}
	}
}

/**
 * Custom generic class whose constructor does NOT reference template types.
 * This proves the fix is general, not WeakMap-specific.
 *
 * @template TKey of string
 * @template TValue
 */
class CustomGenericCache
{
	/** @var array<TKey, TValue> */
	private array $data = [];

	public function __construct()
	{
	}

	/**
	 * @param TKey $key
	 * @param TValue $value
	 */
	public function set(string $key, mixed $value): void
	{
		$this->data[$key] = $value;
	}
}

class CustomGenericPropertyCase
{
	/**
	 * @var CustomGenericCache<string, int>|null
	 */
	private ?CustomGenericCache $cache = null;

	public function init(): void
	{
		if ($this->cache === null) {
			$this->cache = new CustomGenericCache();
			assertType('Bug11844\CustomGenericCache<string, int>', $this->cache);
		}
	}
}

class StaticCustomGenericPropertyCase
{
	/**
	 * @var CustomGenericCache<string, int>|null
	 */
	private static ?CustomGenericCache $cache = null;

	public static function init(): void
	{
		if (self::$cache === null) {
			self::$cache = new CustomGenericCache();
			assertType('Bug11844\CustomGenericCache<string, int>', self::$cache);
		}
	}
}

/**
 * @template T of object
 * @template U
 */
class TemplatePropertyCase
{
	/**
	 * @var \WeakMap<T, U>|null
	 */
	private ?\WeakMap $map = null;

	public function init(): void
	{
		if ($this->map === null) {
			$this->map = new \WeakMap();
			assertType('WeakMap<T of object (class Bug11844\TemplatePropertyCase, argument), U (class Bug11844\TemplatePropertyCase, argument)>', $this->map);
		}
	}
}

/**
 * @template T of object
 * @template U
 */
class StaticTemplatePropertyCase
{
	/**
	 * @var \WeakMap<T, U>|null
	 */
	private static ?\WeakMap $map = null;

	public static function init(): void
	{
		if (self::$map === null) {
			self::$map = new \WeakMap();
			assertType('WeakMap<T of object (class Bug11844\StaticTemplatePropertyCase, argument), U (class Bug11844\StaticTemplatePropertyCase, argument)>', self::$map);
		}
	}
}
