<?php

namespace ClassAliasTest;

class OriginalClass
{
	public function doFoo()
	{
	}
}

$x = new \AliasName();
$x->doFoo();

$y = new AliasName();
$y->doFoo();
