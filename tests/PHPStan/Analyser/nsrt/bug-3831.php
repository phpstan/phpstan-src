<?php declare(strict_types = 1);

namespace Bug3831Nsrt;

use function PHPStan\Testing\assertType;

class DynamicMethodCall
{
	public int $counter = 0;

	/** @var array<string> */
	public array $footer = [];

	public function test(): void
	{
		$this->counter = 0;
		assertType('0', $this->counter);

		$this->{'increment'}();
		assertType('int', $this->counter);
	}

	public function testDynamicVar(): void
	{
		$this->footer = [];
		assertType('array{}', $this->footer);

		$method = 'compileSection';
		$this->{$method}();
		assertType('array<string>', $this->footer);
	}

	private function increment(): int
	{
		$this->counter++;
		return 0;
	}

	private function compileSection(): void
	{
		$this->footer[] = 'section-name';
	}
}
