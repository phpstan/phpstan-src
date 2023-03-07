<?php

namespace Bug5091\Monolog {
	/**
	 * @phpstan-type Record array{message: string}
	 */
	class Logger {
	}
}

namespace Bug5091\Monolog\Handler {
	/**
	 * @phpstan-import-type Record from \Bug5091\Monolog\Logger
	 */
	class Handler {
		use \Bug5091\Monolog\Processor\TraitA;
	}
}

namespace Bug5091\Monolog\Processor {
	/**
	 * @phpstan-import-type Record from \Bug5091\Monolog\Logger
	 */
	trait TraitA {
		/**
		 * @var Record
		 */
		public $foo;

		/**
		 * @return Record
		 */
		public function foo() {
			return ['message' => ''];
		}
	}
}

namespace Bug5091 {

	/**
	 * @phpstan-type MyType array{foobar: string}
	 */
	trait MyTrait
	{
		/**
		 * @return array<MyType>
		 */
		public function MyMethod(): array
		{
			return [['foobar' => 'foo']];
		}
	}

	class MyClass
	{
		use MyTrait;
	}

	/**
	 * @phpstan-type TypeArrayAjaxResponse array{
	 *     message  : string,
	 *     status   : int,
	 *     success  : bool,
	 *     value    : null|float|int|string,
	 * }
	 */
	trait MyTrait2
	{
		/** @return TypeArrayAjaxResponse */
		protected function getAjaxResponse(): array
		{
			return [
				"message" => "test",
				"status" => 200,
				"success" => true,
				"value" => 5,
			];
		}
	}

	class MyController
	{
		use MyTrait2;
	}


	/**
	 * @phpstan-type X string
	 */
	class Types {}

	/**
	 * @phpstan-import-type X from Types
	 */
	trait t {
		/** @return X */
		public function getX() {
			return "123";
		}
	}

	class aClass
	{
		use t;
	}

	/**
	 * @phpstan-import-type X from Types
	 */
	class Z {
		/** @return X */
		public function getX() { // works as expected
			return "123";
		}
	}

	/**
	 * @phpstan-type SomePhpstanType array{
	 *  property: mixed
	 * }
	 */
	trait TraitWithType
	{
		/**
		 * @phpstan-return SomePhpstanType
		 */
		protected function get(): array
		{
			return [
				'property' => 'something',
			];
		}
	}

	/**
	 * @phpstan-import-type SomePhpstanType from TraitWithType
	 */
	class ClassWithTraitWithType
	{
		use TraitWithType;

		/**
		 * @phpstan-return SomePhpstanType
		 */
		public function SomeMethod(): array
		{
			return $this->get();
		}
	}

	/**
	 * @phpstan-type FooJson array{bar: string}
	 */
	trait Foo {
		/**
		 * @phpstan-return FooJson
		 */
		public function sayHello(\DateTime $date): array
		{
			return [
				'bar'=> 'baz'
			];
		}
	}

	/**
	 * @phpstan-import-type FooJson from Foo
	 */
	class HelloWorld
	{
		use Foo;
	}

}
