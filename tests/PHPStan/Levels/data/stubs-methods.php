<?php

namespace StubsIntegrationTest;

class Foo
{

	public function doFoo($i)
	{
		return '';
	}

}

function (Foo $foo) {
	$string = $foo->doFoo('test');
	$foo->doFoo($string);
};
