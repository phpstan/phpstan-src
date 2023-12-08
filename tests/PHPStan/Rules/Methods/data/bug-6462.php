<?php declare(strict_types = 1);

namespace Bug6462;

interface ThisInterface {
	/**
	 * @return $this
	 */
	public function t() : self;
}

class ThisImplementer implements ThisInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function t() : self {
		return $this;
	}
}


interface NonThisInterface {
	public function t() : self;
}

class NonThisImplementer implements NonThisInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function t() : self {
		return $this;
	}
}
