<?php

namespace Bug10208;

abstract class SomeParent
{
	/**
	 * @return $this
	 */
	abstract public function clear(): self;
}

class SomeChild extends SomeParent
{
	public function clear(): self
	{
		return $this;
	}
}
