<?php

namespace TestInitializedProperty;

class TestAdditionalConstructor
{
	public string $one;

	protected int $two;

	protected int $three;

	protected int $four;

	public function setTwo(int $value): void
	{
		$this->two = $value;
		$this->setFour();
	}

	public function setThree(int $value): void
	{
		$this->three = $value;
	}

	public function setFour(): void
	{
		$this->four = 1;
	}
}
