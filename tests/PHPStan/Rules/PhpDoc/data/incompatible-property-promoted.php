<?php // lint >= 8.0

namespace InvalidPhpDocPromotedProperties;

use InvalidPhpDoc\Foo;
use InvalidPhpDoc\Bar;

class FooWithProperty
{

	public function __construct(
		/** @var aray<self> */
		private $foo,

		/** @var Foo&Bar */
		private $bar,

		/** @var never */
		private $baz,

		/** @var class-string<int> */
		private $classStringInt,

		/** @var class-string<stdClass> */
		private $classStringValid,

		/** @var array{\InvalidPhpDocDefinitions\Foo<\stdClass>} */
		private $fooGeneric,

		/** @var \InvalidPhpDocDefinitions\FooGeneric<int, \InvalidArgumentException> */
		private $validGenericFoo,

		/** @var \InvalidPhpDocDefinitions\FooGeneric<int> */
		private $notEnoughTypesGenericfoo,

		/** @var \InvalidPhpDocDefinitions\FooGeneric<int, \InvalidArgumentException, string> */
		private $tooManyTypesGenericfoo,

		/** @var \InvalidPhpDocDefinitions\FooGeneric<int, \Throwable> */
		private $invalidTypeGenericfoo,

		/** @var \InvalidPhpDocDefinitions\FooGeneric<int, \stdClass> */
		private $anotherInvalidTypeGenericfoo,

		/** @var UnknownClass::BLABLA */
		private $unknownClassConstant,

		/** @var self::BLABLA */
		private $unknownClassConstant2
	) { }

}

class Bar
{

	/**
	 * @param int $foo
	 * @param string $bar
	 */
	public function __construct(private int $foo, private int $bar)
	{

	}

}
