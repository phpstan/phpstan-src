<?php

namespace Bug4412;

/**
 * @phpstan-template T of \Exception
 */
class B
{
	/** @var self<\Exception> $a */
	public $a;

	/**
	 * @phpstan-return T
	 */
	public function get() {
		return $a->get();
	}

}
