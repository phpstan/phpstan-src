<?php declare(strict_types = 1);

/*
 * instructions: 20.09M
 * cycles: 31.85M
 * memory: 36.29MiB
 */

namespace Bug5390;

/** @mixin B */
class A
{
	/** @var B */
	private $b;

	public function infiniteRecursion() {
		return $this->b->someMethod();
	}
}
/** @mixin A */
class B
{

}
