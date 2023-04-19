<?php // lint >= 7.4

namespace TestInitializedProperty;

class TestAdditionalConstructor
{
	public string $one;

	protected int $two;

	protected int $three;

	public function setTwo(int $value): void
	{
		$this->two = $value;
	}

	public function setThree(int $value): void
	{
		$this->three = $value;
	}
}
