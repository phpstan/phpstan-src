<?php // lint >= 8.4

namespace UnthrownExceptionPropertyHooksVersionGuard;

class MyCustomException extends \Exception
{

}

class Foo
{

	public int $i {
		/** @throws MyCustomException */
		get {
			if (rand(0, 1)) {
				throw new MyCustomException();
			}

			return 1;
		}
	}

	public int $j {
		/** @throws MyCustomException */
		set {
			if (rand(0, 1)) {
				throw new MyCustomException();
			}

			$this->j = $value;
		}
	}

	public function doFoo(int $v): void
	{
		if (PHP_VERSION_ID >= 80400) {
			try {
				$a = $this->i;
			} catch (MyCustomException $e) {

			}

			try {
				$this->j = $v;
			} catch (MyCustomException $e) {

			}
		}

		if (PHP_VERSION_ID < 80400) {
			try {
				$a = $this->i;
			} catch (MyCustomException $e) { // no property hooks below PHP 8.4

			}

			try {
				$this->j = $v;
			} catch (MyCustomException $e) { // no property hooks below PHP 8.4

			}
		}
	}

}
