<?php

namespace Bug3703;

class Foo {
	/**
	 * @var array<string, array<string, int[]>>
	 */
	public $bar;

	public function doFoo()
	{
		$foo = new self();
		// Should not be allowed (missing string key)
		$foo->bar['foo']['bar'][] = 'ok';

		// Should not be allowed (value should be array)
		$foo->bar['foo']['bar'] = 1;

		// Should not be allowed
		$foo->bar['ok'] = 'ok';
	}

}
