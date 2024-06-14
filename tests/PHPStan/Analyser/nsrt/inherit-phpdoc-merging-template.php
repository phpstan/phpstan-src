<?php declare(strict_types = 1);

namespace InheritDocMergingTemplate;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @template T
	 * @template U
	 * @param T $a
	 * @param U $b
	 * @return T|array<U>
	 */
	public function doFoo($a, $b)
	{

	}

}

class Bar extends Foo
{

	public function doFoo($a, $b)
	{
		assertType('T (method InheritDocMergingTemplate\Foo::doFoo(), argument)', $a);
		assertType('U (method InheritDocMergingTemplate\Foo::doFoo(), argument)', $b);
	}

	public function doBar()
	{
		assertType('1|array<\'hahaha\'>', $this->doFoo(1, 'hahaha'));
	}

}

class Dolor extends Foo
{

	/**
	 * @param T $a
	 * @param U $b
	 * @return T|array<U>
	 */
	public function doFoo($a, $b)
	{
		assertType('InheritDocMergingTemplate\T', $a);
		assertType('InheritDocMergingTemplate\U', $b);
	}

	public function doBar()
	{
		assertType('array<InheritDocMergingTemplate\U>|InheritDocMergingTemplate\T', $this->doFoo(1, 'hahaha'));
	}

}

class Sit extends Foo
{

	/**
	 * @template T
	 * @param T $a
	 * @param U $b
	 * @return T|array<U>
	 */
	public function doFoo($a, $b)
	{
		assertType('T (method InheritDocMergingTemplate\Sit::doFoo(), argument)', $a);
		assertType('InheritDocMergingTemplate\U', $b);
	}

	public function doBar()
	{
		assertType('1|array<InheritDocMergingTemplate\U>', $this->doFoo(1, 'hahaha'));
	}

}

class Amet extends Foo
{

	/** SomeComment */
	public function doFoo($a, $b)
	{
		assertType('T (method InheritDocMergingTemplate\Foo::doFoo(), argument)', $a);
		assertType('U (method InheritDocMergingTemplate\Foo::doFoo(), argument)', $b);
	}

	public function doBar()
	{
		assertType('1|array<\'hahaha\'>', $this->doFoo(1, 'hahaha'));
	}

}

/**
 * @template T of object
 */
class Baz
{

	/**
	 * @param T $a
	 */
	public function doFoo($a)
	{

	}

}

class Lorem extends Baz
{

	public function doFoo($a)
	{
		assertType('object', $a);
	}

}

/**
 * @extends Baz<\stdClass>
 */
class Ipsum extends Baz
{

	public function doFoo($a)
	{
		assertType('stdClass', $a);
	}

}
