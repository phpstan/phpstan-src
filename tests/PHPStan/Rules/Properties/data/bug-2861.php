<?php declare(strict_types = 1);

namespace Bug2861;

trait EnumTrait {
	/** @var mixed */
	protected $value;

	/** @param mixed $value */
	final public function __construct($value) {
		$this->value = $value;
	}

	/** @return static|null */
	public static function getDefault() {
		if (property_exists(static::class, 'default') && null !== static::$default) {
			$obj = static::$default;
			return new static($obj);
		}
		return null;
	}
}

class Foo {
	use EnumTrait;
	public const BLA = 'bla';
}

class Bar {
	use EnumTrait;
	public static $default = 'bla';
	public const BLA = 'bla';
}

class Baz {
	use EnumTrait;

	/** @return static|null */
	public static function getDefault2() {
		if (property_exists(self::class, 'default') && null !== self::$default) {
			return new static(self::$default);
		}
		return null;
	}
}

class ExpressionBased {
	/**
	 * @param class-string $className
	 */
	public static function test(string $className): void {
		if (property_exists($className, 'default')) {
			echo $className::$default;
		}
	}
}
