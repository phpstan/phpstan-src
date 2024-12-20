<?php // lint >= 8.4

namespace UnthrownExceptionPropertyHooksImplicitThrowsDisabled;

class MyCustomException extends \Exception
{

}

class SomeException extends \Exception
{

}

class Foo
{
	public int $i;

	public function doFoo(): void
	{
		try {
			echo $this->i;
		} catch (MyCustomException) { // unthrown - implicit @throws disabled

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
		} catch (MyCustomException) { // unthrown - implicit @throws disabled

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
		} catch (MyCustomException) { // unthrown - implicit @throws disabled

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
		} catch (MyCustomException) { // unthrown - implicit @throws disabled

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
		} catch (MyCustomException) { // unthrown - implicit @throws disabled

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
