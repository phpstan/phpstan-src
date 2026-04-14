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
