<?php declare(strict_types = 1);

namespace Bug9384;

class Deprecation
{
	private const TYPE_NONE               = 0;
	private const TYPE_TRACK_DEPRECATIONS = 1;
	private const TYPE_TRIGGER_ERROR      = 2;

	/** @var int-mask-of<self::TYPE_*> */
	private static $type = 0;

	public static function enableTrackingDeprecations(): void
	{
		self::$type |= self::TYPE_TRACK_DEPRECATIONS;
	}

	public static function invalidValue(): void
	{
		self::$type = self::$type | 10; // invalid value
	}

	public static function enableWithTriggerError(): void
	{
		self::$type |= self::TYPE_TRIGGER_ERROR;
	}

	public static function disable(): void
	{
		self::$type = self::TYPE_NONE;
	}
}

class A
{
	public const FLAG_A = 0b0001;
	public const FLAG_B = 0b0010;

	/** @var int-mask-of<self::FLAG_*> */
	protected int $flags = 0;

	public function enableA(): void
	{
		$this->flags |= self::FLAG_A;
	}

	public function disableA(): void
	{
		$this->flags &= ~self::FLAG_A;
	}

	public function enableB(): void
	{
		$this->flags |= self::FLAG_B;
	}

	public function disableB(): void
	{
		$this->flags &= ~self::FLAG_B;
	}
}

class Foo
{
	const BITMASK_0 = 0;
	const BITMASK_1 = 1;
	const BITMASK_2 = 2;
	const BITMASK_3 = 3;
	const BITMASK_4 = 4;

	/** @var int-mask-of<Foo::BITMASK_*> */
	private int $a = 0;

	/**
	 * @param int-mask-of<Foo::BITMASK_*> $b
	 */
	public function bar(int $b): void
	{
		$this->a = Foo::BITMASK_1 & $b;
		$this->a = $this->a & $b;
		$this->a &= $b;
	}
}
