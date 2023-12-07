<?php

namespace MethodTooWideReturnAlwaysCheckFinal;

class Foo
{

	private function test(): ?int
	{
		return 1;
	}

	protected function test2(): ?int
	{
		return 1;
	}

	public function test3(): ?int
	{
		return 1;
	}

}

final class FinalFoo
{

	private function test(): ?int
	{
		return 1;
	}

	protected function test2(): ?int
	{
		return 1;
	}

	public function test3(): ?int
	{
		return 1;
	}

}

class FooFinalMethods
{

	private function test(): ?int
	{
		return 1;
	}

	final protected function test2(): ?int
	{
		return 1;
	}

	final public function test3(): ?int
	{
		return 1;
	}

}
