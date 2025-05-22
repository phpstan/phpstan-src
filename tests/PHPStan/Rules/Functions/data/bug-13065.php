<?php

namespace Bug13065;

class Foo
{

	public function test()
	{
		getenv(null);
		getenv();
		getenv('foo');
	}

}
