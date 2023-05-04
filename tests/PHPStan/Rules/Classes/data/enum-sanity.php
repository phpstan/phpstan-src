<?php // lint >= 8.1

namespace EnumSanity;

enum EnumWithAbstractMethod
{
	abstract function foo();
}

enum EnumWithConstructorAndDestructor
{
	public function __construct()
	{}

	public function __destruct()
	{}
}

enum EnumWithMagicMethods
{
	public function __get()
	{}

	public function __call()
	{}

	public function __callStatic()
	{}

	public function __set()
	{}

	public function __invoke()
	{}
}

enum PureEnumCannotRedeclareMethods
{
	public static function cases()
	{
	}

	public static function tryFrom()
	{
	}

	public static function from()
	{
	}
}

enum BackedEnumCannotRedeclareMethods: int
{
	public static function cases()
	{
	}

	public static function tryFrom()
	{
	}

	public static function from()
	{
	}
}

enum BackedEnumWithFloatType: float
{
}

enum BackedEnumWithBoolType: bool
{
}

enum EnumWithSerialize {
	case Bar;

	public function __serialize() {
	}

	public function __unserialize(array $data) {

	}
}

enum EnumDuplicateValue: int {
	case A = 1;
	case B = 2;
	case C = 2;
	case D = 3;
	case E = 1;
}

enum EnumWithoutDuplicateValuesRemainsUntouched {
	case A = 1;
	case B = 2;
}

enum EnumMayNotSerializable implements \Serializable {

	public function serialize() {
	}
	public function unserialize($data) {
	}
}
