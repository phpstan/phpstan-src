<?php // lint >= 8.0

namespace Bug12250;

class HelloWorld
{
	/**
	 * @var \WeakMap<\stdClass, \stdClass>
	 */
	protected \WeakMap $bug, $ok;

	public function bug(): void
	{
		$this->bug ??= new \WeakMap();
		$this->ok = new \WeakMap();
	}
}
