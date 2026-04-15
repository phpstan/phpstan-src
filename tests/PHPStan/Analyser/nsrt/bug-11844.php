<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11844Nsrt;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @var \WeakMap<object, string>|null
	 */
	private static ?\WeakMap $staticMap = null;

	/**
	 * @var \WeakMap<object, string>|null
	 */
	private ?\WeakMap $instanceMap = null;

	public static function initStatic(): void
	{
		if (self::$staticMap === null) {
			self::$staticMap = new \WeakMap();
			assertType('WeakMap<object, string>', self::$staticMap);
		}
	}

	public function initInstance(): void
	{
		if ($this->instanceMap === null) {
			$this->instanceMap = new \WeakMap();
			assertType('WeakMap<object, string>', $this->instanceMap);
		}
	}
}

class Bar
{
	/**
	 * @var \WeakMap<object, string>|null|false
	 */
	private \WeakMap|null|false $instanceMap = false;

	/**
	 * @var \WeakMap<object, string>|null|false
	 */
	private static \WeakMap|null|false $staticMap = false;

	public function initInstance(): void
	{
		if ($this->instanceMap !== false) {
			if ($this->instanceMap === null) {
				$this->instanceMap = new \WeakMap();
				assertType('WeakMap<object, string>', $this->instanceMap);
			}
		}
	}

	public static function initStatic(): void
	{
		if (self::$staticMap !== false) {
			if (self::$staticMap === null) {
				self::$staticMap = new \WeakMap();
				assertType('WeakMap<object, string>', self::$staticMap);
			}
		}
	}
}

/**
 * @template T of object
 * @template U
 */
class Baz
{
	/**
	 * @var \WeakMap<T, U>|null
	 */
	private ?\WeakMap $instanceMap = null;

	/**
	 * @var \WeakMap<T, U>|null
	 */
	private static ?\WeakMap $staticMap = null;

	public function initInstance(): void
	{
		if ($this->instanceMap === null) {
			$this->instanceMap = new \WeakMap();
			assertType('WeakMap<T of object (class Bug11844Nsrt\Baz, argument), U (class Bug11844Nsrt\Baz, argument)>', $this->instanceMap);
		}
	}

	public static function initStatic(): void
	{
		if (self::$staticMap === null) {
			self::$staticMap = new \WeakMap();
			assertType('WeakMap<T of object (class Bug11844Nsrt\Baz, argument), U (class Bug11844Nsrt\Baz, argument)>', self::$staticMap);
		}
	}
}
