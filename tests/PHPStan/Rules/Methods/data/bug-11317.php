<?php // lint >= 8.0

namespace Bug11317;

class A {
	/**
	 * @param array{string, int, bool} $array
	 */
	public function do(array $array): B
	{
		return new B;
	}
}

class B {}

class C {
	public function __construct(private A $a) {}

	/**
	 * @return Callable(array{string, int, bool}): B
	 */
	public function getCallable(): Callable
	{
		/**
		 * @param array{string, int, bool}
		 */
		return function (array $array): B 
		{
			return $this->a->do($array);
		};
	}
}
