<?php // lint >= 8.4

namespace ThrowsVoidPropertyHook;

class MyException extends \Exception
{

}

class Foo
{

	public int $i {
		/**
		 * @throws void
		 */
		get {
			throw new MyException();
		}
	}

}
