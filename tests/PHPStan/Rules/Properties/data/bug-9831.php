<?php // lint >= 8.1

namespace Bug9831;

class Foo
{
	private string $bar;

	public function __construct()
	{
		$var = function (): void {
			echo $this->bar;
		};

		$this->bar = '123';

		$var = function (): void {
			echo $this->bar;
		};
	}
}
