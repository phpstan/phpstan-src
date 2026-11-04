<?php // lint >= 8.0

namespace Bug15235Properties;

/**
 * @template T of object
 */
class A
{
	/**
	 * @param T $object
	 */
	public function __construct(public object $object) {}
}

class B
{
	/** @var A<covariant static>|null */
	public $a = null;
}

function (): void {
	$b = new B();
	$b->a = new A($b);
};
