<?php declare(strict_types = 1);

namespace Bug13484;

class MyPeriod extends \DatePeriod {
	protected array $arguments;
	public function __construct(...$arguments) {
		$this->arguments = $arguments;
	}
}

function test(): MyPeriod
{
	return new MyPeriod();
}
