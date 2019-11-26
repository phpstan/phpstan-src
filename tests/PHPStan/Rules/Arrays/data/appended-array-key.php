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
		function () {
			$this->intArray[new \DateTimeImmutable()] = 1;
		};
		function () use ($intOrString) {
			$this->intArray[$intOrString] = 1;
		};
		function () use ($int) {
			$this->intArray[$int] = 1;
		};
		function () use ($string) {
			$this->intArray[$string] = 1;
		};
		function () use ($int) {
			$this->stringArray[$int] = 1;
		};
		function () use ($string) {
			$this->stringArray[$string] = 1;
		};
		function () use ($intOrString) {
			$this->stringArray[$intOrString] = 1;
		};
		function () use ($int) {
			$this->bothArray[$int] = 1;
		};
		function () use ($intOrString) {
			$this->bothArray[$intOrString] = 1;
		};
		function () use ($string) {
			$this->bothArray[$string] = 1;
		};
		function () use ($stringOrNull) {
			$this->stringArray[$stringOrNull] = 1; // will be cast to string
		};
		function () {
			$this->stringArray['0'] = 1;
		};
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
