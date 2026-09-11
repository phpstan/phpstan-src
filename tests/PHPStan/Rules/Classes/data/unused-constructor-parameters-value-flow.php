<?php declare(strict_types = 1);

namespace UnusedConstructorParametersValueFlow;

class Foo
{

	private int $value;

	public function __construct(int $input, int $covered, int $used, int $overwritten)
	{
		while (rand(0, 1)) {
			$input = $input + 1;
		}
		$copy = $covered + 1;
		$this->value = $used + 1;
		$overwritten = 1;
		$this->value += $overwritten;
	}

}
