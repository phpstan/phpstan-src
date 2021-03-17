<?php

namespace Bug3118;

/**
 * @template T of Enum
 */
class EnumSet
{
	private $type;

	/**
	 * @param class-string<T> $type
	 */
	public function __construct(string $type)
	{
		$this->type = $type;
	}
}

abstract class Enum
{
	/**
	 * @return EnumSet<static>
	 */
	public static function all(): EnumSet
	{
		return new EnumSet(static::class);
	}
}

/**
 * @extends EnumSet<CustomEnum>
 */
final class CustomEnumSet extends EnumSet
{

	public function __construct()
	{
		parent::__construct(CustomEnum::class);
	}
}

final class CustomEnum extends Enum
{
	public static function all(): EnumSet
	{
		return new CustomEnumSet();
	}
}

class CustomEnum2 extends Enum
{
	public static function all(): EnumSet
	{
		return new CustomEnumSet();
	}
}
