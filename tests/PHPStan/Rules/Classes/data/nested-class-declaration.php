<?php // lint >= 8.1

namespace NestedClassDeclaration;

class Foo
{

	public function doFoo(): void
	{
		class NestedClass
		{
		}

		interface NestedInterface
		{
		}

		trait NestedTrait
		{
		}

		enum NestedEnum
		{
		}

		$f = function (): void {
			class InClosure
			{
			}
		};

		$anonymous = new class {

			public function doBar(): int
			{
				return 1;
			}

		};
	}

}

function doBaz(): void
{
	class InFunction
	{
	}

	$f = function (): void {
		class InClosureInFunction
		{
		}
	};
}

trait DeclaresInMethod
{

	public function doTrait(): void
	{
		class InTraitMethod
		{
		}
	}

}

class UsesTheTrait
{

	use DeclaresInMethod;

}
