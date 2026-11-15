<?php // lint >= 8.0

namespace OverridingMethodVoidMixed;

class Foo
{

	public function explicitMixed(): mixed
	{
		return 1;
	}

	public function noReturnType()
	{
		return 1;
	}

	public function explicitMixedToNever(): mixed
	{
		return 1;
	}

}

class Bar extends Foo
{

	public function explicitMixed(): void
	{
	}

	public function noReturnType(): void
	{
	}

	public function explicitMixedToNever(): never
	{
		throw new \Exception();
	}

}
