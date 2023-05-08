<?php

namespace CallVariadicMethods;

class Foo
{

	public function bar()
	{
		$this->baz();
		$this->lorem();
		$this->baz(1, 2, 3);
		$this->lorem(1, 2, 3);
	}

	public function baz($foo, ...$bar)
	{

	}

	public function lorem($foo, $bar)
	{
		$foo = 'bar';
		if ($foo) {
			func_get_args();
		}
	}

	public function doFoo()
	{
		$this->doVariadicString(1, 'foo', 'bar');
		$this->doVariadicString(1, 2, 3);
		$this->doVariadicString(1);
		$this->doVariadicString('foo');
		$this->doVariadicWithFuncGetArgs('foo', 'bar');

		$strings = ['foo', 'bar', 'baz'];
		$this->doVariadicString(1, ...$strings);
		$this->doVariadicString(1, 'foo', ...$strings);

		$integers = [1, 2, 3];
		$this->doVariadicString(1, 'foo', 1, ...$integers);
		$this->doIntegerParameters(...$strings);
		$this->doIntegerParameters(...$integers);
	}

	public function doVariadicString(int $int, string ...$strings)
	{

	}

	public function doVariadicWithFuncGetArgs()
	{
		func_get_args();
	}

	public function doIntegerParameters(int $foo, int $bar)
	{

	}

}

class Bar
{

	/**
	 * @param string[] ...$strings
	 */
	function variadicStrings(string ...$strings)
	{

	}

	/**
	 * @param string[] ...$strings
	 */
	function anotherVariadicStrings(...$strings)
	{

	}

	public function doFoo()
	{
		$this->variadicStrings(1, 2);
		$this->variadicStrings('foo', 'bar');

		$this->anotherVariadicStrings(1, 2);
		$this->anotherVariadicStrings('foo', 'bar');
	}

}

class ImpliedVariadicGh9280
{
	public function anyArgsWithNumAndGet(int $index): int
	{
		$argsCount = func_num_args();
		$lastArg = func_get_arg($argsCount - 1);

		return is_int($lastArg) ? $lastArg : $index;
	}

	public function anyArgsWithGetAll(int $index): int
	{
		$args = func_get_args();
		$lastArg = end($args);

		return is_int($lastArg) ? $lastArg : $index;
	}

	public function onlyOneArgA(int $index): int
	{
		return $index;
	}

	public function onlyOneArgB(int $index): int
	{
		$argsCount = func_num_args();
		if ($argsCount > 1) {
			throw new \Error('Too many args');
		}

		return $index;
	}

	public function doFoo()
	{
		$this->anyArgsWithNumAndGet();
		$this->anyArgsWithNumAndGet(1);
		$this->anyArgsWithNumAndGet(1, 2);

		$this->anyArgsWithGetAll();
		$this->anyArgsWithGetAll(1);
		$this->anyArgsWithGetAll(1, 2);

		$this->onlyOneArgA();
		$this->onlyOneArgA(1);
		$this->onlyOneArgA(1, 2);

		$this->onlyOneArgB();
		$this->onlyOneArgB(1);
		$this->onlyOneArgB(1, 2);
	}
}
