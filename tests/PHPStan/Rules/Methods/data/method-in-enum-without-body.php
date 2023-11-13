<?php // lint >= 8.1

namespace MethodInEnumWithoutBody;

enum Foo
{

	public function doFoo(): void;

	abstract public function doBar(): void;

}
