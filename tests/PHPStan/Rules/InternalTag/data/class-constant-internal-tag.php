<?php

namespace ClassConstantInternalTagOne {

	class Foo
	{
		/** @internal */
		public const INTERNAL = 1;

		public const NOT_INTERNAL = 2;
	}

	/**
	 * @internal
	 */
	class FooInternal
	{

		public const FOO = 1;

	}

	function (): void {
		Foo::INTERNAL;
		Foo::NOT_INTERNAL;
	};

	function (): void {
		FooInternal::FOO;
	};

}

namespace ClassConstantInternalTagOne\Test {

	function (): void {
		\ClassConstantInternalTagOne\Foo::INTERNAL;
		\ClassConstantInternalTagOne\Foo::NOT_INTERNAL;
	};

	function (): void {
		\ClassConstantInternalTagOne\FooInternal::FOO;
	};
}

namespace ClassConstantInternalTagTwo {

	function (): void {
		\ClassConstantInternalTagOne\Foo::INTERNAL;
		\ClassConstantInternalTagOne\Foo::NOT_INTERNAL;
	};

	function (): void {
		\ClassConstantInternalTagOne\FooInternal::FOO;
	};

}

namespace {

	function (): void {
		\ClassConstantInternalTagOne\Foo::INTERNAL;
		\ClassConstantInternalTagOne\Foo:: NOT_INTERNAL;
	};

	function (): void {
		\ClassConstantInternalTagOne\FooInternal::FOO;
	};

	class FooWithInternalClassConstantWithoutNamespace
	{
		/** @internal */
		public const INTERNAL = 1;

		public const NOT_INTERNAL = 2;
	}

	/**
	 * @internal
	 */
	class FooInternalWithClassConstantWithoutNamespace
	{

		public const FOO = 1;

	}

	function (): void {
		FooWithInternalClassConstantWithoutNamespace::INTERNAL;
		FooWithInternalClassConstantWithoutNamespace::NOT_INTERNAL;
	};

	function (): void {
		FooInternalWithClassConstantWithoutNamespace::FOO;
	};

}

namespace SomeNamespace {

	function (): void {
		\FooWithInternalClassConstantWithoutNamespace::INTERNAL;
		\FooWithInternalClassConstantWithoutNamespace::NOT_INTERNAL;
	};

	function (): void {
		\FooInternalWithClassConstantWithoutNamespace::FOO;
	};

}

namespace ClassConstantInternalTagThree {

	function (): void {
		\ClassConstantInternalTagOne\FooInternal::class;
	};

}
