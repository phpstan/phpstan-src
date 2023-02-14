<?php declare(strict_types = 1);

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
