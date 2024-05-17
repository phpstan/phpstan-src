<?php declare(strict_types = 1); // lint >= 7.4

namespace ImpureAssignRef;

class HelloWorld
{
	public int $value = 0;
	/** @var array<int, int> */
	public array $arr = [];
	/** @var array<int, \stdClass> */
	public array $objectArr = [];
	public static int $staticValue = 0;
	/** @var array<int, int> */
	public static array $staticArr = [];

	private function bar1(): void
	{
		$value = &$this->value;
		$value = 1;
	}

	private function bar2(): void
	{
		$value = &$this->arr[0];
		$value = 1;
	}

	private function bar3(): void
	{
		$value = &self::$staticValue;
		$value = 1;
	}

	private function bar4(): void
	{
		$value = &self::$staticArr[0];
		$value = 1;
	}

	private function bar5(self $other): void
	{
		$value = &$other->value;
		$value = 1;
	}

	private function bar6(): void
	{
		$value = &$this->objectArr[0]->foo;
		$value = 1;
	}

	public function foo(): void
	{
		$this->bar1();
		$this->bar2();
		$this->bar3();
		$this->bar4();
		$this->bar5(new self());
		$this->bar6();
	}
}
