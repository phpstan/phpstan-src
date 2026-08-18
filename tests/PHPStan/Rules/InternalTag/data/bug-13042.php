<?php

namespace Bug13042 {

	trait SomeTrait
	{

		/** @internal don't use it directly */
		private ?string $text = null;

		/** @internal */
		private static ?string $staticText = null;

		/** @internal */
		private function internalMethod(): void
		{
		}

		/** @internal */
		private static function internalStaticMethod(): void
		{
		}

		public function setText(string $text): void
		{
			$this->text = $text;
			self::$staticText = $text;
			$this->internalMethod();
			self::internalStaticMethod();
		}

	}

	class HelloWorld
	{

		public function sayHello(): object
		{
			return new class {

				use SomeTrait;

				/** @internal */
				public $ownProperty;

				/** @internal */
				public static $ownStaticProperty;

				/** @internal */
				public const OWN_CONSTANT = 'x';

				/** @internal */
				public function ownMethod(): void
				{
				}

				/** @internal */
				public static function ownStaticMethod(): void
				{
				}

				public function doFoo(): void
				{
					$this->ownProperty;
					self::$ownStaticProperty;
					self::OWN_CONSTANT;
					$this->ownMethod();
					self::ownStaticMethod();
				}

			};
		}

		public function fromTheOutside(): void
		{
			$foo = new class {

				/** @internal */
				public $internal;

				/** @internal */
				public static $internalStatic;

				/** @internal */
				const INTERNAL = 'x';

				/** @internal */
				public function doInternal(): void
				{
				}

				/** @internal */
				public static function doInternalStatic(): void
				{
				}

			};

			$foo->internal;
			$foo::$internalStatic;
			$foo::INTERNAL;
			$foo->doInternal();
			$foo::doInternalStatic();
		}

	}

	class Foo
	{

		/** @internal */
		public $internal;

		/** @internal */
		public static $internalStatic;

		/** @internal */
		const INTERNAL = 'x';

		/** @internal */
		public function doInternal(): void
		{
		}

		/** @internal */
		public static function doInternalStatic(): void
		{
		}

	}

}

namespace Bug13042Other {

	function (): object {
		return new class {

			public function doFoo(\Bug13042\Foo $foo): void
			{
				$foo->internal;
				$foo::$internalStatic;
				$foo::INTERNAL;
				$foo->doInternal();
				$foo::doInternalStatic();
			}

		};
	};

}

namespace Bug13042Inheritance {

	function (): object {
		return new class extends \Bug13042\Foo {

			public function doFoo(): void
			{
				$this->internal;
				self::$internalStatic;
				self::INTERNAL;
				$this->doInternal();
				self::doInternalStatic();
			}

		};
	};

}
