<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11844;

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
		}
	}
}
