<?php declare(strict_types = 1);

namespace Bug11663;

class BuilderA
{
	/**
	 * @param string $test
	 * @return $this
	 */
	public function where(string $test)
	{
		return $this;
	}
}

class BuilderB
{
	/**
	 * @param string $test
	 * @return $this
	 */
	public function where(string $test)
	{
		return $this;
	}
}

interface A
{
	public function doFoo(): static;
}

interface B
{
}

class Test
{
	/**
	 * @template B of BuilderA|BuilderB
	 * @param B $template
	 * @return B
	 */
	public function test($template)
	{
		return $template->where('test');
	}

	/**
	 * @param __benevolent<BuilderA|BuilderB|false> $template
	 * @return __benevolent<BuilderA|BuilderB>
	 */
	public function test2($template)
	{
		return $template->where('test');
	}


	/**
	 * @template T of A|B
	 * @param T $ab
	 * @return T
	 */
	function foo(A|B $ab): A|B
	{
		return $ab->doFoo();
	}

	/**
	 * @template T of __benevolent<A|B>
	 * @param T $ab
	 * @return T
	 */
	function foo2(A|B $ab): A|B
	{
		return $ab->doFoo();
	}
}
