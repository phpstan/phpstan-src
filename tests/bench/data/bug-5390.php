<?php declare(strict_types = 1);

/*
 * instructions: 23.36M
 * cycles: 35.32M
 * memory: 554.0KiB
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
