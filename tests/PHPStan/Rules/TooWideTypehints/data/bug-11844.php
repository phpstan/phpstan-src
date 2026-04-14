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
