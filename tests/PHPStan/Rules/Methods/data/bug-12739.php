<?php

declare(strict_types = 1);

/**
 * @template TScalar of scalar
 */
abstract class ScalarType {
	/**
	 * @return class-string<ScalarType<TScalar>>
	 */
	abstract public static function getClassIdentify() : string;
}

/**
 * @extends ScalarType<int>
 */
class MyInt extends ScalarType {
	public static function getClassIdentify() : string {
		return MyInt::class;
	}
}

/**
 * @extends ScalarType<string>
 */
class MyString extends ScalarType {
	public static function getClassIdentify() : string {
		return MyString::class;
	}
}

/**
 * @extends ScalarType<lowercase-string>
 */
class MyLowerString extends ScalarType {
	public static function getClassIdentify() : string {
		return MyLowerString::class;
	}
}

/**
 * @extends ScalarType<uppercase-string>
 */
class MyUpperString extends ScalarType {
	public static function getClassIdentify() : string {
		return MyUpperString::class;
	}
}

/**
 * @extends ScalarType<class-string>
 */
class MyClassString extends ScalarType {
	public static function getClassIdentify() : string {
		return MyClassString::class;
	}
}
