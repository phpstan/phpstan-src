<?php declare(strict_types = 1);

namespace Bug10208;

abstract class AbstractScope
{
	/**
	 * @return $this
	 */
	abstract public function clear(): self;
}

class Condition extends AbstractScope
{
	/** @var string|null */
	public $key;

	public function clear(): self
	{
		$this->key = null;

		return $this;
	}
}
