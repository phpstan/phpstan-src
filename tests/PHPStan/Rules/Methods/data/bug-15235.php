<?php // lint >= 8.1

namespace Bug15235;

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

class B  {
    /**
     * @param A<covariant static> $a
     */
    public function method(A $a): void {}

	/**
	 * @param A<covariant static> $a
	 */
	public static function staticMethod(A $a): void {}
}

class D {
	public function inconsistent(B $b): void {
		$b->method(new A($b)); // (!) No error.
	}
}

$b = new B();

$b->method(new A($b)); // (!) Parameter #1 $a of method B::method() expects A<covariant B>, A<B> given.

$b::staticMethod(new A($b));

$callable = $b->method(...);
$callable(new A($b));
