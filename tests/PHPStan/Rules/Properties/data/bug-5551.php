<?php

namespace Bug5551;

class Foo
{
	/**
	 * @var WeakMap<\stdClass, \stdClass>
	 */
	protected static WeakMap $bug;

	/**
	 * @var WeakMap<\stdClass, \stdClass>
	 */
	protected WeakMap $ok;

	public function bug(): void
	{
		$this->ok = new WeakMap();
		static::$bug = new WeakMap();
	}
}
