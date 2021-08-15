<?php

namespace AppendedArrayKey;

class Foo
{

	/** @var array<int, mixed> */
	private $intArray;

	/** @var array<string, mixed> */
	private $stringArray;

	/** @var array<int|string, mixed> */
	private $bothArray;

	/**
	 * @param int|string $intOrString
	 */
	public function doFoo(
		int $int,
		string $string,
		$intOrString,
		?string $stringOrNull
	)
	{
		$this->intArray[new \DateTimeImmutable()] = 1;
		$this->intArray[$intOrString] = 1;
		$this->intArray[$int] = 1;
		$this->intArray[$string] = 1;
		$this->stringArray[$int] = 1;
		$this->stringArray[$string] = 1;
		$this->stringArray[$intOrString] = 1;
		$this->bothArray[$int] = 1;
		$this->bothArray[$intOrString] = 1;
		$this->bothArray[$string] = 1;
		$this->stringArray[$stringOrNull] = 1; // will be cast to string
		$this->stringArray['0'] = 1;
	}

	public function checkRewrittenArray()
	{
		$this->stringArray = [];
		$integerKey = (int)'1';

		$this->stringArray[$integerKey] = false;
	}

}

class Bar
{

	/** @var array<class-string<\stdClass>, true> */
	private $classStringKey;

	/**
	 * @param class-string<\stdClass> $s
	 */
	public function doFoo(string $s)
	{
		$this->classStringKey[$s] = true;
	}

	public function doBar()
	{
		$this->classStringKey[\stdClass::class] = true;
	}

}

class MorePreciseKey
{

	/** @var array<1|2|3, string> */
	private $test;

	public function doFoo(int $i): void
	{
		$this->test[$i] = 'foo';
	}

	public function doBar(): void
	{
		$this->test[4] = 'foo';
	}

}
