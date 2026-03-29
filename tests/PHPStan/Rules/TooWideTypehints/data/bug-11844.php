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

class DirectAssignCase
{
	/**
	 * @var \WeakMap<object, string>|null
	 */
	private ?\WeakMap $map = null;

	public function initAlways(): void
	{
		$this->map = new \WeakMap();
	}
}
