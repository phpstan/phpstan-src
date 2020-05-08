<?php declare(strict_types = 1);

namespace PhpDoc\VarTag;

class A {}
class B extends A {}

class One
{
	/** @var A */
	protected $property;

	protected function requireInt(int $n): void {}
	public function method(): void { $this->requireInt($this->property); } // error: passed A, expected int
}

class Two extends One
{
	/** @var B */
	protected $property;

	public function method(): void { $this->requireInt($this->property); } // error: passed B, expected int
}

class Three extends Two
{
	/** Some comment */
	protected $property;

	public function method(): void { $this->requireInt($this->property); } // error: passed B, expected int
}

class Four extends Three
{
	protected $property;

	public function method(): void { $this->requireInt($this->property); } // error: passed B, expected int
}

class Five extends Four
{
	public function method(): void { $this->requireInt($this->property); } // error: passed B, expected int
}
