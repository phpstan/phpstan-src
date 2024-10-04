<?php // lint >= 8.0

namespace Bug11802b;

class HelloWorld
{
	public function __construct(
	) {}

	private function doBar():void {}

	public function doFoo(HelloWorld $x, string $y): void {
		$s = $x->$y();
	}
}
