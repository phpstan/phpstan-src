<?php // lint >= 7.4

namespace Bug6757;

use Ds\Set;
use stdClass;

final class A
{
	/** @var Set<stdClass> */
	public Set $a;

	public function __construct()
	{
		$this->a = new Set();
	}
}
