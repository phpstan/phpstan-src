<?php

namespace NodeCallbackScopeResolverRule;

class Foo
{

	public function doFoo(): ?string
	{
		return 'foo';
	}

	public function doBar(): ?int
	{
		return 1;
	}

}

function (Foo $foo): void {
	$foo->doFoo($a = 1, $a + 1, 3);

	echo 'foo';
};
