<?php // lint >= 8.4

namespace AssignHookedProperties;

class Foo
{

	public int $i {
		/** @param array<string>|int $val */
		set (array|int $val) {
			$this->i = $val; // only int allowed
		}
	}

	public int $j {
		/** @param array<string>|int $val */
		set (array|int $val) {
			$this->i = $val; // this is okay - hook called
			$this->j = $val; // only int allowed
		}
	}

	public function doFoo(): void
	{
		$this->i = ['foo']; // okay
		$this->i = 1; // okay
		$this->i = [1]; // not okay
	}

}

/**
 * @template T
 */
class FooGenerics
{

	/** @var T */
	public $a {
		set {
			$this->a = $value;
		}
	}

	/**
	 * @param FooGenerics<int> $f
	 * @return void
	 */
	public static function doFoo(self $f): void
	{
		$f->a = 1;
		$f->a = 'foo';
	}

	/**
	 * @param T $t
	 */
	public function doBar($t): void
	{
		$this->a = $t;
		$this->a = 1;
	}

}

/**
 * @template T
 */
class FooGenericsParam
{

	/** @var array<T> */
	public array $a {
		/** @param array<T>|int $value */
		set (array|int $value) {
			$this->a = $value; // not ok

			if (is_array($value)) {
				$this->a = $value; // ok
			}
		}
	}

	/**
	 * @param FooGenericsParam<int> $f
	 * @return void
	 */
	public static function doFoo(self $f): void
	{
		$f->a = [1]; // ok
		$f->a = ['foo']; // not ok
	}

	/**
	 * @param T $t
	 */
	public function doBar($t): void
	{
		$this->a = [$t]; // ok
		$this->a = 1; // ok
	}

}
