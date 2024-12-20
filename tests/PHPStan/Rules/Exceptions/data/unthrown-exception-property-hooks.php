<?php // lint >= 8.4

namespace UnthrownExceptionPropertyHooks;

class MyCustomException extends \Exception
{

}

class SomeException extends \Exception
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

			try {
				return $this->i;
			} catch (MyCustomException) { // unthrown - @throws does not apply to direct access in the hook

			}
		}
	}

	public function doFoo(): void
	{
		try {
			$a = $this->i;
		} catch (MyCustomException) {

		} catch (SomeException) { // unthrown

		}
	}

	public int $j {
		/** @throws MyCustomException */
		set {
			if (rand(0, 1)) {
				throw new MyCustomException();
			}

			try {
				$this->j = $value;
			} catch (MyCustomException) { // unthrown - @throws does not apply to direct access in the hook

			}
		}
	}

	public function doBar(int $v): void
	{
		try {
			$this->j = $v;
		} catch (MyCustomException) {

		} catch (SomeException) { // unthrown

		}
	}

	public int $k {
		get {
			return 1;
		}
	}

	public function doBaz(): void
	{
		try {
			echo $this->k;
		} catch (MyCustomException) { // can be thrown - implicit @throws

		}

		try {
			$this->k = 1;
		}  catch (MyCustomException) { // can be thrown - subclass might introduce a set hook

		}
	}

	private int $l {
		get {
			return $this->l;
		}
	}

	public function doLorem(): void
	{
		try {
			echo $this->l;
		} catch (MyCustomException) { // can be thrown - implicit @throws

		}

		try {
			$this->l = 1;
		}  catch (MyCustomException) { // unthrown - set hook does not exist

		}
	}

	final public int $m {
		get {
			return $this->m;
		}
	}

	public function doIpsum(): void
	{
		try {
			echo $this->m;
		} catch (MyCustomException) { // can be thrown - implicit @throws

		}

		try {
			$this->m = 1;
		}  catch (MyCustomException) { // unthrown - set hook does not exist

		}
	}

}

final class FinalFoo
{

	public int $m {
		get {
			return $this->m;
		}
	}

	public function doIpsum(): void
	{
		try {
			echo $this->m;
		} catch (MyCustomException) { // can be thrown - implicit @throws

		}

		try {
			$this->m = 1;
		}  catch (MyCustomException) { // unthrown - set hook does not exist

		}
	}

}

class ThrowsVoid
{

	public int $m {
		/** @throws void */
		get {
			return $this->m;
		}
	}

	public function doIpsum(): void
	{
		try {
			echo $this->m;
		} catch (MyCustomException) { // unthrown

		}
	}

}

class Dynamic
{

	public function doFoo(object $o, string $s): void
	{
		try {
			echo $o->$s;
		} catch (MyCustomException) { // implicit throw point

		}

		try {
			$o->$s = 1;
		} catch (MyCustomException) { // implicit throw point

		}
	}

}
