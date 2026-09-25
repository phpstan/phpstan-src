<?php // lint >= 8.1

declare(strict_types = 1);

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

/** @template-covariant T of object */
class Covariant
{
	/** @param T $object */
	public function __construct(public object $object) {}
}

class B
{
	/** @var A<covariant static>|null */
	public ?A $callSiteVariance = null;

	/** @var Covariant<static>|null */
	public ?Covariant $declaredVariance = null;
}

function fromParameter(B $b): void
{
	$b->callSiteVariance = new A($b);
	$b->declaredVariance = new Covariant($b);
}

function fromNew(): void
{
	$b = new B();
	$b->callSiteVariance = new A($b);
	$b->declaredVariance = new Covariant($b);
}
