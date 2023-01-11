<?php

namespace ReflectionClassIssue8679;

class FooClass
{
    public function __construct(
		private int $test1 = 0, 
		private int $test2 = 0,
		private int $test3 = 0
	)
	{}
	
	public function showTest(): string
	{
		return $this->test1.$this->test2.$this->test3."\n";
	}
}

class FooClassSimpleFactory
{
	/**
	 * @param array<int, mixed> $options Options for MyClass
	 */
	public static function getClassA(array $options = []): FooClass
	{
		return (new \ReflectionClass('FooClass'))->newInstanceArgs($options);
	}

	/**
	 * @param array<string, mixed> $options Options for MyClass
	 */
	public static function getClassB(array $options = []): FooClass
	{
		return (new \ReflectionClass('FooClass'))->newInstanceArgs($options);
	}

	/**
	 * @param array<int|string, mixed> $options Options for MyClass
	 */
	public static function getClassC(array $options = []): FooClass
	{
		return (new \ReflectionClass('FooClass'))->newInstanceArgs($options);
	}
}